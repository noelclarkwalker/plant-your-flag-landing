/**
 * P.S. reader highlights — Phase 1 pilot (Open Journal).
 * Selection → Your P.S. popover → pending moderated submit.
 */
(function () {
  "use strict";

  var config = window.noelclarkV1PsHighlights;

  if (!config || !config.submitUrl || !config.nonce || !config.pageId) {
    return;
  }

  var root = document.querySelector("[data-ps-highlights-root]");

  if (!root) {
    return;
  }

  var PASSAGE_MAX = config.passageMax || 800;
  var NOTE_MAX = config.noteMax || 500;
  var CONTEXT_MAX = config.contextMax || 48;

  var popover = null;
  var passageEl = null;
  var noteEl = null;
  var statusEl = null;
  var submitBtn = null;
  var honeypotEl = null;
  var pendingSelection = null;
  var openTimer = null;
  var submitting = false;

  function clampText(value, max) {
    var text = String(value || "").replace(/\s+/g, " ").trim();

    if (!text) {
      return "";
    }

    return text.length > max ? text.slice(0, max) : text;
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

    var rect = range.getBoundingClientRect();

    return {
      passage: passage,
      prefix: prefix,
      suffix: suffix,
      rect: rect,
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

    popover.innerHTML =
      '<p class="ps-highlight-popover__title">Your P.S.</p>' +
      '<blockquote class="ps-highlight-popover__passage" data-ps-hl-passage></blockquote>' +
      '<p class="ps-highlight-popover__instruction">Share a thought, a question, or simply save this highlight.</p>' +
      '<label class="ps-highlight-popover__label" for="ps-highlight-note">Optional note</label>' +
      '<textarea id="ps-highlight-note" class="ps-highlight-popover__note" data-ps-hl-note maxlength="' +
      String(NOTE_MAX) +
      '" rows="3" placeholder="Optional note or question"></textarea>' +
      '<div class="ps-highlight-popover__honeypot" aria-hidden="true">' +
      '<label>Company<input type="text" name="company" data-ps-hl-honey tabindex="-1" autocomplete="off"></label>' +
      "</div>" +
      '<div class="ps-highlight-popover__actions">' +
      '<button type="button" class="ps-highlight-popover__submit" data-ps-hl-submit>Save highlight</button>' +
      '<button type="button" class="ps-highlight-popover__cancel" data-ps-hl-cancel>Cancel</button>' +
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

    var scrollX = window.scrollX || window.pageXOffset || 0;
    var scrollY = window.scrollY || window.pageYOffset || 0;
    var gap = 10;
    var top = scrollY + rect.bottom + gap;
    var left = scrollX + rect.left;

    popover.hidden = false;

    var width = popover.offsetWidth || 320;
    var height = popover.offsetHeight || 220;
    var maxLeft = scrollX + document.documentElement.clientWidth - width - 12;
    var maxTop = scrollY + document.documentElement.clientHeight - height - 12;

    if (left > maxLeft) {
      left = Math.max(scrollX + 12, maxLeft);
    }

    if (left < scrollX + 12) {
      left = scrollX + 12;
    }

    if (top > maxTop) {
      top = scrollY + rect.top - height - gap;
    }

    if (top < scrollY + 12) {
      top = scrollY + 12;
    }

    popover.style.top = Math.round(top) + "px";
    popover.style.left = Math.round(left) + "px";
  }

  function openPopover(selection) {
    ensurePopover();
    pendingSelection = selection;
    passageEl.textContent = selection.passage;
    noteEl.value = "";
    if (honeypotEl) {
      honeypotEl.value = "";
    }
    setStatus("", "");
    submitBtn.disabled = false;
    submitBtn.textContent = "Save highlight";
    positionPopover(selection.rect);

    window.setTimeout(function () {
      if (noteEl) {
        noteEl.focus();
      }
    }, 10);
  }

  function closePopover() {
    if (!popover) {
      return;
    }

    popover.hidden = true;
    pendingSelection = null;
    setStatus("", "");
  }

  function clearWindowSelection() {
    var sel = window.getSelection();

    if (sel && sel.removeAllRanges) {
      sel.removeAllRanges();
    }
  }

  function maybeOpenFromSelection() {
    var selection = readSelection();

    if (!selection) {
      return;
    }

    openPopover(selection);
  }

  function scheduleOpen() {
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
    submitBtn.textContent = "Saving…";
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
          submitBtn.textContent = "Saved";
          clearWindowSelection();
          window.setTimeout(closePopover, 1200);
          return;
        }

        submitBtn.disabled = false;
        submitBtn.textContent = "Save highlight";

        var code = result.data && result.data.code ? result.data.code : "";

        if (code === "rate_limited") {
          setStatus("Please wait before saving another highlight.", "error");
          return;
        }

        if (code === "nonce_error" || code === "forbidden") {
          setStatus("Please refresh the page and try again.", "error");
          return;
        }

        setStatus("Could not save right now. Please try again.", "error");
      })
      .catch(function () {
        submitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = "Save highlight";
        setStatus("Could not save right now. Please try again.", "error");
      });
  }

  document.addEventListener("mouseup", function (event) {
    if (popover && !popover.hidden && popover.contains(event.target)) {
      return;
    }

    scheduleOpen();
  });

  document.addEventListener(
    "touchend",
    function (event) {
      if (popover && !popover.hidden && popover.contains(event.target)) {
        return;
      }

      scheduleOpen();
    },
    { passive: true }
  );

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

    closePopover();
  });
})();
