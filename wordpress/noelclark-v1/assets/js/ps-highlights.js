/**
 * P.S. reader highlights — Writing UX corrections (Open Journal).
 * Highlighter OFF by default. Selection → Your P.S. only when ON.
 * focus({ preventScroll: true }) avoids jumping to the notes board.
 * Submits to existing moderated REST backend. No in-essay note insertion.
 */
(function () {
  "use strict";

  var config = window.noelclarkV1PsHighlights;

  if (!config || !config.submitUrl || !config.nonce || !config.pageId) {
    return;
  }

  var root = document.querySelector("[data-ps-highlights-root]");
  var shell = document.querySelector(".ps-writing") || document.body;
  var toggle = document.querySelector("[data-ps-hl-toggle]");
  var stateEl = document.querySelector("[data-ps-hl-state]");
  var leaveCta = document.querySelector("[data-ps-notes-empty] .ps-writing__leave");

  if (!root) {
    return;
  }

  var PASSAGE_MAX = config.passageMax || 800;
  var NOTE_MAX = config.noteMax || 500;
  var CONTEXT_MAX = config.contextMax || 48;
  var HighlightAPI = window.CSS && window.CSS.highlights ? window.CSS.highlights : null;
  var HighlightCtor = typeof window.Highlight === "function" ? window.Highlight : null;

  var highlighterOn = false;
  var popover = null;
  var passageEl = null;
  var noteEl = null;
  var statusEl = null;
  var submitBtn = null;
  var honeypotEl = null;
  var pendingSelection = null;
  var pendingRange = null;
  var openTimer = null;
  var selectionTimer = null;
  var submitting = false;
  var coarsePointer = false;

  try {
    coarsePointer = window.matchMedia("(pointer: coarse)").matches;
  } catch (err) {
    coarsePointer = false;
  }

  function clampText(value, max) {
    var text = String(value || "").replace(/\s+/g, " ").trim();

    if (!text) {
      return "";
    }

    return text.length > max ? text.slice(0, max) : text;
  }

  function focusNoScroll(el) {
    if (!el || typeof el.focus !== "function") {
      return;
    }

    try {
      el.focus({ preventScroll: true });
    } catch (err) {
      el.focus();
    }
  }

  function useSheetLayout() {
    return coarsePointer || window.innerWidth <= 600;
  }

  function setToggleUi() {
    if (toggle) {
      toggle.setAttribute("aria-pressed", highlighterOn ? "true" : "false");
      toggle.setAttribute(
        "aria-label",
        highlighterOn
          ? "Highlighter on. Select a passage to leave a P.S. Activate to turn off."
          : "Highlighter off. Turn on to highlight a passage and leave a P.S."
      );
    }

    if (stateEl) {
      stateEl.textContent = highlighterOn ? "On" : "Off";
    }

    if (shell && shell.classList) {
      shell.classList.toggle("is-ps-highlighter-on", highlighterOn);
    }
  }

  function clearPendingHighlight() {
    if (HighlightAPI) {
      HighlightAPI.delete("ps-hl-pend");
    }

    pendingRange = null;
  }

  function applyPendingHighlight(range) {
    clearPendingHighlight();

    if (!range || !HighlightAPI || !HighlightCtor) {
      return;
    }

    try {
      HighlightAPI.set("ps-hl-pend", new HighlightCtor(range));
    } catch (err) {
      /* Safari / partial support — selection styling still applies when ON. */
    }
  }

  function isExcludedNode(node) {
    if (!node) {
      return true;
    }

    var el = node.nodeType === 1 ? node : node.parentElement;

    if (!el) {
      return true;
    }

    if (!root.contains(el)) {
      return true;
    }

    return Boolean(
      el.closest(
        "a, button, input, textarea, select, label, summary, [data-thread-ego], .ps-thread-ego, .ps-highlight-popover"
      )
    );
  }

  function rangeIntersectsRoot(range) {
    if (!range) {
      return false;
    }

    return (
      root.contains(range.commonAncestorContainer) ||
      root.contains(range.startContainer) ||
      root.contains(range.endContainer)
    );
  }

  function contextAround(fullText, start, end, max) {
    var before = fullText.slice(Math.max(0, start - max), start).trim();
    var after = fullText.slice(end, Math.min(fullText.length, end + max)).trim();

    return {
      prefix: before.slice(-max),
      suffix: after.slice(0, max),
    };
  }

  function readSelection() {
    var sel = window.getSelection();

    if (!sel || sel.isCollapsed || sel.rangeCount < 1) {
      return null;
    }

    var range = sel.getRangeAt(0);

    if (!rangeIntersectsRoot(range)) {
      return null;
    }

    if (isExcludedNode(range.startContainer) || isExcludedNode(range.endContainer)) {
      return null;
    }

    var passage = clampText(range.toString(), PASSAGE_MAX);

    if (passage.length < 3) {
      return null;
    }

    var rootText = root.textContent || "";
    var index = rootText.indexOf(passage);
    var prefix = "";
    var suffix = "";

    if (index !== -1) {
      var ctx = contextAround(rootText, index, index + passage.length, CONTEXT_MAX);
      prefix = ctx.prefix;
      suffix = ctx.suffix;
    }

    var cloned = range.cloneRange();
    var rect = cloned.getBoundingClientRect();

    return {
      passage: passage,
      prefix: prefix,
      suffix: suffix,
      rect: rect,
      range: cloned,
    };
  }

  function ensurePopover() {
    if (popover) {
      return;
    }

    popover = document.createElement("div");
    popover.className = "ps-highlight-popover";
    popover.hidden = true;
    popover.setAttribute("role", "dialog");
    popover.setAttribute("aria-label", "Your P.S.");
    popover.setAttribute("aria-modal", "false");

    popover.innerHTML =
      '<button type="button" class="ps-highlight-popover__close" data-ps-hl-cancel aria-label="Close">×</button>' +
      '<h3 class="ps-highlight-popover__title">Your P.S.</h3>' +
      '<blockquote class="ps-highlight-popover__passage" data-ps-hl-passage></blockquote>' +
      '<p class="ps-highlight-popover__instruction">Share a thought, a question, or simply save this highlight.</p>' +
      '<label class="ps-highlight-popover__label" for="ps-highlight-note">Add a note (optional)</label>' +
      '<textarea id="ps-highlight-note" class="ps-highlight-popover__note" data-ps-hl-note maxlength="' +
      String(NOTE_MAX) +
      '" rows="3"></textarea>' +
      '<div class="ps-highlight-popover__honeypot" aria-hidden="true">' +
      '<label>Company<input type="text" name="company" data-ps-hl-honey tabindex="-1" autocomplete="off"></label>' +
      "</div>" +
      '<div class="ps-highlight-popover__actions">' +
      '<button type="button" class="ps-highlight-popover__submit" data-ps-hl-submit>POST YOUR P.S.</button>' +
      "</div>" +
      '<p class="ps-highlight-popover__status" data-ps-hl-status hidden></p>';

    document.body.appendChild(popover);

    passageEl = popover.querySelector("[data-ps-hl-passage]");
    noteEl = popover.querySelector("[data-ps-hl-note]");
    statusEl = popover.querySelector("[data-ps-hl-status]");
    submitBtn = popover.querySelector("[data-ps-hl-submit]");
    honeypotEl = popover.querySelector("[data-ps-hl-honey]");

    submitBtn.addEventListener("click", onSubmit);
    popover.querySelector("[data-ps-hl-cancel]").addEventListener("click", closePopover);
  }

  function setStatus(message, tone) {
    if (!statusEl) {
      return;
    }

    if (!message) {
      statusEl.hidden = true;
      statusEl.textContent = "";
      statusEl.removeAttribute("data-tone");
      return;
    }

    statusEl.hidden = false;
    statusEl.textContent = message;
    statusEl.setAttribute("data-tone", tone || "ok");
  }

  function positionPopover(rect) {
    if (!popover || !rect) {
      return;
    }

    var scrollYBefore = window.scrollY || window.pageYOffset || 0;

    popover.hidden = false;

    if (useSheetLayout()) {
      popover.classList.add("is-ps-hl-sheet");
      popover.style.top = "";
      popover.style.left = "";
      popover.style.right = "";
      popover.style.bottom = "";

      if ((window.scrollY || window.pageYOffset || 0) !== scrollYBefore) {
        window.scrollTo(0, scrollYBefore);
      }

      return;
    }

    popover.classList.remove("is-ps-hl-sheet");

    var gap = 12;
    var width = popover.offsetWidth || 320;
    var height = popover.offsetHeight || 240;
    var vw = window.innerWidth;
    var vh = window.innerHeight;

    /* Prefer to the right of the selection; fall back below / above. */
    var left = rect.right + gap;
    var top = rect.top;

    if (left + width > vw - 12) {
      left = rect.left - width - gap;
    }

    if (left < 12) {
      left = Math.max(12, Math.min(rect.left, vw - width - 12));
      top = rect.bottom + gap;

      if (top + height > vh - 12) {
        top = rect.top - height - gap;
      }
    }

    top = Math.max(12, Math.min(top, vh - height - 12));
    left = Math.max(12, Math.min(left, vw - width - 12));

    popover.style.top = Math.round(top) + "px";
    popover.style.left = Math.round(left) + "px";
    popover.style.right = "auto";
    popover.style.bottom = "auto";

    if ((window.scrollY || window.pageYOffset || 0) !== scrollYBefore) {
      window.scrollTo(0, scrollYBefore);
    }
  }

  function openPopover(selection) {
    var scrollYBefore = window.scrollY || window.pageYOffset || 0;

    ensurePopover();
    pendingSelection = selection;
    pendingRange = selection.range || null;
    applyPendingHighlight(pendingRange);

    if (HighlightAPI && pendingRange) {
      clearWindowSelection();
    }

    passageEl.textContent = selection.passage;
    noteEl.value = "";
    if (honeypotEl) {
      honeypotEl.value = "";
    }
    setStatus("", "");
    submitBtn.disabled = false;
    submitBtn.textContent = "POST YOUR P.S.";
    positionPopover(selection.rect);

    window.setTimeout(function () {
      focusNoScroll(noteEl);

      if ((window.scrollY || window.pageYOffset || 0) !== scrollYBefore) {
        window.scrollTo(0, scrollYBefore);
      }
    }, 10);
  }

  function closePopover() {
    if (!popover) {
      clearPendingHighlight();
      pendingSelection = null;
      return;
    }

    popover.hidden = true;
    pendingSelection = null;
    clearPendingHighlight();
    setStatus("", "");
  }

  function clearWindowSelection() {
    var sel = window.getSelection();

    if (sel && sel.removeAllRanges) {
      sel.removeAllRanges();
    }
  }

  function setHighlighterOn(next) {
    highlighterOn = Boolean(next);
    setToggleUi();

    if (!highlighterOn) {
      closePopover();
      clearWindowSelection();
    }
  }

  function maybeOpenFromSelection() {
    if (!highlighterOn) {
      return;
    }

    var selection = readSelection();

    if (!selection) {
      return;
    }

    openPopover(selection);
  }

  function scheduleOpen() {
    if (!highlighterOn) {
      return;
    }

    if (openTimer) {
      window.clearTimeout(openTimer);
    }

    openTimer = window.setTimeout(function () {
      openTimer = null;
      maybeOpenFromSelection();
    }, 180);
  }

  function onSubmit() {
    if (submitting || !pendingSelection) {
      return;
    }

    var payload = {
      nonce: config.nonce,
      pageId: config.pageId,
      passage: pendingSelection.passage,
      prefix: pendingSelection.prefix || "",
      suffix: pendingSelection.suffix || "",
      note: clampText(noteEl && noteEl.value, NOTE_MAX),
      company: honeypotEl ? String(honeypotEl.value || "") : "",
    };

    submitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = "POSTING…";
    setStatus("", "");

    fetch(config.submitUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data || {} };
        });
      })
      .then(function (result) {
        submitting = false;

        if (result.status === 202 || (result.data && result.data.code === "pending")) {
          setStatus("Saved for review. Thank you.", "ok");
          submitBtn.textContent = "POSTED";
          clearWindowSelection();
          clearPendingHighlight();
          window.setTimeout(closePopover, 1200);
          return;
        }

        submitBtn.disabled = false;
        submitBtn.textContent = "POST YOUR P.S.";

        var code = result.data && result.data.code ? result.data.code : "";

        if (code === "rate_limited") {
          setStatus("Please wait before saving another highlight.", "error");
          return;
        }

        if (code === "nonce_error" || code === "forbidden") {
          setStatus("Please refresh the page and try again.", "error");
          return;
        }

        if (code === "passage_mismatch") {
          setStatus("That selection could not be verified. Please try again.", "error");
          return;
        }

        setStatus("Could not save right now. Please try again.", "error");
      })
      .catch(function () {
        submitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = "POST YOUR P.S.";
        setStatus("Could not save right now. Please try again.", "error");
      });
  }

  function armHighlighterFromCta() {
    var scrollYBefore = window.scrollY || window.pageYOffset || 0;
    setHighlighterOn(true);
    focusNoScroll(toggle);

    if ((window.scrollY || window.pageYOffset || 0) !== scrollYBefore) {
      window.scrollTo(0, scrollYBefore);
    }
  }

  if (toggle) {
    toggle.addEventListener("click", function () {
      setHighlighterOn(!highlighterOn);
    });
  }

  if (leaveCta) {
    leaveCta.addEventListener("click", function (event) {
      event.preventDefault();
      armHighlighterFromCta();
    });

    leaveCta.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        armHighlighterFromCta();
      }
    });
  }

  document.addEventListener("mouseup", function (event) {
    if (!highlighterOn) {
      return;
    }

    if (popover && !popover.hidden && popover.contains(event.target)) {
      return;
    }

    scheduleOpen();
  });

  document.addEventListener(
    "touchend",
    function (event) {
      if (!highlighterOn) {
        return;
      }

      if (popover && !popover.hidden && popover.contains(event.target)) {
        return;
      }

      scheduleOpen();
    },
    { passive: true }
  );

  if (coarsePointer) {
    document.addEventListener("selectionchange", function () {
      if (!highlighterOn) {
        return;
      }

      if (selectionTimer) {
        window.clearTimeout(selectionTimer);
      }

      selectionTimer = window.setTimeout(function () {
        selectionTimer = null;
        maybeOpenFromSelection();
      }, 400);
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closePopover();
    }
  });

  document.addEventListener("mousedown", function (event) {
    if (!popover || popover.hidden) {
      return;
    }

    if (popover.contains(event.target)) {
      return;
    }

    if (toggle && toggle.contains(event.target)) {
      return;
    }

    closePopover();
  });

  setToggleUi();
})();
