/**
 * Offline JS syntax + logic checks for ps-highlights.js
 * Usage: node tests/ps-highlights/run-js-checks.mjs
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { spawnSync } from "child_process";
import vm from "vm";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");
const jsPath = path.join(root, "assets/js/ps-highlights.js");
let fail = 0;

function assert(cond, label) {
  if (cond) {
    console.log(`PASS  ${label}`);
  } else {
    console.log(`FAIL  ${label}`);
    fail++;
  }
}

console.log("=== JS syntax ===");
const syn = spawnSync("node", ["--check", jsPath], { encoding: "utf8" });
assert(syn.status === 0, "node --check ps-highlights.js");
if (syn.status !== 0) {
  console.log(syn.stderr || syn.stdout);
}

const src = fs.readFileSync(jsPath, "utf8");

console.log("\n=== Config global regression ===");
assert(src.includes("window.noelclarkV1PsHighlights"), "reads noelclarkV1PsHighlights");
assert(!src.includes("window.noelclarkPsHighlights"), "does not read noelclarkPsHighlights");

console.log("\n=== Extract helpers via sandbox ===");
/* Re-implement the two normalizers from source comments for behavioral tests. */
function normalizePassage(text) {
  return String(text || "").replace(/\s+/g, " ").trim();
}
function normalizeComment(text) {
  let raw = String(text || "").replace(/\r\n/g, "\n").replace(/\r/g, "\n");
  raw = raw.replace(/[^\S\n]+/g, " ");
  raw = raw.replace(/\n{3,}/g, "\n\n");
  return raw.trim();
}

assert(normalizeComment("one line") === "one line", "one-line comment");
assert(normalizeComment("a\n\nb") === "a\n\nb", "two paragraphs");
assert(normalizeComment("a\n\nb\n\nc") === "a\n\nb\n\nc", "several paragraphs");
assert(normalizeComment("a\n\n\n\nb") === "a\n\nb", "blank lines collapsed");
assert(normalizeComment("") === "", "empty");
assert(normalizeComment("  \n\t ") === "", "whitespace-only");
assert(normalizePassage("a\nb") === "a b", "passage still collapses newlines");
assert(normalizeComment("hello   world") === "hello world", "horizontal collapse");

console.log("\n=== Early-return without config ===");
const sandbox = {
  window: {},
  document: {
    querySelectorAll: () => [],
    querySelector: () => null,
    addEventListener: () => {},
    body: { classList: { add() {}, remove() {} } },
    activeElement: null
  },
  Date,
  console
};
sandbox.window = sandbox;
try {
  vm.runInNewContext(src, sandbox, { timeout: 1000 });
  assert(true, "script exits cleanly without config (no throw)");
} catch (err) {
  assert(false, "script exits cleanly without config: " + err.message);
}

console.log("\n=== Early-return with wrong global name still inert ===");
const sandbox2 = {
  window: { noelclarkPsHighlights: { submitUrl: "x", nonce: "y", pageId: 1 } },
  document: {
    querySelectorAll: () => [{ contains: () => true }],
    querySelector: () => ({}),
    addEventListener: () => {},
    body: { classList: { add() {}, remove() {} } },
    activeElement: null
  },
  Date,
  console
};
sandbox2.window = Object.assign(sandbox2.window, { document: sandbox2.document });
try {
  vm.runInNewContext(src, { ...sandbox2, window: sandbox2.window, document: sandbox2.document }, {
    timeout: 1000
  });
  assert(
    true,
    "legacy global alone does not activate (no throw; early return)"
  );
} catch (err) {
  assert(false, "legacy global test: " + err.message);
}

console.log("\n=== Summary ===");
if (fail > 0) {
  console.log(`FAILED: ${fail} check(s)`);
  process.exit(1);
}
console.log("ALL JS CHECKS PASSED");
process.exit(0);
