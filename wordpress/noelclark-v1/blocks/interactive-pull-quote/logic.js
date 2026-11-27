/**
 * Interactive Pull Quotes — Phase 1 pure helpers (no WordPress runtime).
 * Used by the editor script and by Node offline tests.
 */
(function (root, factory) {
  if (typeof module === "object" && module.exports) {
    module.exports = factory();
  } else {
    root.NoelClarkPullQuotesLogic = factory();
  }
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  "use strict";

  /** Finished-product effect library (Phase 1 stores shape only). */
  var EFFECT_TYPES = [
    "highlight",
    "underline",
    "circle",
    "margin_bracket",
    "strike_revise",
    "expand",
  ];

  function createQuoteId() {
    var rand = Math.random().toString(36).slice(2, 10);
    return "pq-" + Date.now().toString(36) + "-" + rand;
  }

  function isKnownEffect(effect) {
    return EFFECT_TYPES.indexOf(String(effect || "")) !== -1;
  }

  /**
   * Normalize a mark object for storage. Does not animate.
   */
  function normalizeMark(raw) {
    var mark = raw && typeof raw === "object" ? raw : {};
    var effect = isKnownEffect(mark.effect) ? mark.effect : "none";
    return {
      id: String(mark.id || createQuoteId()),
      effect: effect,
      text: String(mark.text || ""),
      replacement: String(mark.replacement || ""),
      expandContent: String(mark.expandContent || ""),
      expandInsertOffset:
        typeof mark.expandInsertOffset === "number" && isFinite(mark.expandInsertOffset)
          ? mark.expandInsertOffset
          : null,
      start: typeof mark.start === "number" && isFinite(mark.start) ? mark.start : null,
      end: typeof mark.end === "number" && isFinite(mark.end) ? mark.end : null,
    };
  }

  function normalizeHtml(html) {
    return String(html || "")
      .replace(/\u00a0/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function hashNeighbors(beforeHtml, afterHtml) {
    var payload = normalizeHtml(beforeHtml) + "||" + normalizeHtml(afterHtml);
    var hash = 0;
    for (var i = 0; i < payload.length; i += 1) {
      hash = (hash << 5) - hash + payload.charCodeAt(i);
      hash |= 0;
    }
    return "h" + String(hash);
  }

  function stripTags(html) {
    return String(html || "")
      .replace(/<[^>]+>/g, "")
      .replace(/\u00a0/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function excerptText(html, maxLen) {
    var text = stripTags(html);
    var limit = typeof maxLen === "number" ? maxLen : 110;
    if (text.length <= limit) {
      return text;
    }
    return text.slice(0, limit - 1).replace(/\s+\S*$/, "") + "…";
  }

  /**
   * Decide whether a selection can convert immediately.
   * @param {object} input
   * @returns {{ok:boolean, reason:string, needsConfirm:boolean}}
   */
  function assessSelection(input) {
    var blockName = input && input.blockName;
    var start = input && input.startOffset;
    var end = input && input.endOffset;
    var crossBlock = !!(input && input.crossBlock);
    var partialLink = !!(input && input.partialLink);
    var empty = !!(input && input.empty);

    if (crossBlock) {
      return {
        ok: false,
        needsConfirm: false,
        reason:
          "Select text within one paragraph. Selections that cross paragraphs are not converted automatically.",
      };
    }

    if (blockName !== "core/paragraph") {
      return {
        ok: false,
        needsConfirm: false,
        reason: "Select text inside a paragraph, then choose Add as pull quote.",
      };
    }

    if (
      typeof start !== "number" ||
      typeof end !== "number" ||
      !isFinite(start) ||
      !isFinite(end) ||
      end <= start
    ) {
      return {
        ok: false,
        needsConfirm: false,
        reason: "Select the passage you want to turn into a pull quote.",
      };
    }

    if (empty) {
      return {
        ok: false,
        needsConfirm: false,
        reason: "Select the passage you want to turn into a pull quote.",
      };
    }

    if (partialLink) {
      return {
        ok: false,
        needsConfirm: true,
        reason:
          "That selection cuts through a link. Adjust the selection so the whole link is included or excluded, then try again.",
      };
    }

    return { ok: true, needsConfirm: false, reason: "" };
  }

  /**
   * Plan restore behavior from stored neighbor fingerprints.
   * @returns {{mode:string, message:string}}
   */
  function planRestore(input) {
    var storedHash = input && input.neighborHash;
    var beforeHtml = (input && input.beforeHtml) || "";
    var afterHtml = (input && input.afterHtml) || "";
    var currentBefore = (input && input.currentBeforeHtml) || "";
    var currentAfter = (input && input.currentAfterHtml) || "";
    var neighborsPresent = !!(input && input.neighborsPresent);
    var liveHash = hashNeighbors(currentBefore, currentAfter);
    var hadNeighbors = beforeHtml !== "" || afterHtml !== "";

    if (!hadNeighbors) {
      return {
        mode: "paragraph",
        message: "These words will become a regular paragraph here.",
      };
    }

    if (!neighborsPresent) {
      return {
        mode: "paragraph",
        message:
          "The neighboring paragraphs changed. These words will become a regular paragraph here instead of merging automatically.",
      };
    }

    if (storedHash && storedHash === liveHash) {
      return {
        mode: "merge",
        message: "These words will return to the surrounding paragraph.",
      };
    }

    return {
      mode: "paragraph",
      message:
        "The neighboring paragraphs were edited after this quote was created. To avoid mixing text in the wrong place, these words will become a regular paragraph here.",
    };
  }

  /**
   * Detect whether [start,end) cuts through a link using RichText-like formats.
   * formats[i] is an array of format objects with type === 'core/link'.
   */
  function selectionCutsPartialLink(formats, start, end) {
    if (!Array.isArray(formats) || typeof start !== "number" || typeof end !== "number") {
      return false;
    }

    function hasLinkAt(index) {
      var list = formats[index] || [];
      for (var i = 0; i < list.length; i += 1) {
        if (list[i] && list[i].type === "core/link") {
          return true;
        }
      }
      return false;
    }

    var inside = false;
    var outside = false;
    var i;

    for (i = start; i < end; i += 1) {
      if (hasLinkAt(i)) {
        inside = true;
      }
    }

    if (!inside) {
      return false;
    }

    if (start > 0 && hasLinkAt(start - 1)) {
      outside = true;
    }
    if (end < formats.length && hasLinkAt(end)) {
      outside = true;
    }

    return outside;
  }

  return {
    EFFECT_TYPES: EFFECT_TYPES,
    createQuoteId: createQuoteId,
    isKnownEffect: isKnownEffect,
    normalizeMark: normalizeMark,
    normalizeHtml: normalizeHtml,
    hashNeighbors: hashNeighbors,
    stripTags: stripTags,
    excerptText: excerptText,
    assessSelection: assessSelection,
    planRestore: planRestore,
    selectionCutsPartialLink: selectionCutsPartialLink,
  };
});
