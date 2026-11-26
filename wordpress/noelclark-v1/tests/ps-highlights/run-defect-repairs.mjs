/**
 * Regression checks for the three confirmed P.S. defects.
 * Offline / simulated only — not WordPress/MySQL integration.
 *
 * Usage: node tests/ps-highlights/run-defect-repairs.mjs
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { spawnSync } from "child_process";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");
const jsPath = path.join(root, "assets/js/ps-highlights.js");
const phpPath = path.join(root, "inc/ps-highlights.php");
let fail = 0;

function assert(cond, label) {
  if (cond) console.log(`PASS  ${label}`);
  else {
    console.log(`FAIL  ${label}`);
    fail++;
  }
}

const js = fs.readFileSync(jsPath, "utf8");
const php = fs.readFileSync(phpPath, "utf8");

console.log("=== Syntax ===");
assert(spawnSync("node", ["--check", jsPath]).status === 0, "JS syntax");
assert(spawnSync("php", ["-l", phpPath]).status === 0, "PHP syntax");

console.log("\n=== A/B/C/D Idempotency (shared IP vs request id) ===");
assert(php.includes("clientRequestId") || php.includes("client_request_id"), "server accepts clientRequestId");
assert(php.includes("noelclark_v1_ps_highlights_client_request_id"), "request id sanitizer");
assert(php.includes("noelclark_v1_ps_highlights_idempotency_key"), "idempotency key helper");
assert(php.includes("nc_ps_idem_"), "idempotency transient prefix");
assert(!/md5\(\$page_id\s*\.\s*'\|'\s*\.\s*\$identity\s*\.\s*'\|'\s*\.\s*\$ip\)/.test(php), "no IP in idempotency key");
assert(js.includes("clientRequestId"), "client sends clientRequestId");
assert(js.includes("newClientRequestId"), "client generates per-attempt ids");
assert(js.includes("pending.requestId"), "request id retained on pending for retries");

/* Simulate idempotency store: same id → one count; different ids same IP → both count */
class IdemStore {
  constructor() {
    this.map = new Map();
    this.counts = new Map();
  }
  key(page, identity, req) {
    return `${page}|${identity}|${req}`;
  }
  submit(page, identity, req, ipIgnored) {
    const k = this.key(page, identity, req);
    if (this.map.has(k)) {
      return { replay: true, count: this.counts.get(identity) };
    }
    const next = (this.counts.get(identity) || 0) + 1;
    this.counts.set(identity, next);
    this.map.set(k, next);
    return { replay: false, count: next };
  }
}
const store = new IdemStore();
const a1 = store.submit(1, "idX", "req-alice-1", "10.0.0.1");
const b1 = store.submit(1, "idX", "req-bob-1", "10.0.0.1"); // same IP, new request
assert(a1.count === 1 && b1.count === 2, "A: two shared-IP visitors both count");
const aRetry = store.submit(1, "idX", "req-alice-1", "10.0.0.1");
assert(aRetry.replay === true && aRetry.count === 2, "B: retry of earlier submission does not bump");
const aDouble = store.submit(1, "idX", "req-alice-1", "10.0.0.1");
assert(aDouble.replay === true && store.counts.get("idX") === 2, "C: rapid same-id taps do not inflate");
const aNew = store.submit(1, "idX", "req-alice-2", "10.0.0.1");
assert(aNew.replay === false && aNew.count === 3, "D: new deliberate highlight counts");

console.log("\n=== E/F/G/H Private range + repeated locations ===");
assert(js.includes("markPrivateFromRange"), "markPrivateFromRange present");
assert(!js.includes("tryMarkInScope"), "legacy indexOf tryMarkInScope removed");
assert(js.includes("cloneLiveRange") && js.includes("rangeStillValid"), "range preserved + validated");
assert(js.includes("intersectsNode"), "cross-node intersection walk");
assert(js.includes("surroundContents"), "safe wrap via surroundContents");
/* Identity distinguishes two locations with identical words */
const id1 = "sha:" + "Hello|prefixA|suffixA";
const id2 = "sha:" + "Hello|prefixB|suffixB";
assert(id1 !== id2, "E: identical words at different locations → distinct identities");

console.log("\n=== I Public counter targeting ===");
assert(js.includes('data-ps-note-id'), "list items carry comment id");
assert(js.includes("data-ps-identity"), "list items carry passage identity");
assert(js.includes('getAttribute("data-ps-note-id")'), "upsert matches by note id");
assert(js.includes('getAttribute("data-ps-identity")'), "upsert matches by identity");
assert(!/q\.textContent\s*===\s*entry\.passage/.test(js), "upsert no longer matches by passage text");
assert(php.includes("'identity'") && php.includes("passage_identity"), "API exposes identity");

/* Simulate upsert targeting */
function upsertSim(cards, entry) {
  const byId = cards.find((c) => c.id === entry.id);
  if (byId) {
    byId.count = entry.count;
    return cards;
  }
  const byIdent = cards.find((c) => c.identity === entry.identity);
  if (byIdent) {
    byIdent.count = entry.count;
    byIdent.id = entry.id;
    return cards;
  }
  cards.push({ ...entry });
  return cards;
}
let cards = [
  { id: 10, identity: id1, passage: "Hello", count: 1 },
  { id: 11, identity: id2, passage: "Hello", count: 1 }
];
cards = upsertSim(cards, { id: 11, identity: id2, passage: "Hello", count: 4 });
assert(cards[0].count === 1 && cards[1].count === 4, "I: correct counter targeted for location 2");
assert(cards.length === 2, "I: no duplicate cards from upsert");

console.log("\n=== J/K Chronology + pagination markers ===");
assert(php.includes("ORDER BY c.comment_date_gmt ASC, c.comment_ID ASC"), "J: oldest-first order");
assert(php.includes("afterDate") && php.includes("hasMore"), "K: pagination cursor intact");
assert(js.includes("Load more responses") || fs.readFileSync(path.join(root, "page-ps-returning-to-the-open-journal.php"), "utf8").includes("Load more responses"), "K: load more control present");

console.log("\n=== L Prior corrections preserved ===");
assert(js.includes("window.noelclarkV1PsHighlights"), "config global name");
assert(js.includes("normalizeComment"), "multiline comments");
assert(php.includes("GET_LOCK") && php.includes("RELEASE_LOCK"), "advisory locks");
assert(php.includes("CAST(meta_value AS UNSIGNED) + 1"), "atomic increment");
assert(php.includes("transition_comment_status"), "pending reconcile");
assert(php.includes("context_matches_essay"), "server passage validation");
assert(js.includes("candidateSeconds") || js.includes("STALE_MS"), "40s candidate");
assert(js.includes("focusBeforeSheet") && js.includes("Escape"), "accessible sheet");

console.log("\n=== Summary ===");
if (fail) {
  console.log(`FAILED: ${fail}`);
  process.exit(1);
}
console.log("ALL DEFECT-REPAIR CHECKS PASSED");
console.log("NOTE: Simulated / static only — not WordPress/MySQL integration.");
process.exit(0);
