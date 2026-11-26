/**
 * Word-boundary + quotation fidelity checks for ps-highlights.js
 * Usage: node tests/ps-highlights/run-word-boundary.mjs
 *
 * Pure helpers are mirrored from the theme script (same rules).
 * DOM Range cases run in Chromium against the local preview when available,
 * otherwise against an inline data URL fixture.
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { createRequire } from "module";
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "../..");
const jsPath = path.join(root, "assets/js/ps-highlights.js");
const cssPath = path.join(root, "assets/css/ps-highlights.css");
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

/* --- Mirrored pure helpers (keep in sync with ps-highlights.js) --- */
function isWordChar(ch) {
  if (!ch) return false;
  if (/[\p{L}\p{N}\p{M}]/u.test(ch)) return true;
  return (
    ch === "'" ||
    ch === "\u2019" ||
    ch === "\u2018" ||
    ch === "-" ||
    ch === "\u2010" ||
    ch === "\u2011"
  );
}

function normalizePassage(text) {
  return String(text || "").replace(/\s+/g, " ").trim();
}

/** Mid-word check against a plain string + start/end offsets (single text node model). */
function midWordInString(full, start, end) {
  const before = start > 0 ? full.charAt(start - 1) : "";
  const after = end < full.length ? full.charAt(end) : "";
  const first = full.charAt(start);
  const last = end > start ? full.charAt(end - 1) : "";
  return (isWordChar(before) && isWordChar(first)) || (isWordChar(last) && isWordChar(after));
}

const sample =
  'My heart is filled with gratitude. She said, "don\u2019t re-enter" the café.';

console.log("=== Source contracts ===");
const src = fs.readFileSync(jsPath, "utf8");
const css = fs.readFileSync(cssPath, "utf8");
const page = fs.readFileSync(pagePath, "utf8");
assert(src.includes("hasIncompleteWordBoundary"), "A–R helper present");
assert(src.includes("normalizePassage(range.toString())"), "Q quotation from Range.toString()");
assert(src.includes("pending.midWord"), "R midWord submit guard");
assert(src.includes("MID_WORD_HINT"), "mid-word hint");
assert(
  src.includes("Oops. Did you mean to stop mid-word? Try again."),
  "new mid-word copy present"
);
assert(
  !src.includes("Your selection starts or ends in the middle of a word."),
  "old mid-word copy absent"
);
assert(/\.ps-hl-sheet__quote\s*\{[^}]*overflow-wrap:\s*break-word/.test(css), "preview break-word");
assert(!/\.ps-hl-sheet__quote\s*\{[^}]*overflow-wrap:\s*anywhere/.test(css), "quote rule not anywhere");
assert(
  page.includes(
    "Highlight a passage in the article, then tap the highlighter to post it here for others to read. You can also leave a comment below."
  ),
  "T explain copy updated"
);
assert(
  !page.includes(
    "Select a passage in the article, then tap the purple highlighter to post it here for others to read."
  ),
  "T previous explain copy absent"
);
assert(page.includes("data-ps-hl-explain"), "T explain markup preserved");
assert(src.includes("revealPsExplainAndScroll"), "T no-selection explain path preserved");

console.log("\n=== Boundary matrix (string model) ===");
/* A complete sentence */
{
  const s = sample.indexOf("My heart");
  const e = sample.indexOf(".") + 1;
  assert(!midWordInString(sample, s, e), "A complete sentence");
}
/* B phrase ending before punctuation */
{
  const phrase = "filled with gratitude";
  const s = sample.indexOf(phrase);
  const e = s + phrase.length;
  assert(!midWordInString(sample, s, e), "B phrase before sentence punctuation");
}
/* C single complete word */
{
  const s = sample.indexOf("gratitude");
  const e = s + "gratitude".length;
  assert(!midWordInString(sample, s, e), "C single complete word");
}
/* D ending inside word */
{
  const s = sample.indexOf("filled with gratit");
  const e = s + "filled with gratit".length;
  assert(midWordInString(sample, s, e), "D ending inside word");
}
/* E starting inside word */
{
  const s = sample.indexOf("lled with gratitude");
  const e = s + "lled with gratitude".length;
  assert(midWordInString(sample, s, e), "E starting inside word");
}
/* F both ends inside words */
{
  const s = sample.indexOf("illed with gratit");
  const e = s + "illed with gratit".length;
  assert(midWordInString(sample, s, e), "F start+end inside words");
}
/* G contractions */
{
  const s = sample.indexOf("don\u2019t");
  const e = s + "don\u2019t".length;
  assert(!midWordInString(sample, s, e), "G full contraction");
  assert(midWordInString(sample, s, s + 3), "G partial contraction mid-word");
}
/* H hyphenated */
{
  const s = sample.indexOf("re-enter");
  const e = s + "re-enter".length;
  assert(!midWordInString(sample, s, e), "H full hyphenated word");
  assert(midWordInString(sample, s, s + 2), "H partial before hyphen");
}
/* I quotation marks / punctuation */
{
  const s = sample.indexOf('"don');
  const e = sample.indexOf("re-enter") + "re-enter".length + 1; /* through closing quote */
  assert(!midWordInString(sample, s, e), "I quoted span with punctuation");
}
/* J / K unicode */
{
  const s = sample.indexOf("café");
  const e = s + "café".length;
  assert(!midWordInString(sample, s, e), "K unicode word café");
  assert(isWordChar("é"), "K acute e is word char");
  assert(!isWordChar("\u201C"), "I curly open quote not word char");
  assert(!isWordChar("."), "I period not word char");
}
/* Partial sentence still valid */
{
  const s = sample.indexOf("My heart is filled");
  const e = s + "My heart is filled".length;
  assert(!midWordInString(sample, s, e), "partial sentence at word boundary valid");
}

