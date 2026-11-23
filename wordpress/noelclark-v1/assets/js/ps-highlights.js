/**
 * P.S. reader highlights — Phase B2 (instant publish + approved notes).
 * Highlighter OFF by default. Selection → P.S. card only when ON.
 * Approved notes load via dedicated REST list (page cache safe).
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
  var notesEmpty = document.querySelector("[data-ps-notes-empty]");
  var notesList = document.querySelector("[data-ps-notes-list]");
  var notesCount = document.querySelector("[data-ps-notes-count]");

  if (!root) {
    return;
  }

  /*
   * Branded highlighter editions — pick once per page load.
   * Weights: NC 55%, phrases 15% each. Compact phones always use NC.
   * Never re-roll on toggle, timers, or mid-visit.
   */
  (function selectHighlighterEditionOnce() {
    if (!toggle) {
      return;
    }

    var brands = toggle.querySelectorAll("[data-ps-hl-brand]");

    if (!brands.length) {
      return;
    }

    var edition = "nc";
    var compact = false;

    try {
      compact = window.matchMedia("(max-width: 420px)").matches;
    } catch (err) {
      compact = window.innerWidth <= 420;
    }

    if (!compact) {
      var roll = Math.random() * 100;

      if (roll < 55) {
        edition = "nc";
      } else if (roll < 70) {
        edition = "masterpiece";
      } else if (roll < 85) {
        edition = "niente";
      } else {
        edition = "curiosity";
      }
    }

    toggle.setAttribute("data-ps-hl-edition", edition);

    for (var i = 0; i < brands.length; i += 1) {
      var brand = brands[i];
      var match = brand.getAttribute("data-ps-hl-brand") === edition;

      if (match) {
        brand.removeAttribute("hidden");
      } else {
        brand.setAttribute("hidden", "");
      }
    }
  })();

  var PASSAGE_MAX = config.passageMax || 2000;
  var NOTE_MAX = config.noteMax || 1000;
  var DISPLAY_NAME_MAX = config.displayNameMax || 40;
  var CONTEXT_MAX = config.contextMax || 48;
  var NAME_STORAGE_KEY = "noelclark-ps-display-name";
  var HighlightAPI = window.CSS && window.CSS.highlights ? window.CSS.highlights : null;
  var HighlightCtor = typeof window.Highlight === "function" ? window.Highlight : null;

  var highlighterOn = false;
  var popover = null;
  var passageEl = null;
  var noteEl = null;
  var nameEl = null;
  var statusEl = null;
  var submitBtn = null;
  var honeypotEl = null;
  var pendingSelection = null;
  var pendingRange = null;
  var openTimer = null;
  var selectionTimer = null;
  var submitting = false;
  var coarsePointer = false;
  var knownNoteIds = {};
  var loadedNotes = [];

  try {
    coarsePointer = window.matchMedia("(pointer: coarse)").matches;
  } catch (err) {
    coarsePointer = false;
  }

  function normalizePassage(value) {
    return String(value || "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function prepareNote(value) {
    return String(value || "").replace(/\r\n/g, "\n").replace(/\r/g, "\n");
  }

  function readStoredName() {
    try {
      if (!window.localStorage) {
        return "";
      }

      var stored = window.localStorage.getItem(NAME_STORAGE_KEY);
      return typeof stored === "string" ? stored.trim() : "";
    } catch (err) {
      return "";
    }
  }

  function writeStoredName(name) {
    try {
      if (!window.localStorage) {
        return;
      }

      if (name) {
        window.localStorage.setItem(NAME_STORAGE_KEY, name);
      }
    } catch (err) {
      /* Private mode / blocked storage — ignore. */
    }
  }

  function sanitizeNameClient(value) {
    var name = String(value || "")
      .replace(/[<>{}[\]]/g, "")
      .replace(/\s+/g, " ")
      .trim();

    if (!name) {
      return "";
    }

    if (/https?:\/\/|www\.|\/\/|\.(com|net|org|io|co|info)\b/i.test(name)) {
      return "";
    }

    return name;
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
      /* Safari / partial support. */
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

    var raw = String(range.toString() || "");
    var display = raw.replace(/\r\n/g, "\n").replace(/[ \t]+\n/g, "\n").trim();
    var passage = normalizePassage(raw);

    if (passage.length < 3) {
      return null;
    }

    var tooLong = passage.length > PASSAGE_MAX;
    var rootText = root.textContent || "";
    var index = rootText.indexOf(passage);
    var prefix = "";
    var suffix = "";

    if (!tooLong && index !== -1) {
      var ctx = contextAround(rootText, index, index + passage.length, CONTEXT_MAX);
      prefix = ctx.prefix;
      suffix = ctx.suffix;
    }

    var cloned = range.cloneRange();
    var rect = cloned.getBoundingClientRect();

    return {
      passage: tooLong ? "" : passage,
      display: display || passage,
      prefix: prefix,
      suffix: suffix,
      rect: rect,
      range: cloned,
      tooLong: tooLong,
    };
  }

  function formatCount(count) {
    var n = Math.max(0, Number(count) || 0);

    if (n === 1) {
      return "1 note";
    }

    return String(n) + " notes";
  }

  function formatNoteDate(iso) {
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
        month: "short",
        day: "numeric",
      });
    } catch (err) {
      return "";
    }
  }

  function createNoteElement(entry) {
    var li = document.createElement("li");
    li.className = "ps-writing__note";
    li.setAttribute("data-ps-note-id", String(entry.id || ""));

    var head = document.createElement("div");
    head.className = "ps-writing__note-head";

    var name = document.createElement("span");
    name.className = "ps-writing__note-name";
    name.textContent = entry.displayName || "Reader";

    var date = document.createElement("time");
    date.className = "ps-writing__note-date";
    var dateLabel = formatNoteDate(entry.date);

    if (entry.date) {
      date.setAttribute("datetime", String(entry.date));
    }

    date.textContent = dateLabel;

    head.appendChild(name);

    if (dateLabel) {
      head.appendChild(date);
    }

    var quote = document.createElement("blockquote");
    quote.className = "ps-writing__note-passage";
    quote.textContent = entry.passage || "";

    li.appendChild(head);
    li.appendChild(quote);

    if (entry.note) {
      var body = document.createElement("p");
      body.className = "ps-writing__note-body";
      body.textContent = entry.note;
      li.appendChild(body);
    }

    return li;
  }

  function renderNotes(notes) {
    loadedNotes = Array.isArray(notes) ? notes.slice() : [];
    knownNoteIds = {};

    if (notesCount) {
      notesCount.textContent = formatCount(loadedNotes.length);
    }

    if (!notesList) {
      return;
    }

    notesList.textContent = "";

    if (!loadedNotes.length) {
      notesList.hidden = true;

      if (notesEmpty) {
        notesEmpty.hidden = false;
      }

      return;
    }

    if (notesEmpty) {
      notesEmpty.hidden = true;
    }

    notesList.hidden = false;

    loadedNotes.forEach(function (entry) {
      if (!entry || !entry.id || !entry.passage) {
        return;
      }

      knownNoteIds[String(entry.id)] = true;
      notesList.appendChild(createNoteElement(entry));
    });
  }

  function prependPublishedNote(entry) {
    if (!entry || !entry.id || !entry.passage) {
      return;
    }

    var id = String(entry.id);

    if (knownNoteIds[id]) {
      return;
    }

    knownNoteIds[id] = true;
    loadedNotes.unshift(entry);

    if (notesCount) {
      notesCount.textContent = formatCount(loadedNotes.length);
    }

    if (!notesList) {
      return;
    }

    if (notesEmpty) {
      notesEmpty.hidden = true;
    }

    notesList.hidden = false;
    notesList.insertBefore(createNoteElement(entry), notesList.firstChild);
  }

  function loadPublicNotes() {
    if (!config.listUrl || !notesList) {
      return;
    }

    fetch(config.listUrl, {
      method: "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
      cache: "no-store",
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("list_failed");
        }

        return response.json();
      })
      .then(function (data) {
        if (!data || data.code !== "ok" || !Array.isArray(data.notes)) {
          return;
        }

        renderNotes(data.notes);
      })
      .catch(function () {
        /* Keep empty shell; do not invent notes. */
      });
  }

  function ensurePopover() {
    if (popover) {
      return;
    }

    popover = document.createElement("div");
    popover.className = "ps-highlight-popover";
    popover.hidden = true;
    popover.setAttribute("role", "dialog");
    popover.setAttribute("aria-label", "P.S.");
    popover.setAttribute("aria-modal", "false");

    popover.innerHTML =
      '<button type="button" class="ps-highlight-popover__close" data-ps-hl-cancel aria-label="Close">×</button>' +
      '<h3 class="ps-highlight-popover__title">P.S.</h3>' +
      '<div class="ps-highlight-popover__name-row">' +
      '<label class="ps-highlight-popover__name-label" for="ps-highlight-name">Name</label>' +
      '<input type="text" id="ps-highlight-name" class="ps-highlight-popover__name" data-ps-hl-name' +
      ' autocomplete="nickname" spellcheck="false" placeholder="Name or nickname"' +
      ' aria-describedby="ps-highlight-status">' +
      "</div>" +
      '<div class="ps-highlight-popover__panels">' +
      '<div class="ps-highlight-popover__panel ps-highlight-popover__panel--passage">' +
      '<blockquote class="ps-highlight-popover__passage" data-ps-hl-passage></blockquote>' +
      "</div>" +
      '<div class="ps-highlight-popover__panel ps-highlight-popover__panel--note">' +
      '<label class="visually-hidden" for="ps-highlight-note">Add a note (optional)</label>' +
      '<textarea id="ps-highlight-note" class="ps-highlight-popover__note" data-ps-hl-note' +
      ' rows="5" placeholder="Add a note (optional)"' +
      ' aria-describedby="ps-highlight-status"></textarea>' +
      "</div>" +
      "</div>" +
      '<div class="ps-highlight-popover__honeypot" aria-hidden="true">' +
      '<label>Company<input type="text" name="company" data-ps-hl-honey tabindex="-1" autocomplete="off"></label>' +
      "</div>" +
      '<div class="ps-highlight-popover__actions">' +
      '<p class="ps-highlight-popover__status" id="ps-highlight-status" data-ps-hl-status hidden></p>' +
      '<button type="button" class="ps-highlight-popover__submit" data-ps-hl-submit>POST SCRIPT</button>' +
      "</div>";

    document.body.appendChild(popover);

    passageEl = popover.querySelector("[data-ps-hl-passage]");
    noteEl = popover.querySelector("[data-ps-hl-note]");
    nameEl = popover.querySelector("[data-ps-hl-name]");
    statusEl = popover.querySelector("[data-ps-hl-status]");
    submitBtn = popover.querySelector("[data-ps-hl-submit]");
    honeypotEl = popover.querySelector("[data-ps-hl-honey]");

    submitBtn.addEventListener("click", onSubmit);
    popover.querySelector("[data-ps-hl-cancel]").addEventListener("click", closePopover);

    noteEl.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.stopPropagation();
      }
    });

    nameEl.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        event.stopPropagation();
      }
    });
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

  function setSubmitEnabled(enabled) {
    if (!submitBtn) {
      return;
    }

    submitBtn.disabled = !enabled;
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
    var width = popover.offsetWidth || 520;
    var height = popover.offsetHeight || 320;
    var vw = window.innerWidth;
    var vh = window.innerHeight;

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
    pendingSelection = selection.tooLong ? null : selection;
    pendingRange = selection.range || null;
    applyPendingHighlight(pendingRange);

    if (HighlightAPI && pendingRange) {
      clearWindowSelection();
    }

    passageEl.textContent = selection.display || selection.passage;
    noteEl.value = "";
    nameEl.value = readStoredName();
    if (honeypotEl) {
      honeypotEl.value = "";
    }

    submitBtn.textContent = "POST SCRIPT";

    if (selection.tooLong) {
      setStatus("Please select a shorter passage (max " + String(PASSAGE_MAX) + " characters).", "error");
      setSubmitEnabled(false);
    } else {
      setStatus("", "");
      setSubmitEnabled(true);
    }

    positionPopover(selection.rect);

    window.setTimeout(function () {
      if (selection.tooLong) {
        focusNoScroll(submitBtn);
      } else if (!nameEl.value) {
        focusNoScroll(nameEl);
      } else {
        focusNoScroll(noteEl);
      }

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

  function finishQuietSubmit(displayName) {
    writeStoredName(displayName);
    submitBtn.textContent = "POST SCRIPT";
    clearWindowSelection();
    clearPendingHighlight();
    closePopover();
  }

  function onSubmit() {
    if (submitting || !pendingSelection) {
      return;
    }

    var draftNote = prepareNote(noteEl && noteEl.value);
    var draftNameRaw = String(nameEl && nameEl.value ? nameEl.value : "");
    var draftNameNormalized = draftNameRaw.replace(/\s+/g, " ").trim();
    var displayName = sanitizeNameClient(draftNameRaw);

    if (draftNameNormalized.length > DISPLAY_NAME_MAX) {
      setStatus("Please use a shorter name (max " + String(DISPLAY_NAME_MAX) + " characters).", "error");
      focusNoScroll(nameEl);
      return;
    }

    if (!displayName) {
      if (/https?:\/\/|www\./i.test(draftNameRaw)) {
        setStatus("Please use a name without links.", "error");
      } else {
        setStatus("Please add a name or nickname.", "error");
      }
      focusNoScroll(nameEl);
      return;
    }

    if (draftNote.length > NOTE_MAX) {
      setStatus("Please shorten your note (max " + String(NOTE_MAX) + " characters).", "error");
      focusNoScroll(noteEl);
      return;
    }

    if (pendingSelection.passage.length > PASSAGE_MAX) {
      setStatus("Please select a shorter passage (max " + String(PASSAGE_MAX) + " characters).", "error");
      setSubmitEnabled(false);
      return;
    }

    var payload = {
      nonce: config.nonce,
      pageId: config.pageId,
      displayName: displayName,
      passage: pendingSelection.passage,
      prefix: pendingSelection.prefix || "",
      suffix: pendingSelection.suffix || "",
      note: draftNote,
      company: honeypotEl ? String(honeypotEl.value || "") : "",
    };

    submitting = true;
    setSubmitEnabled(false);
    submitBtn.textContent = "Posting…";
    setStatus("", "");

    fetch(config.submitUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
      cache: "no-store",
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data || {} };
        });
      })
      .then(function (result) {
        submitting = false;

        var code = result.data && result.data.code ? result.data.code : "";

        if (result.status === 201 && code === "published" && result.data.note) {
          prependPublishedNote(result.data.note);
          finishQuietSubmit(displayName);
          return;
        }

        if (code === "pending") {
          writeStoredName(displayName);
          submitBtn.textContent = "POST SCRIPT";
          setStatus("Pending review.", "ok");
          setSubmitEnabled(false);
          window.setTimeout(function () {
            clearWindowSelection();
            clearPendingHighlight();
            closePopover();
          }, 900);
          return;
        }

        if (code === "accepted" || result.status === 202) {
          /* Honeypot / spam quarantine — no public insert; no pending claim. */
          finishQuietSubmit(displayName);
          return;
        }

        submitBtn.textContent = "POST SCRIPT";
        setSubmitEnabled(true);
        noteEl.value = draftNote;
        nameEl.value = draftNameRaw;

        if (code === "duplicate") {
          setStatus("That P.S. was already posted.", "error");
          return;
        }

        if (code === "rate_limited") {
          setStatus("Please wait before posting again.", "error");
          return;
        }

        if (code === "name_required") {
          setStatus("Please add a name or nickname.", "error");
          focusNoScroll(nameEl);
          return;
        }

        if (code === "name_too_long") {
          setStatus("Please use a shorter name (max " + String(DISPLAY_NAME_MAX) + " characters).", "error");
          focusNoScroll(nameEl);
          return;
        }

        if (code === "passage_too_long") {
          setStatus("Please select a shorter passage (max " + String(PASSAGE_MAX) + " characters).", "error");
          return;
        }

        if (code === "note_too_long") {
          setStatus("Please shorten your note (max " + String(NOTE_MAX) + " characters).", "error");
          focusNoScroll(noteEl);
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

        if (code === "rejected") {
          setStatus("That submission could not be posted.", "error");
          return;
        }

        setStatus("Could not post right now. Please try again.", "error");
      })
      .catch(function () {
        submitting = false;
        submitBtn.textContent = "POST SCRIPT";
        setSubmitEnabled(true);
        noteEl.value = draftNote;
        nameEl.value = draftNameRaw;
        setStatus("Could not post right now. Please try again.", "error");
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
  loadPublicNotes();
})();
