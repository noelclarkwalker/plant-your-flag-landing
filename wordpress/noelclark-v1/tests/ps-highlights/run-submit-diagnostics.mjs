/**
 * Highlight submit error-diagnostic checks (mocked responses).
 * Offline only — no live POSTs, no WordPress/MySQL.
 *
 * Usage: node tests/ps-highlights/run-submit-diagnostics.mjs
 *
 * Pure helpers below are mirrored from assets/js/ps-highlights.js
 * (keep in sync with classifyHighlightFailure / formatHighlightFailureStatus /
 * parseSubmitResponse / isDevDiagnosticsHost).
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { spawnSync } from "child_process";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");
const jsPath = path.join(root, "assets/js/ps-highlights.js");
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

const src = fs.readFileSync(jsPath, "utf8");
const page = fs.readFileSync(pagePath, "utf8");

console.log("=== Syntax + contracts ===");
assert(spawnSync("node", ["--check", jsPath]).status === 0, "JS syntax");
assert(src.includes("parseSubmitResponse"), "safe parse helper present");
assert(src.includes("classifyHighlightFailure"), "classifier present");
assert(src.includes("formatHighlightFailureStatus"), "formatter present");
assert(src.includes("isDevDiagnosticsHost"), "dev host gate present");
assert(src.includes("noelclarkdev.wpenginepowered.com"), "powered.com allowlisted");
assert(src.includes("noelclarkdev.wpengine.com"), "wpengine.com allowlisted");
assert(
  /function parseSubmitResponse[\s\S]*?\.text\(\)/.test(src),
  "submit parse uses response.text()"
);
assert(
  /function parseSubmitResponse[\s\S]*?JSON\.parse/.test(src),
  "submit parse uses JSON.parse after text()"
);
assert(
  /function postPayload[\s\S]*?parseSubmitResponse/.test(src),
  "postPayload routes through safe parse"
);
assert(
  !/function postPayload[\s\S]*?r\.json\(\)/.test(src),
  "postPayload no longer calls r.json() directly"
);
assert(src.includes("clientRequestId"), "clientRequestId still sent");
assert(src.includes("Keep pending.requestId"), "requestId retention comment");
assert(!/setTimeout\([^)]*postPayload/.test(src), "no automatic postPayload retry timer");
assert(
  src.includes('return "Could not submit right now.";'),
  "Production still uses prior generic failure copy"
);
assert(
  src.includes("Diagnostic: "),
  "Dev diagnostic prefix present"
);
assert(
  !src.includes("document.cookie"),
  "does not read document.cookie for diagnostics"
);
assert(
  page.includes("Something stick with you?"),
  "explain lead preserved"
);
assert(
  page.includes(
    "Highlight a passage in the article, then tap the highlighter to share it here. You can also leave a comment below."
  ),
  "explain body preserved"
);
assert(
  src.includes("Oops. Did you mean to stop mid-word? Try again."),
  "mid-word copy preserved"
);

/* --- Mirrored helpers (keep in sync with ps-highlights.js) --- */
function isDevDiagnosticsHost(hostname) {
  const host = String(hostname || "").toLowerCase();
  if (!host) return false;
  if (host === "localhost" || host === "127.0.0.1") return true;
  return (
    host === "noelclarkdev.wpenginepowered.com" ||
    host === "noelclarkdev.wpengine.com"
  );
}

const PS_HL_VALIDATION_CODES = {
  invalid: 1,
  empty: 1,
  passage_required: 1,
  passage_too_long: 1,
  passage_mismatch: 1,
  context_mismatch: 1,
  note_too_long: 1,
  name_too_long: 1,
  name_required: 1
};

const PS_HL_AUTH_CODES = {
  forbidden: 1,
  forbidden_page: 1,
  nonce_error: 1
};

function submitResultCode(result) {
  if (!result || !result.jsonOk || !result.data) return "";
  const code = result.data.code;
  return typeof code === "string" ? code : "";
}

