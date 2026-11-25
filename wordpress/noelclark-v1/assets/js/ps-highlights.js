/**
 * P.S. single highlighter + chronological public feed (Open Journal pilot).
 * Variant B: selectionchange candidate (40s), attach only on intentional pen tap.
 *
 * Config contract: must match wp_localize_script handle object name
 * `noelclarkV1PsHighlights` (see inc/enqueue.php).
 */
(function () {
  "use strict";

  var config = window.noelclarkV1PsHighlights;
  if (!config || !config.submitUrl || !config.nonce || !config.pageId) {
    return;
  }

  var STALE_MS = (config.candidateSeconds || 40) * 1000;
  var PASSAGE_MAX = config.passageMax || 2000;
  var NOTE_MAX = config.noteMax || 1000;
  var CONTEXT_MAX = config.contextMax || 48;
  var PEN_COLOR = config.penColor || "#A21EFF";
  var LIST_PAGE_SIZE = config.listPageSize || 100;

  var scopes = document.querySelectorAll("[data-ps-hl-scope]");
  var penBtn = document.querySelector("[data-ps-pen-launch]");
  var sheet = document.querySelector("[data-ps-hl-sheet]");
  var closeBtn = document.querySelector("[data-ps-hl-close]");
  var quoteWrap = document.querySelector("[data-ps-quote-wrap]");
  var passageEl = document.querySelector("[data-ps-hl-passage]");
  var clearQuoteBtn = document.querySelector("[data-ps-quote-clear]");
  var hintEl = document.querySelector("[data-ps-hl-hint]");
  var statusEl = document.querySelector("[data-ps-hl-status]");
  var submitBtn = document.querySelector("[data-ps-hl-submit]");
  var honeyEl = document.querySelector("[data-ps-hl-honey]");
  var notesEmpty = document.querySelector("[data-ps-notes-empty]");
  var notesList = document.querySelector("[data-ps-notes-list]");
  var loadMoreBtn = document.querySelector("[data-ps-notes-more]");
  var commentForm = document.querySelector("[data-ps-comment-form]");
  var commentBody = document.querySelector("[data-ps-comment-body]");
  var commentName = document.querySelector("[data-ps-comment-name]");
  var commentStatus = document.querySelector("[data-ps-comment-status]");
  var commentHoney = document.querySelector("[data-ps-comment-honey]");
  var commentSubmit = document.querySelector("[data-ps-comment-submit]");

  if (!scopes.length || !penBtn || !sheet || !submitBtn) {
    return;
  }

  var candidate = null;
  var pending = null;
  var submitting = false;
  var privateMarks = [];
  var listCursor = null;
  var listHasMore = false;
  var listLoading = false;
  var focusBeforeSheet = null;

  function now() {
    return Date.now();
  }

  /** Per intentional submission attempt — reused only for retries of that attempt. */
  function newClientRequestId() {
    try {
      if (window.crypto && typeof window.crypto.randomUUID === "function") {
        return window.crypto.randomUUID();
      }
    } catch (err) {}
    return (
      "ps_" +
      Date.now().toString(36) +
      "_" +
      Math.random().toString(36).slice(2, 12) +
      Math.random().toString(36).slice(2, 8)
    );
  }

  function cloneLiveRange(range) {
    if (!range || typeof range.cloneRange !== "function") {
      return null;
    }
    try {
      return range.cloneRange();
    } catch (err) {
      return null;
    }
  }

  /** True when a preserved Range still points at in-document, in-scope nodes. */
  function rangeStillValid(range) {
    if (!range) {
      return false;
    }
    try {
      if (range.collapsed) {
        return false;
      }
      var root = range.commonAncestorContainer;
      if (!root || (root.nodeType === 1 ? !root.isConnected : !root.parentElement || !root.parentElement.isConnected)) {
        return false;
      }
      if (!nodeInScopes(range.startContainer) || !nodeInScopes(range.endContainer)) {
        return false;
      }
      return true;
    } catch (err) {
      return false;
    }
  }

  /** Passage / highlight matching: collapse all whitespace. */
  function normalizePassage(text) {
    return String(text || "").replace(/\s+/g, " ").trim();
  }

  /**
   * Comment body: preserve newlines / paragraphs; collapse horizontal
   * whitespace only. Must NOT use normalizePassage.
   */
  function normalizeComment(text) {
    var raw = String(text || "").replace(/\r\n/g, "\n").replace(/\r/g, "\n");
    raw = raw.replace(/[^\S\n]+/g, " ");
    raw = raw.replace(/\n{3,}/g, "\n\n");
    return raw.trim();
  }

  function setStatus(el, msg, tone) {
    if (!el) {
      return;
    }
    if (!msg) {
      el.hidden = true;
      el.textContent = "";
      el.removeAttribute("data-tone");
      return;
    }
    el.hidden = false;
    el.textContent = msg;
    if (tone) {
      el.setAttribute("data-tone", tone);
    } else {
      el.removeAttribute("data-tone");
    }
  }

  function nodeInScopes(node) {
    if (!node) {
      return false;
    }
    var el = node.nodeType === 1 ? node : node.parentElement;
    if (!el) {
      return false;
    }
    for (var i = 0; i < scopes.length; i++) {
      if (scopes[i].contains(el)) {
        return true;
      }
    }
    return false;
  }

  /** Both endpoints and the live range must stay inside essay/title scopes. */
  function selectionInsideScopes(sel) {
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
      return false;
    }
    try {
      if (!nodeInScopes(sel.anchorNode) || !nodeInScopes(sel.focusNode)) {
        return false;
      }
      var range = sel.getRangeAt(0);
      if (!range) {
        return false;
      }
      if (!nodeInScopes(range.commonAncestorContainer)) {
        return false;
      }
      if (!nodeInScopes(range.startContainer) || !nodeInScopes(range.endContainer)) {
        return false;
      }
      return true;
    } catch (err) {
      return false;
    }
  }

  function orderedTextNodes() {
    var nodes = [];
    for (var s = 0; s < scopes.length; s++) {
      var walker = document.createTreeWalker(scopes[s], NodeFilter.SHOW_TEXT);
      var node;
      while ((node = walker.nextNode())) {
        nodes.push(node);
      }
    }
    return nodes;
  }

  /**
   * Absolute character offsets of a Range within the concatenated,
   * whitespace-normalized essay text built from scoped text nodes.
   * Returns null if the range cannot be mapped.
   */
  function offsetsForRange(range) {
    if (!range) {
      return null;
    }
    var nodes = orderedTextNodes();
    var norm = "";
    var start = -1;
    var end = -1;
    var i;
    var node;
    var text;
    var piece;
    var local;

    function appendNormalized(chunk) {
      var cleaned = String(chunk || "").replace(/\s+/g, " ");
      if (!cleaned) {
        return;
      }
      if (norm.length && /\s$/.test(norm) && /^\s/.test(cleaned)) {
        cleaned = cleaned.replace(/^\s+/, "");
      } else if (norm.length && !/\s$/.test(norm) && /^\s/.test(cleaned)) {
        /* keep single space from cleaned */
      } else if (norm.length && /\S$/.test(norm) && /^\S/.test(cleaned)) {
        /* adjacent words from separate nodes — insert space if original had break */
      }
      /* Prefer joining with the same rules as normalizePassage on full innerText:
         build raw then normalize once at end for membership; for offsets, map via
         running normalize of prefixes. */
      norm += cleaned;
      norm = norm.replace(/\s+/g, " ");
    }

    /* Simpler reliable approach: build full raw with node boundaries, then
       locate range by reconstructing with markers. */
    var rawParts = [];
    var raw = "";
    var startRaw = -1;
    var endRaw = -1;
    for (i = 0; i < nodes.length; i++) {
      node = nodes[i];
      text = node.nodeValue || "";
      if (node === range.startContainer) {
        startRaw = raw.length + range.startOffset;
      }
      if (node === range.endContainer) {
        endRaw = raw.length + range.endOffset;
      }
      rawParts.push(text);
      raw += text;
      if (i < nodes.length - 1) {
        raw += " ";
      }
    }
    if (startRaw < 0 || endRaw < 0 || endRaw < startRaw) {
      return null;
    }

    var beforeRaw = raw.slice(0, startRaw);
    var passageRaw = raw.slice(startRaw, endRaw);
    var afterRaw = raw.slice(endRaw);
    var passage = normalizePassage(passageRaw);
    if (!passage) {
      return null;
    }

    var prefixFull = normalizePassage(beforeRaw);
    var suffixFull = normalizePassage(afterRaw);
    var prefix = prefixFull.slice(Math.max(0, prefixFull.length - CONTEXT_MAX));
    var suffix = suffixFull.slice(0, CONTEXT_MAX);

    return {
      passage: passage,
      prefix: prefix,
      suffix: suffix
    };
  }

  function readLiveSelection() {
    var sel = window.getSelection && window.getSelection();
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) {
      return null;
    }
    if (!selectionInsideScopes(sel)) {
      return null;
    }
    var range;
    try {
      range = sel.getRangeAt(0);
    } catch (err) {
      return null;
    }
    var mapped = offsetsForRange(range);
    if (!mapped || !mapped.passage) {
      /* Fallback: normalized toString only if still in-scope (no forged context). */
      var raw = normalizePassage(sel.toString());
      if (raw.length < 3 || raw.length > PASSAGE_MAX) {
        return null;
      }
      return {
        passage: raw,
        prefix: "",
        suffix: "",
        color: PEN_COLOR,
        at: now(),
        range: cloneLiveRange(range)
      };
    }
    if (mapped.passage.length < 3 || mapped.passage.length > PASSAGE_MAX) {
      return null;
    }
    return {
      passage: mapped.passage,
      prefix: mapped.prefix || "",
      suffix: mapped.suffix || "",
      color: PEN_COLOR,
      at: now(),
      range: cloneLiveRange(range)
    };
  }

  function rememberCandidate() {
    var live = readLiveSelection();
    if (!live) {
      return;
    }
    candidate = live;
  }

  function candidateFresh() {
    return candidate && candidate.passage && now() - candidate.at <= STALE_MS;
  }

  function sheetFocusables() {
    return sheet.querySelectorAll(
      'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
  }

  function openSheet() {
    focusBeforeSheet = document.activeElement;
    sheet.classList.add("is-open");
    sheet.setAttribute("aria-hidden", "false");
    sheet.setAttribute("aria-modal", "true");
    document.body.classList.add("is-ps-hl-open");
    window.setTimeout(function () {
      var target = submitBtn && !submitBtn.disabled ? submitBtn : closeBtn;
      if (target && typeof target.focus === "function") {
        try {
          target.focus();
        } catch (err) {}
      }
    }, 10);
  }

  function closeSheet() {
    sheet.classList.remove("is-open");
    sheet.setAttribute("aria-hidden", "true");
    sheet.removeAttribute("aria-modal");
    document.body.classList.remove("is-ps-hl-open");
    if (focusBeforeSheet && typeof focusBeforeSheet.focus === "function") {
      try {
        focusBeforeSheet.focus();
      } catch (err) {}
    }
    focusBeforeSheet = null;
  }

  function setPending(sel) {
    if (!sel || !sel.passage) {
      pending = null;
    } else {
      pending = {
        passage: sel.passage,
        prefix: sel.prefix || "",
        suffix: sel.suffix || "",
        color: sel.color || PEN_COLOR,
        range: sel.range || null,
        /* New intentional pending selection → new submission attempt id. */
        requestId: sel.requestId || newClientRequestId()
      };
    }
    if (!quoteWrap || !passageEl) {
      return;
    }
    if (!pending || !pending.passage) {
      quoteWrap.hidden = true;
      passageEl.textContent = "";
      if (hintEl) {
        hintEl.hidden = false;
        hintEl.textContent = "No quotation attached.";
      }
      submitBtn.disabled = true;
      return;
    }
    quoteWrap.hidden = false;
    passageEl.textContent = pending.passage;
    if (hintEl) {
      hintEl.hidden = true;
    }
    submitBtn.disabled = false;
  }

  /**
   * Apply a private mark using the preserved selected Range — never first indexOf.
   * Cross-node: wrap each intersecting text-node slice. Fail closed if range stale.
   */
  function markPrivateFromRange(range, expectedPassage) {
    if (!rangeStillValid(range)) {
      return false;
    }
    var normalized = normalizePassage(range.toString());
    if (
      expectedPassage &&
      normalized &&
      normalized !== expectedPassage &&
      normalized.indexOf(expectedPassage) === -1 &&
      expectedPassage.indexOf(normalized) === -1
    ) {
      /* DOM drifted — do not paint the wrong location. */
      return false;
    }

    var nodes = [];
    try {
      var root = range.commonAncestorContainer;
      if (root.nodeType === 3) {
        root = root.parentNode;
      }
      if (!root) {
        return false;
      }
      var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: function (node) {
          if (!nodeInScopes(node)) {
            return NodeFilter.FILTER_REJECT;
          }
          if (node.parentElement && node.parentElement.closest("mark.ps-private-hl")) {
            return NodeFilter.FILTER_REJECT;
          }
          try {
            if (!range.intersectsNode(node)) {
              return NodeFilter.FILTER_REJECT;
            }
          } catch (err) {
            return NodeFilter.FILTER_REJECT;
          }
          if (!(node.nodeValue || "").length) {
            return NodeFilter.FILTER_REJECT;
          }
          return NodeFilter.FILTER_ACCEPT;
        }
      });
      var n;
      while ((n = walker.nextNode())) {
        nodes.push(n);
      }
    } catch (err) {
      return false;
    }

    if (!nodes.length) {
      return false;
    }

    var marked = false;
    for (var i = 0; i < nodes.length; i++) {
      var node = nodes[i];
      var text = node.nodeValue || "";
      var start = node === range.startContainer ? range.startOffset : 0;
      var end = node === range.endContainer ? range.endOffset : text.length;
      if (end > text.length) {
        end = text.length;
      }
      if (start < 0) {
        start = 0;
      }
      if (end <= start) {
        continue;
      }
      try {
        var slice = document.createRange();
        slice.setStart(node, start);
        slice.setEnd(node, end);
        var mark = document.createElement("mark");
        mark.className = "ps-private-hl";
        slice.surroundContents(mark);
        marked = true;
      } catch (wrapErr) {
        /* Skip unsafe slice rather than corrupting markup. */
      }
    }

    if (marked && expectedPassage) {
      privateMarks.push(expectedPassage);
    }
    return marked;
  }

  function formatCount(n) {
    return n === 1 ? "Highlighted 1 time" : "Highlighted " + String(n) + " times";
  }

  function formatDate(iso) {
    if (!iso) {
      return "";
    }
    try {
      var d = new Date(iso);
      if (isNaN(d.getTime())) {
        return "";
      }
      return d.toLocaleDateString(undefined, {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    } catch (err) {
      return "";
    }
  }

  function createEntryEl(entry) {
    var li = document.createElement("li");
    var type = entry.type || (entry.passage && !entry.note ? "highlight" : "comment");

    if (entry.id) {
      li.setAttribute("data-ps-note-id", String(entry.id));
    }

    if (type === "highlight" || (entry.passage && !entry.note)) {
      li.className = "ps-writing__note ps-writing__note--highlight";
      if (entry.identity) {
        li.setAttribute("data-ps-identity", String(entry.identity));
      }
      var quote = document.createElement("blockquote");
      quote.className = "ps-writing__note-passage";
      quote.textContent = entry.passage || "";
      li.appendChild(quote);
      var count = document.createElement("p");
      count.className = "ps-writing__note-count";
      count.textContent = formatCount(entry.count || 1);
      li.appendChild(count);
      return li;
    }

    li.className = "ps-writing__note ps-writing__note--comment";
    var head = document.createElement("div");
    head.className = "ps-writing__note-head";
    var name = document.createElement("span");
    name.className = "ps-writing__note-name";
    name.textContent = entry.displayName || "Reader";
    var date = document.createElement("time");
    date.className = "ps-writing__note-date";
    date.textContent = formatDate(entry.date);
    head.appendChild(name);
    head.appendChild(date);
    li.appendChild(head);
    var body = document.createElement("p");
    body.className = "ps-writing__note-body";
    body.textContent = entry.note || "";
    /* Preserve author newlines visually */
    body.style.whiteSpace = "pre-wrap";
    li.appendChild(body);
    return li;
  }

  function updateLoadMoreUi() {
    if (!loadMoreBtn) {
      return;
    }
    if (listHasMore) {
      loadMoreBtn.hidden = false;
      loadMoreBtn.disabled = listLoading;
      loadMoreBtn.textContent = listLoading ? "Loading…" : "Load more responses";
    } else {
      loadMoreBtn.hidden = true;
    }
  }

  function appendEntries(entries, replace) {
    if (!notesList) {
      return;
    }
    if (replace) {
      notesList.textContent = "";
    }
    if (!entries || !entries.length) {
      if (replace || !notesList.children.length) {
        notesList.hidden = true;
        if (notesEmpty) {
          notesEmpty.hidden = false;
        }
      }
      updateLoadMoreUi();
      return;
    }
    if (notesEmpty) {
      notesEmpty.hidden = true;
    }
    notesList.hidden = false;
    for (var i = 0; i < entries.length; i++) {
      notesList.appendChild(createEntryEl(entries[i]));
    }
    updateLoadMoreUi();
  }

  function upsertHighlightInFeed(entry) {
    if (!notesList || !entry) {
      return;
    }
    if (notesEmpty) {
      notesEmpty.hidden = true;
    }
    notesList.hidden = false;
    var items = notesList.querySelectorAll(".ps-writing__note--highlight");
    var i;
    var c;
    /* Prefer canonical comment id, then validated passage identity — never text alone. */
    if (entry.id) {
      for (i = 0; i < items.length; i++) {
        if (items[i].getAttribute("data-ps-note-id") === String(entry.id)) {
          c = items[i].querySelector(".ps-writing__note-count");
          if (c) {
            c.textContent = formatCount(entry.count || 1);
          }
          if (entry.identity) {
            items[i].setAttribute("data-ps-identity", String(entry.identity));
          }
          return;
        }
      }
    }
    if (entry.identity) {
      for (i = 0; i < items.length; i++) {
        if (items[i].getAttribute("data-ps-identity") === String(entry.identity)) {
          c = items[i].querySelector(".ps-writing__note-count");
          if (c) {
            c.textContent = formatCount(entry.count || 1);
          }
          if (entry.id) {
            items[i].setAttribute("data-ps-note-id", String(entry.id));
          }
          return;
        }
      }
    }
    notesList.appendChild(createEntryEl(entry));
    updateLoadMoreUi();
  }

  function appendCommentInFeed(entry) {
    if (!notesList || !entry) {
      return;
    }
    if (notesEmpty) {
      notesEmpty.hidden = true;
    }
    notesList.hidden = false;
    notesList.appendChild(createEntryEl(entry));
    updateLoadMoreUi();
  }

  function listUrl(after) {
    var url = config.listUrl;
    if (!after || !after.date || !after.id) {
      return url;
    }
    var join = url.indexOf("?") >= 0 ? "&" : "?";
    return (
      url +
      join +
      "afterDate=" +
      encodeURIComponent(after.date) +
      "&afterId=" +
      encodeURIComponent(String(after.id))
    );
  }

  function loadFeed(append) {
    if (!config.listUrl || listLoading) {
      return;
    }
    listLoading = true;
    updateLoadMoreUi();
    var url = append && listCursor ? listUrl(listCursor) : config.listUrl;
    fetch(url, { credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        listLoading = false;
        if (!data || data.code !== "ok" || !Array.isArray(data.notes)) {
          updateLoadMoreUi();
          return;
        }
        listHasMore = !!data.hasMore;
        if (data.nextCursor && data.nextCursor.date && data.nextCursor.id) {
          listCursor = {
            date: data.nextCursor.date,
            id: data.nextCursor.id
          };
        } else if (data.notes.length) {
          var last = data.notes[data.notes.length - 1];
          listCursor = {
            date: last.dateGmt || last.date || "",
            id: last.id
          };
        }
        appendEntries(data.notes, !append);
      })
      .catch(function () {
        listLoading = false;
        updateLoadMoreUi();
      });
  }

  function postPayload(payload) {
    return fetch(config.submitUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    }).then(function (r) {
      return r.json().then(function (data) {
        return { status: r.status, data: data || {} };
      });
    });
  }

  function scrollToCommentSection() {
    var target = commentForm || document.querySelector(".ps-writing__ps");
    if (!target) {
      return;
    }
    closeSheet();
    try {
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (err) {
      target.scrollIntoView(true);
    }
    if (commentBody) {
      window.setTimeout(function () {
        try {
          commentBody.focus();
        } catch (focusErr) {}
      }, 350);
    }
  }

  function onPenTap(event) {
    event.preventDefault();
    event.stopPropagation();
    var live = readLiveSelection();
    var sel = live || (candidateFresh() ? candidate : null);
    if (!sel || !sel.passage) {
      setPending(null);
      setStatus(statusEl, "");
      scrollToCommentSection();
      return;
    }
    setPending({
      passage: sel.passage,
      prefix: sel.prefix || "",
      suffix: sel.suffix || "",
      color: PEN_COLOR,
      range: sel.range || null
      /* requestId assigned inside setPending for a new intentional attempt */
    });
    setStatus(statusEl, "");
    openSheet();
  }

  function onHighlightSubmit() {
    if (submitting || !pending || !pending.passage) {
      return;
    }
    if (!pending.requestId) {
      pending.requestId = newClientRequestId();
    }
    submitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting…";
    var attemptId = pending.requestId;
    var attemptRange = pending.range;
    var attemptPassage = pending.passage;
    postPayload({
      nonce: config.nonce,
      pageId: config.pageId,
      passage: pending.passage,
      prefix: pending.prefix || "",
      suffix: pending.suffix || "",
      note: "",
      color: PEN_COLOR,
      displayName: "",
      company: (honeyEl && honeyEl.value) || "",
      intent: "highlight",
      clientRequestId: attemptId
    })
      .then(function (result) {
        submitting = false;
        submitBtn.textContent = "Submit Highlight";
        var code = result.data && result.data.code;
        if ((result.status === 201 || result.status === 200) && code === "published" && result.data.note) {
          markPrivateFromRange(attemptRange, attemptPassage);
          upsertHighlightInFeed(result.data.note);
          setPending(null);
          setStatus(statusEl, "Highlight submitted.", "ok");
          window.setTimeout(closeSheet, 700);
          return;
        }
        if (code === "pending" || code === "accepted") {
          markPrivateFromRange(attemptRange, attemptPassage);
          setPending(null);
          setStatus(statusEl, "Received for review.", "ok");
          window.setTimeout(closeSheet, 900);
          return;
        }
        if (code === "duplicate") {
          setStatus(statusEl, "Already submitted.", "error");
          submitBtn.disabled = false;
          return;
        }
        /* Keep pending.requestId so a retry reuses the same submission id. */
        setStatus(statusEl, "Could not submit right now.", "error");
        submitBtn.disabled = false;
      })
      .catch(function () {
        submitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Highlight";
        /* Uncertain response: same requestId retained for safe retry. */
        setStatus(statusEl, "Could not submit right now.", "error");
      });
  }

  function onCommentSubmit(event) {
    event.preventDefault();
    if (submitting) {
      return;
    }
    var note = normalizeComment(commentBody && commentBody.value);
    var name = normalizePassage(commentName && commentName.value);
    if (!note) {
      setStatus(commentStatus, "Write a comment first.", "error");
      return;
    }
    if (!name) {
      setStatus(commentStatus, "Add a name or nickname.", "error");
      return;
    }
    if (note.length > NOTE_MAX) {
      setStatus(commentStatus, "Please shorten your comment.", "error");
      return;
    }
    submitting = true;
    if (commentSubmit) {
      commentSubmit.disabled = true;
      commentSubmit.textContent = "Posting…";
    }
    postPayload({
      nonce: config.nonce,
      pageId: config.pageId,
      passage: "",
      prefix: "",
      suffix: "",
      note: note,
      displayName: name,
      company: (commentHoney && commentHoney.value) || "",
      intent: "comment"
    })
      .then(function (result) {
        submitting = false;
        if (commentSubmit) {
          commentSubmit.disabled = false;
          commentSubmit.textContent = "Post Comment";
        }
        var code = result.data && result.data.code;
        if (result.status === 201 && code === "published" && result.data.note) {
          appendCommentInFeed(result.data.note);
          if (commentBody) {
            commentBody.value = "";
          }
          setStatus(commentStatus, "Comment posted.", "ok");
          return;
        }
        if (code === "pending" || code === "accepted") {
          if (commentBody) {
            commentBody.value = "";
          }
          setStatus(commentStatus, "Received for review.", "ok");
          return;
        }
        if (code === "name_required") {
          setStatus(commentStatus, "Add a name or nickname.", "error");
          return;
        }
        setStatus(commentStatus, "Could not post right now.", "error");
      })
      .catch(function () {
        submitting = false;
        if (commentSubmit) {
          commentSubmit.disabled = false;
          commentSubmit.textContent = "Post Comment";
        }
        setStatus(commentStatus, "Could not post right now.", "error");
      });
  }

  document.addEventListener("selectionchange", rememberCandidate);

  penBtn.addEventListener("pointerup", function () {
    var live = readLiveSelection();
    if (live) {
      candidate = live;
    }
  });

  penBtn.addEventListener("click", onPenTap);

  if (closeBtn) {
    closeBtn.addEventListener("click", closeSheet);
  }
  if (clearQuoteBtn) {
    clearQuoteBtn.addEventListener("click", function () {
      setPending(null);
      setStatus(statusEl, "");
      if (closeBtn && typeof closeBtn.focus === "function") {
        closeBtn.focus();
      }
    });
  }
  submitBtn.addEventListener("click", onHighlightSubmit);

  if (commentForm) {
    commentForm.addEventListener("submit", onCommentSubmit);
  }

  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", function () {
      if (listHasMore) {
        loadFeed(true);
      }
    });
  }

  document.addEventListener("keydown", function (event) {
    if (!sheet.classList.contains("is-open")) {
      return;
    }
    if (event.key === "Escape") {
      event.preventDefault();
      closeSheet();
      return;
    }
    if (event.key !== "Tab") {
      return;
    }
    var items = sheetFocusables();
    if (!items.length) {
      return;
    }
    var first = items[0];
    var last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  setPending(null);
  updateLoadMoreUi();
  loadFeed(false);
})();
