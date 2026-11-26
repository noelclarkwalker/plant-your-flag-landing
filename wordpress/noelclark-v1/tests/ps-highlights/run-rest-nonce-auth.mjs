/**
 * P.S. highlight REST cookie-auth (wp_rest / X-WP-Nonce) regression checks.
 * Offline / mocked only — no live WordPress POSTs.
 *
 * Usage: node tests/ps-highlights/run-rest-nonce-auth.mjs
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { spawnSync } from "child_process";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");
const jsPath = path.join(root, "assets/js/ps-highlights.js");
const phpPath = path.join(root, "inc/ps-highlights.php");
const pagePath = path.join(root, "page-ps-returning-to-the-open-journal.php");
let fail = 0;

function assert(cond, label) {
  if (cond) {
    console.log(`PASS  ${label}`);
  } else {
    console.log(`FAIL  ${label}`);
    fail++;
  }
}

const js = fs.readFileSync(jsPath, "utf8");
const php = fs.readFileSync(phpPath, "utf8");
const page = fs.readFileSync(pagePath, "utf8");

console.log("=== Syntax ===");
assert(spawnSync("node", ["--check", jsPath]).status === 0, "JS syntax");
assert(spawnSync("php", ["-l", phpPath]).status === 0, "PHP syntax");

console.log("\n=== Config + request contracts ===");
assert(php.includes("'restNonce'"), "frontend_config localizes restNonce");
assert(php.includes("wp_create_nonce('wp_rest')"), "restNonce uses wp_rest action");
assert(
  php.includes("wp_create_nonce(NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION)"),
  "custom highlight nonce still created"
);
assert(
  php.includes("NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION") &&
    php.includes("wp_verify_nonce($nonce, NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION)"),
  "custom nonce still verified with same action"
);
assert(js.includes('headers["X-WP-Nonce"]'), "JS sets X-WP-Nonce header");
assert(js.includes("config.restNonce"), "JS reads config.restNonce");
assert(/nonce:\s*config\.nonce/.test(js), "custom nonce still in JSON body");
assert(js.includes('credentials: "same-origin"'), "credentials same-origin preserved");
assert(
  php.includes("'permission_callback' => '__return_true'"),
  "REST route still open (anonymous supported)"
);
assert(
  !/permission_callback'\s*=>\s*'is_user_logged_in'/.test(php) &&
    !/permission_callback'\s*=>\s*"is_user_logged_in"/.test(php),
  "route does not require login"
);

console.log("\n=== Pilot page no-cache (narrow) ===");
assert(
  php.includes("noelclark_v1_ps_highlights_send_nocache_headers"),
  "pilot nocache helper present"
);
assert(
  php.includes("noelclark_v1_is_ps_highlights_pilot_page()"),
  "nocache gated by pilot page"
);
assert(php.includes("DONOTCACHEPAGE"), "DONOTCACHEPAGE for pilot");
assert(php.includes("nocache_headers()"), "nocache_headers() called");
assert(
  /function noelclark_v1_ps_highlights_send_nocache_headers[\s\S]*?noelclark_v1_is_ps_highlights_pilot_page/.test(
    php
  ),
  "nocache function checks pilot page before headers"
);

console.log("\n=== Simulated WordPress user-context auth (not live WP) ===");
/*
 * Models the WordPress nonce user-scoping that caused nonce_error:
 * create as logged-in uid, verify as REST uid 0 without X-WP-Nonce.
 * Then models the Mail Room fix: X-WP-Nonce/wp_rest aligns REST uid
 * before custom-nonce verify. This is SIMULATED — not runtime WP.
 */
const CUSTOM_ACTION = "noelclark_v1_ps_highlights";
const REST_ACTION = "wp_rest";

function makeNonceStore() {
  const issued = new Map();
  let seq = 0;
  return {
    create(action, uid) {
      seq += 1;
      const token = `n${seq}:${action}:u${uid}`;
      issued.set(token, { action, uid });
      return token;
    },
    verify(token, action, uid) {
      const row = issued.get(token);
      if (!row) return false;
      return row.action === action && row.uid === uid;
    }
  };
}

function buildHeaders(config) {
  const headers = { "Content-Type": "application/json" };
  if (config.restNonce) {
    headers["X-WP-Nonce"] = config.restNonce;
  }
  return headers;
}

/**
 * Simulate REST cookie auth: without valid X-WP-Nonce/wp_rest, uid=0.
 * With matching rest nonce for a logged-in session, REST uid becomes that user.
 */
function resolveRestUid(headers, store, sessionUid) {
  const restHeader = headers["X-WP-Nonce"];
  if (!restHeader) {
    return 0;
  }
  if (store.verify(restHeader, REST_ACTION, sessionUid)) {
    return sessionUid;
  }
  if (store.verify(restHeader, REST_ACTION, 0)) {
    return 0;
  }
  return 0;
}

function simulateSubmit({ pageUid, sendRestNonce }) {
  const store = makeNonceStore();
  const customNonce = store.create(CUSTOM_ACTION, pageUid);
  const restNonce = store.create(REST_ACTION, pageUid);
  const config = {
    nonce: customNonce,
    restNonce: sendRestNonce ? restNonce : ""
  };
  const headers = buildHeaders(config);
  const body = {
    nonce: config.nonce,
    pageId: 42,
    intent: "highlight",
    clientRequestId: "attempt-1"
  };
  const restUid = resolveRestUid(headers, store, pageUid);
  const ok = store.verify(body.nonce, CUSTOM_ACTION, restUid);
  return { ok, restUid, pageUid, headers, body };
}