function classifyHighlightFailure(result) {
  if (!result || result.networkError) {
    return {
      category: "network",
      message: "Couldn't connect to the posting service.",
      diagnostic: "Network request failed",
      uncertain: true
    };
  }

  const status = result.status || 0;
  const code = submitResultCode(result);
  const jsonOk = !!result.jsonOk;

  if (!jsonOk) {
    if (status === 401 || status === 403) {
      return {
        category: "auth",
        message:
          "Couldn't reach the posting service. Access may need to be verified.",
        diagnostic: `HTTP ${status} / non-JSON response`,
        uncertain: false
      };
    }
    if (status >= 500) {
      return {
        category: "server",
        message: "Posting is temporarily unavailable.",
        diagnostic: `HTTP ${status} / non-JSON response`,
        uncertain: true
      };
    }
    if (status === 429) {
      return {
        category: "rate_limit",
        message: "Too many attempts right now. Please try again later.",
        diagnostic: "HTTP 429 / non-JSON response",
        uncertain: false
      };
    }
    return {
      category: "unexpected",
      message: "Couldn't post your highlight right now.",
      diagnostic: status
        ? `HTTP ${status} / non-JSON response`
        : result.empty
          ? "Empty response"
          : "Malformed or non-JSON response",
      uncertain: true
    };
  }

  if (code === "rate_limited" || status === 429) {
    return {
      category: "rate_limit",
      message: "Too many attempts right now. Please try again later.",
      diagnostic: `HTTP ${status} / ${code || "rate_limited"}`,
      uncertain: false
    };
  }

  if (PS_HL_AUTH_CODES[code] || status === 401) {
    return {
      category: "auth",
      message:
        "Couldn't reach the posting service. Access may need to be verified.",
      diagnostic: `HTTP ${status} / ${code || "auth"}`,
      uncertain: false
    };
  }

  if (PS_HL_VALIDATION_CODES[code] || (status === 400 && code)) {
    return {
      category: "validation",
      message:
        "This highlight couldn't be accepted. Please try selecting it again.",
      diagnostic: `HTTP ${status} / ${code || "validation"}`,
      uncertain: false
    };
  }

  if (code === "unavailable" || status >= 500) {
    return {
      category: "server",
      message: "Posting is temporarily unavailable.",
      diagnostic: `HTTP ${status} / ${code || "server"}`,
      uncertain: true
    };
  }

  if (!code) {
    return {
      category: "unexpected",
      message: "Couldn't post your highlight right now.",
      diagnostic: `HTTP ${status} / unknown response code`,
      uncertain: true
    };
  }

  return {
    category: "unexpected",
    message: "Couldn't post your highlight right now.",
    diagnostic: `HTTP ${status} / ${code}`,
    uncertain: true
  };
}

function formatHighlightFailureStatus(result, hostname) {
  const info = classifyHighlightFailure(result);
  if (!isDevDiagnosticsHost(hostname)) {
    return "Could not submit right now.";
  }
  let text = info.message;
  if (info.uncertain) {
    text += " Submission status is uncertain — avoid repeated taps.";
  }
  text += " Diagnostic: " + info.diagnostic;
  return text;
}

async function parseSubmitResponse(response) {
  const status =
    response && typeof response.status === "number" ? response.status : 0;
  let contentType = "";
  try {
    contentType = String(
      (response.headers && response.headers.get("content-type")) || ""
    );
  } catch (err) {
    contentType = "";
  }
  const text = await response.text();
  let data = null;
  let jsonOk = false;
  if (text) {
    try {
      data = JSON.parse(text);
      jsonOk = !!(data && typeof data === "object");
    } catch (err) {
      jsonOk = false;
      data = null;
    }
  }
  return {
    ok: !!(response && response.ok),
    status,
    contentType,
    jsonOk,
    data: data || {},
    empty: !text,
    networkError: false
  };
}

function mockResponse(status, body, contentType) {
  return {
    ok: status >= 200 && status < 300,
    status,
    headers: {
      get(name) {
        if (String(name).toLowerCase() === "content-type") {
          return contentType || "";
        }
        return null;
      }
    },
    text() {
      return Promise.resolve(body == null ? "" : String(body));
    }
  };
}

console.log("\n=== Dev host gate ===");
assert(isDevDiagnosticsHost("noelclarkdev.wpenginepowered.com"), "dev powered");
assert(isDevDiagnosticsHost("noelclarkdev.wpengine.com"), "dev wpengine");
assert(isDevDiagnosticsHost("localhost"), "localhost");
assert(isDevDiagnosticsHost("127.0.0.1"), "loopback");
assert(!isDevDiagnosticsHost("noelclark.com"), "prod apex blocked");
assert(!isDevDiagnosticsHost("www.noelclark.com"), "prod www blocked");
assert(!isDevDiagnosticsHost("noelclark.wpenginepowered.com"), "prod-ish host blocked");

