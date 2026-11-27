/**
 * Phase 1 pull-quote pure logic checks (Node).
 */
import { createRequire } from "module";
import { fileURLToPath } from "url";
import path from "path";

const require = createRequire(import.meta.url);
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const logic = require("../../blocks/interactive-pull-quote/logic.js");

let fail = 0;

function assert(cond, msg) {
  if (cond) {
    console.log(`PASS  ${msg}`);
    return;
  }
  fail += 1;
  console.log(`FAIL  ${msg}`);
}

assert(typeof logic.createQuoteId() === "string", "createQuoteId returns string");
assert(logic.createQuoteId().startsWith("pq-"), "createQuoteId prefix");

assert(Array.isArray(logic.EFFECT_TYPES) && logic.EFFECT_TYPES.length === 6, "six effect types");
assert(logic.EFFECT_TYPES.indexOf("margin_bracket") !== -1, "includes Margin Bracket");
assert(logic.EFFECT_TYPES.indexOf("expand") !== -1, "includes Expand");
assert(logic.isKnownEffect("highlight") === true, "highlight known");
assert(logic.isKnownEffect("not-an-effect") === false, "unknown effect rejected");

const norm = logic.normalizeMark({
  effect: "expand",
  text: "",
  expandContent: "and more",
  expandInsertOffset: 12,
});
assert(norm.effect === "expand", "normalizeMark keeps expand");
assert(norm.expandContent === "and more", "normalizeMark expandContent");
assert(norm.expandInsertOffset === 12, "normalizeMark expandInsertOffset");
assert(logic.normalizeMark({ effect: "nope" }).effect === "none", "unknown → none");

assert(
  logic.hashNeighbors("a", "b") === logic.hashNeighbors("a", "b"),
  "neighbor hash stable"
);
assert(
  logic.hashNeighbors("a", "b") !== logic.hashNeighbors("a", "c"),
  "neighbor hash changes with after text"
);

assert(logic.stripTags("<em>Hello</em> world") === "Hello world", "stripTags");
assert(logic.excerptText("abcdefghijklmnopqrstuvwxyz", 10).endsWith("…"), "excerpt truncates");

assert(
  logic.assessSelection({
    blockName: "core/paragraph",
    startOffset: 0,
    endOffset: 5,
    crossBlock: false,
    partialLink: false,
    empty: false,
  }).ok === true,
  "safe paragraph selection ok"
);

assert(
  logic.assessSelection({
    blockName: "core/paragraph",
    startOffset: 0,
    endOffset: 5,
    crossBlock: true,
    partialLink: false,
    empty: false,
  }).ok === false,
  "cross-paragraph refused"
);

assert(
  logic.assessSelection({
    blockName: "core/image",
    startOffset: 0,
    endOffset: 5,
    crossBlock: false,
    partialLink: false,
    empty: false,
  }).ok === false,
  "non-paragraph refused"
);

assert(
  logic.assessSelection({
    blockName: "core/paragraph",
    startOffset: 0,
    endOffset: 5,
    crossBlock: false,
    partialLink: true,
    empty: false,
  }).ok === false,
  "partial link refused"
);

assert(
  logic.assessSelection({
    blockName: "core/paragraph",
    startOffset: 2,
    endOffset: 2,
    crossBlock: false,
    partialLink: false,
    empty: true,
  }).ok === false,
  "empty selection refused"
);

const formats = [];
for (let i = 0; i < 10; i += 1) {
  formats[i] = i >= 3 && i < 8 ? [{ type: "core/link", attributes: { url: "https://example.com" } }] : [];
}
assert(logic.selectionCutsPartialLink(formats, 3, 8) === false, "whole link selection ok");
assert(logic.selectionCutsPartialLink(formats, 4, 7) === true, "partial link inside detected");
assert(logic.selectionCutsPartialLink(formats, 0, 2) === false, "no link range ok");

const mergePlan = logic.planRestore({
  neighborHash: logic.hashNeighbors("Before. ", " After."),
  beforeHtml: "Before. ",
  afterHtml: " After.",
  currentBeforeHtml: "Before. ",
  currentAfterHtml: " After.",
  neighborsPresent: true,
});
assert(mergePlan.mode === "merge", "unchanged neighbors → merge");

const editedPlan = logic.planRestore({
  neighborHash: logic.hashNeighbors("Before. ", " After."),
  beforeHtml: "Before. ",
  afterHtml: " After.",
  currentBeforeHtml: "Before — edited. ",
  currentAfterHtml: " After.",
  neighborsPresent: true,
});
assert(editedPlan.mode === "paragraph", "edited neighbors → paragraph fallback");

const missingPlan = logic.planRestore({
  neighborHash: logic.hashNeighbors("Before. ", " After."),
  beforeHtml: "Before. ",
  afterHtml: " After.",
  currentBeforeHtml: "",
  currentAfterHtml: "",
  neighborsPresent: false,
});
assert(missingPlan.mode === "paragraph", "missing neighbors → paragraph fallback");

const soloPlan = logic.planRestore({
  neighborHash: logic.hashNeighbors("", ""),
  beforeHtml: "",
  afterHtml: "",
  currentBeforeHtml: "",
  currentAfterHtml: "",
  neighborsPresent: false,
});
assert(soloPlan.mode === "paragraph", "solo quote restore → paragraph");

console.log("");
if (fail) {
  console.log(`RESULT: ${fail} failure(s)`);
  process.exit(1);
}
console.log("RESULT: all logic checks passed");