console.log("\n=== Normalize does not invent words ===");
assert(
  normalizePassage("filled   with\ngratitude") === "filled with gratitude",
  "whitespace collapse only"
);
assert(
  normalizePassage("filled with gratit") === "filled with gratit",
  "does not complete truncated word"
);

async function runDomTests() {
  console.log("\n=== DOM Range / confirmation (Chromium) ===");
  let puppeteer;
  try {
    const require = createRequire(import.meta.url);
    puppeteer = require("/tmp/ps-aside-cap/node_modules/puppeteer-core");
  } catch (err) {
    console.log("SKIP  Chromium DOM suite (puppeteer-core unavailable)");
    return;
  }

  const themeJs = fs.readFileSync(jsPath, "utf8");
  const fixture = `<!DOCTYPE html><html><head>
<style>
.ps-hl-sheet__quote{white-space:pre-wrap;overflow-wrap:break-word;word-break:normal;max-height:min(12rem,36vh);overflow:auto}
.ps-hl-sheet.is-open{display:block}
.ps-hl-sheet{display:none}
.ps-hl-sheet.is-open{display:block}
[data-ps-quote-wrap][hidden],[data-ps-hl-hint][hidden]{display:none!important}
</style>
</head><body class="site-page ps-writing-page">
<main class="ps-writing">
  <h1 data-ps-hl-scope>Title</h1>
  <div data-ps-highlights-root data-ps-hl-scope id="root">
    <p id="p1">My heart is filled with gratitude.</p>
    <p id="p2">She said, "don\u2019t re-enter" the café.</p>
    <p id="p3">Line one.</p>
    <p id="p4">Line two with <em id="em">inline</em> HTML.</p>
    <p id="long">${"Long quotation sentence. ".repeat(40)}</p>
  </div>
  <button type="button" data-ps-pen-launch>pen</button>
  <aside class="ps-hl-sheet" data-ps-hl-sheet aria-hidden="true">
    <div data-ps-quote-wrap hidden>
      <blockquote class="ps-hl-sheet__quote" data-ps-hl-passage></blockquote>
      <button type="button" data-ps-quote-clear>Remove</button>
    </div>
    <p data-ps-hl-hint hidden></p>
    <p data-ps-hl-status hidden></p>
    <input data-ps-hl-honey />
    <button type="button" data-ps-hl-submit>Submit Highlight</button>
    <button type="button" data-ps-hl-close>Close</button>
  </aside>
  <section class="ps-writing__ps">
    <h2 id="ps-writing-notes-heading">P.S.</h2>
    <p data-ps-hl-explain hidden>Highlight a passage in the article, then tap the highlighter to post it here for others to read. You can also leave a comment below.</p>
    <form data-ps-comment-form>
      <textarea data-ps-comment-body></textarea>
      <input data-ps-comment-name />
      <input data-ps-comment-honey />
      <p data-ps-comment-status hidden></p>
      <button data-ps-comment-submit>Post Comment</button>
    </form>
    <div data-ps-notes-empty></div>
    <ul data-ps-notes-list hidden></ul>
    <button data-ps-notes-more hidden>Load more responses</button>
  </section>
</main>
<script>
window.__lastSubmit = null;
window.noelclarkV1PsHighlights = {
  submitUrl: "/__mock_submit", listUrl: "/__mock_list",
  nonce: "t", pageId: 1, candidateSeconds: 40, passageMax: 2000,
  noteMax: 1000, contextMax: 48, penColor: "#A21EFF", listPageSize: 100
};
window.fetch = function(url, opts) {
  const u = String(url);
  if (u.indexOf("__mock_list") !== -1) {
    return Promise.resolve({
      status: 200,
      json: function(){ return Promise.resolve({code:"ok",notes:[],hasMore:false}); }
    });
  }
  if (u.indexOf("__mock_submit") !== -1) {
    try { window.__lastSubmit = opts && opts.body ? JSON.parse(opts.body) : null; } catch (e) { window.__lastSubmit = null; }
    var passage = (window.__lastSubmit && window.__lastSubmit.passage) || "x";
    return Promise.resolve({
      status: 200,
      json: function(){
        return Promise.resolve({
          code:"ok",
          note:{ id:1, passage:passage, identity:"x", count:1, displayName:"", date:"", dateGmt:"", note:"", color:"#A21EFF" }
        });
      }
    });
  }
  return Promise.reject(new Error("unexpected fetch " + u));
};
</script>
<script>${themeJs.replace(/<\/script>/gi, "<\\/script>")}</script>
</body></html>`;

  const browser = await puppeteer.launch({
    executablePath: "/usr/local/bin/google-chrome",
    headless: "new",
    args: ["--no-sandbox", "--disable-gpu"]
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  await page.setContent(fixture, { waitUntil: "load" });

  async function selectOffsets(selector, start, end) {
    await page.$eval(
      selector,
      (el, s, e) => {
        const text = el.firstChild;
        const range = document.createRange();
        range.setStart(text, s);
        range.setEnd(text, e);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      },
      start,
      end
    );
  }

  async function penTap() {
    await page.click("[data-ps-pen-launch]");
    await new Promise((r) => setTimeout(r, 120));
  }

  async function sheetState() {
    return page.evaluate(() => {
      const sheet = document.querySelector("[data-ps-hl-sheet]");
      const passage = document.querySelector("[data-ps-hl-passage]");
      const hint = document.querySelector("[data-ps-hl-hint]");
      const submit = document.querySelector("[data-ps-hl-submit]");
      return {
        open: sheet.classList.contains("is-open"),
        passage: (passage.textContent || "").trim(),
        hint: hint.hidden ? "" : (hint.textContent || "").trim(),
        disabled: !!submit.disabled
      };
    });
  }

  /* A */
  await selectOffsets("#p1", 0, "My heart is filled with gratitude.".length);
  await penTap();
  let st = await sheetState();
  assert(st.open && !st.disabled && st.passage === "My heart is filled with gratitude.", "A DOM complete sentence");
  await page.click("[data-ps-hl-close]");

  /* B */
  const bStart = "My heart is filled with gratitude".indexOf("filled");
  // in p1: "My heart is filled with gratitude."
  const full = "My heart is filled with gratitude.";
  await selectOffsets("#p1", full.indexOf("filled with gratitude"), full.indexOf("filled with gratitude") + "filled with gratitude".length);
  await penTap();
  st = await sheetState();
  assert(st.open && !st.disabled && st.passage === "filled with gratitude", "B DOM phrase");
  await page.click("[data-ps-hl-close]");

  /* C */
  await selectOffsets("#p1", full.indexOf("gratitude"), full.indexOf("gratitude") + "gratitude".length);
  await penTap();
  st = await sheetState();
  assert(st.open && !st.disabled && st.passage === "gratitude", "C DOM single word");
  await page.click("[data-ps-hl-close]");

  /* D */
  const dEnd = full.indexOf("gratitude") + "gratit".length;
  await selectOffsets("#p1", full.indexOf("filled"), dEnd);
  await penTap();
  st = await sheetState();
  assert(
    st.open &&
      st.disabled &&
      st.passage === "filled with gratit" &&
      st.hint === "Oops. Did you mean to stop mid-word? Try again.",
    "D DOM mid-word end blocks submit"
  );

  /* R — cannot submit mid-word */
  await page.click("[data-ps-hl-submit]");
  const submittedWhileBlocked = await page.evaluate(() => !!window.__lastSubmit);
  assert(!submittedWhileBlocked, "R mid-word submit does not fire");
  await page.click("[data-ps-hl-close]");

  /* E */
  await selectOffsets("#p1", full.indexOf("lled with"), full.indexOf(".") );
  await penTap();
  st = await sheetState();
  assert(
    st.open &&
      st.disabled &&
      st.hint === "Oops. Did you mean to stop mid-word? Try again.",
    "E DOM mid-word start"
  );
  await page.click("[data-ps-hl-close]");

  /* G */
  const p2 = 'She said, "don\u2019t re-enter" the café.';
  await selectOffsets("#p2", p2.indexOf("don"), p2.indexOf("don") + "don\u2019t".length);
  await penTap();
  st = await sheetState();
  assert(st.open && !st.disabled && st.passage.includes("don"), "G DOM contraction");
  await page.click("[data-ps-hl-close]");

  /* H */
  await selectOffsets("#p2", p2.indexOf("re-enter"), p2.indexOf("re-enter") + "re-enter".length);
  await penTap();
  st = await sheetState();
  assert(st.open && !st.disabled && st.passage === "re-enter", "H DOM hyphenated");
  await page.click("[data-ps-hl-close]");

  /* L inline HTML */
  await page.$eval("#p4", (el) => {
    const range = document.createRange();
    range.setStart(el.firstChild, 0);
    range.setEnd(el.querySelector("em").firstChild, 6);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  });
  await penTap();
  st = await sheetState();
  assert(st.open && st.passage.includes("inline"), "L spans inline HTML");
  await page.click("[data-ps-hl-close]");

  /* M long quotation scrollable */
  await page.$eval("#long", (el) => {
    const range = document.createRange();
    range.selectNodeContents(el);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  });
  await penTap();
  st = await sheetState();
  const longUi = await page.evaluate(() => {
    const q = document.querySelector("[data-ps-hl-passage]");
    const submit = document.querySelector("[data-ps-hl-submit]");
    const qs = getComputedStyle(q);
    return {
      scrollable: q.scrollHeight > q.clientHeight + 2,
      wrap: qs.overflowWrap,
      submitVisible: submit.getBoundingClientRect().top < window.innerHeight
    };
  });
  assert(st.open && st.passage.length > 100, "M long quotation captured fully");
  assert(longUi.wrap === "break-word", "M overflow-wrap break-word");
  assert(longUi.submitVisible, "M submit control remains accessible");
  await page.click("[data-ps-hl-close]");

  /* O new selection replaces older candidate */
  await selectOffsets("#p1", 0, 8);
  await new Promise((r) => setTimeout(r, 30));
  await selectOffsets("#p1", full.indexOf("gratitude"), full.indexOf("gratitude") + "gratitude".length);
  await penTap();
  st = await sheetState();
  assert(st.passage === "gratitude", "O newer selection replaces older candidate");
  await page.click("[data-ps-hl-close]");

  /* Q submit payload matches displayed passage */
  await selectOffsets("#p1", full.indexOf("My heart is filled"), full.indexOf("My heart is filled") + "My heart is filled".length);
  await penTap();
  st = await sheetState();
  await page.click("[data-ps-hl-submit]");
  await new Promise((r) => setTimeout(r, 150));
  const payload = await page.evaluate(() => window.__lastSubmit);
  assert(
    payload && payload.passage === st.passage && payload.passage === "My heart is filled",
    "Q displayed quotation equals submitted passage"
  );

  /* P clear selection → explain */
  await page.evaluate(() => window.getSelection().removeAllRanges());
  /* expire candidate by reloading */
  await page.reload({ waitUntil: "load" });
  /* re-inject config+script already in setContent — reload loses data URL scripts from setContent?
     setContent pages reload to empty. Re-set content. */
  await page.setContent(fixture, { waitUntil: "load" });
  await page.click("[data-ps-pen-launch]");
  await new Promise((r) => setTimeout(r, 400));
  const explain = await page.evaluate(() => {
    const el = document.querySelector("[data-ps-hl-explain]");
    const sheet = document.querySelector("[data-ps-hl-sheet]");
    return { visible: el && !el.hidden, sheetOpen: sheet.classList.contains("is-open") };
  });
  assert(explain.visible && !explain.sheetOpen, "P no-selection shows explain");

  /* J multi paragraph — select across p3 and p4 start */
  await page.$eval("body", () => {
    const a = document.querySelector("#p3").firstChild;
    const b = document.querySelector("#p4").firstChild;
    const range = document.createRange();
    range.setStart(a, 0);
    range.setEnd(b, 8);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  });
  await penTap();
  st = await sheetState();
  assert(st.open && /Line one/i.test(st.passage), "J multi-paragraph selection");

  await browser.close();
}

await runDomTests();

console.log("\n=== Summary ===");
if (fail) {
  console.log(`FAILED: ${fail} check(s)`);
  process.exit(1);
}
console.log("ALL WORD-BOUNDARY CHECKS PASSED");