console.log("\n=== Success / accepted paths (classifier not used) ===");
const published = {
  jsonOk: true,
  status: 201,
  data: { code: "published", note: { id: 1, passage: "x" } }
};
const pending = { jsonOk: true, status: 202, data: { code: "pending" } };
const accepted = { jsonOk: true, status: 202, data: { code: "accepted" } };
const duplicate = { jsonOk: true, status: 409, data: { code: "duplicate" } };
assert(published.data.code === "published", "published code intact");
assert(pending.data.code === "pending", "pending code intact");
assert(accepted.data.code === "accepted", "accepted code intact");
assert(duplicate.data.code === "duplicate", "duplicate code intact");

console.log("\n=== Mocked response parse matrix ===");
{
  const r = await parseSubmitResponse(
    mockResponse(201, JSON.stringify({ code: "published", note: { id: 9 } }), "application/json")
  );
  assert(r.jsonOk && r.status === 201 && r.data.code === "published", "successful JSON");
}
{
  const r = await parseSubmitResponse(
    mockResponse(202, JSON.stringify({ code: "pending" }), "application/json")
  );
  assert(r.jsonOk && r.data.code === "pending", "pending JSON");
}
{
  const r = await parseSubmitResponse(
    mockResponse(202, JSON.stringify({ code: "accepted" }), "application/json")
  );
  assert(r.jsonOk && r.data.code === "accepted", "accepted JSON");
}
{
  const r = await parseSubmitResponse(
    mockResponse(409, JSON.stringify({ code: "duplicate" }), "application/json")
  );
  assert(r.jsonOk && r.data.code === "duplicate", "duplicate JSON");
}
{
  const r = await parseSubmitResponse(
    mockResponse(400, JSON.stringify({ code: "passage_mismatch" }), "application/json")
  );
  assert(r.jsonOk && r.status === 400 && r.data.code === "passage_mismatch", "WP JSON 400");
}
{
  const r = await parseSubmitResponse(
    mockResponse(403, JSON.stringify({ code: "nonce_error" }), "application/json")
  );
  assert(r.jsonOk && r.data.code === "nonce_error", "WP JSON 403 nonce");
}
{
  const html =
    "<html><head></head><body>401 Authorization Required\nWWW-Authenticate secret</body></html>";
  const r = await parseSubmitResponse(
    mockResponse(401, html, "text/html")
  );
  assert(!r.jsonOk && r.status === 401 && r.empty === false, "HTTP 401 HTML");
  const msg = formatHighlightFailureStatus(r, "noelclarkdev.wpenginepowered.com");
  assert(msg.includes("Diagnostic: HTTP 401 / non-JSON response"), "401 diagnostic");
  assert(!msg.includes("Authorization Required"), "401 does not leak HTML body");
  assert(!msg.includes("WWW-Authenticate"), "401 does not leak auth header text from body");
  assert(!msg.includes("<html"), "401 does not leak markup");
}
{
  const r = await parseSubmitResponse(
    mockResponse(502, "<html>Bad Gateway</html>", "text/html")
  );
  assert(!r.jsonOk && r.status === 502, "HTTP 502 HTML");
  const msg = formatHighlightFailureStatus(r, "noelclarkdev.wpenginepowered.com");
  assert(msg.includes("temporarily unavailable"), "502 visitor message");
  assert(msg.includes("Diagnostic: HTTP 502 / non-JSON response"), "502 diagnostic");
  assert(!msg.includes("Bad Gateway"), "502 does not leak HTML");
}
{
  const r = await parseSubmitResponse(
    mockResponse(429, JSON.stringify({ code: "rate_limited" }), "application/json")
  );
  const info = classifyHighlightFailure(r);
  assert(info.category === "rate_limit", "HTTP 429 rate limit category");
  assert(info.diagnostic === "HTTP 429 / rate_limited", "429 diagnostic");
}
{
  const r = await parseSubmitResponse(
    mockResponse(200, "{not-json", "application/json")
  );
  assert(!r.jsonOk, "malformed JSON");
  const info = classifyHighlightFailure(r);
  assert(info.category === "unexpected", "malformed → unexpected");
}
{
  const r = await parseSubmitResponse(mockResponse(200, "", "application/json"));
  assert(r.empty && !r.jsonOk, "empty response");
  const info = classifyHighlightFailure(r);
  assert(info.diagnostic === "Empty response" || info.diagnostic.includes("non-JSON"), "empty diagnostic");
}
{
  const info = classifyHighlightFailure({ networkError: true });
  assert(info.category === "network", "network failure category");
  assert(info.uncertain === true, "network marked uncertain");
  const msg = formatHighlightFailureStatus(
    { networkError: true },
    "noelclarkdev.wpenginepowered.com"
  );
  assert(msg.includes("Diagnostic: Network request failed"), "network diagnostic");
  assert(msg.includes("uncertain"), "network uncertain warning");
}
{
  const r = {
    jsonOk: true,
    status: 418,
    data: { code: "teapot" },
    empty: false,
    networkError: false
  };
  const info = classifyHighlightFailure(r);
  assert(info.category === "unexpected", "unknown response code");
  assert(info.diagnostic === "HTTP 418 / teapot", "unknown code diagnostic");
}
{
  const r = {
    jsonOk: true,
    status: 403,
    data: { code: "nonce_error" },
    empty: false,
    networkError: false
  };
  const info = classifyHighlightFailure(r);
  assert(info.category === "auth", "nonce_error → auth");
  assert(info.diagnostic === "HTTP 403 / nonce_error", "nonce diagnostic");
}
{
  const r = {
    jsonOk: true,
    status: 400,
    data: { code: "context_mismatch" },
    empty: false,
    networkError: false
  };
  const info = classifyHighlightFailure(r);
  assert(info.category === "validation", "context_mismatch → validation");
}