/* Broken pre-fix path: logged-in page, no X-WP-Nonce → REST uid 0 */
{
  const broken = simulateSubmit({ pageUid: 7, sendRestNonce: false });
  assert(broken.pageUid === 7 && broken.restUid === 0, "SIM: logged-in page → anonymous REST without X-WP-Nonce");
  assert(broken.ok === false, "SIM: custom nonce fails across uid mismatch (nonce_error)");
  assert(broken.headers["X-WP-Nonce"] === undefined, "SIM: broken path sends no X-WP-Nonce");
  assert(broken.body.nonce.startsWith("n"), "SIM: custom nonce still in body");
}

/* Fixed path: logged-in page + X-WP-Nonce aligns REST uid */
{
  const fixed = simulateSubmit({ pageUid: 7, sendRestNonce: true });
  assert(fixed.restUid === 7, "SIM: X-WP-Nonce aligns REST to logged-in uid");
  assert(fixed.ok === true, "SIM: custom nonce verifies after REST auth alignment");
  assert(!!fixed.headers["X-WP-Nonce"], "SIM: fixed path sends X-WP-Nonce");
  assert(
    fixed.headers["X-WP-Nonce"].includes(REST_ACTION),
    "SIM: X-WP-Nonce is the wp_rest token"
  );
  assert(
    fixed.body.nonce.includes(CUSTOM_ACTION),
    "SIM: body still carries custom action nonce"
  );
  assert(
    fixed.headers["X-WP-Nonce"] !== fixed.body.nonce,
    "SIM: rest nonce and custom nonce are distinct tokens"
  );
}

/* Anonymous: uid 0 create + uid 0 REST — works with or without requiring login */
{
  const anon = simulateSubmit({ pageUid: 0, sendRestNonce: true });
  assert(anon.restUid === 0 && anon.ok === true, "SIM: anonymous highlight supported (uid 0↔0)");
  assert(!!anon.headers["X-WP-Nonce"], "SIM: anonymous still sends wp_rest nonce; login not required");
}

/* Invalid custom nonce rejected even when REST auth aligns */
{
  const store = makeNonceStore();
  const restNonce = store.create(REST_ACTION, 7);
  const headers = buildHeaders({ restNonce });
  const restUid = resolveRestUid(headers, store, 7);
  assert(restUid === 7, "SIM: REST auth ok");
  assert(store.verify("forged-token", CUSTOM_ACTION, restUid) === false, "SIM: forged custom nonce rejected");
}

const loggedIn = buildHeaders({
  restNonce: "rest-for-admin-session",
  nonce: "custom-hl-nonce"
});
assert(
  loggedIn["X-WP-Nonce"] === "rest-for-admin-session",
  "header builder: logged-in context sends X-WP-Nonce"
);

const anonymous = buildHeaders({
  restNonce: "rest-for-uid-0",
  nonce: "custom-hl-nonce-anon"
});
assert(
  anonymous["X-WP-Nonce"] === "rest-for-uid-0",
  "header builder: anonymous also gets X-WP-Nonce"
);

const legacy = buildHeaders({ nonce: "custom-only" });
assert(
  legacy["X-WP-Nonce"] === undefined,
  "header builder: missing restNonce does not invent a header"
);

console.log(
  "NOTE: above auth cases are SIMULATED offline models, not live WordPress runtime verification."
);

console.log("\n=== Security gates retained ===");
assert(php.includes("'code' => 'nonce_error'"), "invalid custom nonce → nonce_error");
assert(php.includes("'code' => 'forbidden_page'"), "wrong page → forbidden_page");
assert(php.includes("ps/returning-to-the-open-journal"), "nested pilot path retained");
assert(
  php.includes("return $pilot_id > 0 && $page_id === $pilot_id;"),
  "exact pilot page ID match retained"
);
assert(php.includes("noelclark_v1_ps_highlights_request_is_same_site"), "same-site check retained");
assert(php.includes("passage_mismatch") || php.includes("context_mismatch"), "passage checks retained");
assert(js.includes("Keep pending.requestId") || js.includes("pending.requestId"), "requestId retention");
assert(!/setTimeout\([^)]*postPayload/.test(js), "no automatic postPayload retry");
assert(php.includes("nc_ps_idem_"), "idempotency unchanged");
assert(php.includes("GET_LOCK"), "locks unchanged");
assert(php.includes("CAST(meta_value AS UNSIGNED) + 1"), "counter increment unchanged");

console.log("\n=== Comment path + diagnostics + copy ===");
assert(js.includes("intent: \"comment\"") || js.includes("intent: 'comment'"), "comment submit path present");
assert(
  /function onCommentSubmit[\s\S]*?postPayload\(/.test(js),
  "comment submissions still use postPayload (inherits X-WP-Nonce)"
);
assert(js.includes("formatHighlightFailureStatus"), "diagnostic formatter retained");
assert(js.includes("isDevDiagnosticsHost"), "dev diagnostic gate retained");
assert(page.includes("Something stick with you?"), "explain lead preserved");
assert(
  page.includes(
    "Highlight a passage in the article, then tap the highlighter to share it here. You can also leave a comment below."
  ),
  "explain body preserved"
);
assert(
  js.includes("Oops. Did you mean to stop mid-word? Try again."),
  "mid-word copy preserved"
);
assert(js.includes("hasIncompleteWordBoundary"), "word-boundary helper retained");

console.log("\n=== Summary ===");
if (fail > 0) {
  console.log(`FAILED: ${fail} check(s)`);
  process.exit(1);
}
console.log("ALL REST-NONCE AUTH CHECKS PASSED");
process.exit(0);