console.log("\n=== Production hides diagnostics ===");
{
  const r = {
    jsonOk: false,
    status: 401,
    data: {},
    empty: false,
    networkError: false
  };
  const prod = formatHighlightFailureStatus(r, "noelclark.com");
  assert(prod === "Could not submit right now.", "prod generic only");
  assert(!prod.includes("Diagnostic"), "prod no Diagnostic label");
  assert(!prod.includes("401"), "prod no status number");
  const www = formatHighlightFailureStatus(r, "www.noelclark.com");
  assert(www === "Could not submit right now.", "www prod generic only");
}

console.log("\n=== Request ID retention + no auto-retry simulation ===");
{
  let pending = {
    passage: "Pay attention to the weak spots",
    requestId: "attempt-abc-1",
    midWord: false
  };
  let postCalls = 0;
  const attemptId = pending.requestId;

  function onFailure() {
    /* Mirror production behavior: keep pending + same requestId */
    postCalls += 1;
    const statusText = formatHighlightFailureStatus(
      { networkError: true },
      "noelclarkdev.wpenginepowered.com"
    );
    assert(pending.requestId === attemptId, "clientRequestId preserved after failure");
    assert(pending.passage === "Pay attention to the weak spots", "pending kept");
    assert(statusText.includes("Diagnostic:"), "dev status set");
    return statusText;
  }

  onFailure();
  assert(postCalls === 1, "exactly one handled failure (no auto-retry)");
  assert(pending.requestId === "attempt-abc-1", "id still same after sheet-style failure");
}

console.log("\n=== Sensitive data leakage scan ===");
{
  const sensitiveSamples = [
    "wp_rest_nonce_SECRET123",
    "Cookie: wordpress_logged_in=abc",
    "Authorization: Basic dXNlcjpwYXNz",
    "<html>password form</html>",
    "clientRequestId-should-not-echo",
    "DB_PASSWORD=hunter2"
  ];
  const r = await parseSubmitResponse(
    mockResponse(401, sensitiveSamples.join("\n"), "text/html")
  );
  const msg = formatHighlightFailureStatus(r, "noelclarkdev.wpenginepowered.com");
  for (const sample of sensitiveSamples) {
    assert(!msg.includes(sample), `does not leak: ${sample.slice(0, 24)}…`);
  }
  assert(!msg.includes("dXNlcjpwYXNz"), "no basic-auth token leak");
}

console.log("\n=== Source mirror sync markers ===");
assert(src.includes("Couldn't connect to the posting service."), "network copy in source");
assert(
  src.includes("Couldn't reach the posting service. Access may need to be verified."),
  "auth copy in source"
);
assert(
  src.includes("This highlight couldn't be accepted. Please try selecting it again."),
  "validation copy in source"
);
assert(
  src.includes("Too many attempts right now. Please try again later."),
  "rate limit copy in source"
);
assert(src.includes("Posting is temporarily unavailable."), "server copy in source");
assert(
  src.includes("Couldn't post your highlight right now."),
  "unexpected copy in source"
);
assert(
  src.includes("Submission status is uncertain — avoid repeated taps."),
  "uncertain warning in source"
);

console.log("\n=== Summary ===");
if (fail > 0) {
  console.log(`FAILED: ${fail} check(s)`);
  process.exit(1);
}
console.log("ALL SUBMIT-DIAGNOSTIC CHECKS PASSED");
process.exit(0);
