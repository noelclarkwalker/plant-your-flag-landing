(function () {
  "use strict";

  var core = window.BrowseCore;
  var artifacts = window.MAIL_ROOM_ARTIFACTS || [];
  var threads = window.MAIL_ROOM_THREADS || {};

  /* Horizontal sill rail: reveal the full curated set; no separate “load more” stack. */
  var INITIAL_BATCH = 100;
  var LOAD_BATCH = 5;
  var MIN_FOR_LOAD_MORE = 999;
  var ROOM_PATH =
    (window.noelclarkV1MailRoom && noelclarkV1MailRoom.roomPath) ||
    window.location.pathname;


  var browseRoot = document.getElementById("mail-browse");
  var discoveryMount = document.getElementById("mail-browse-discovery");
  var exploreToggle = document.getElementById("mail-explore-toggle");
  var DISCOVERY_TAG_LIMIT = 12;
  var EXCLUDED_SUBJECT_TAGS = { humanity: true };
  var hero = document.querySelector(".mail-room-hero");
  var fieldSection = document.querySelector(".mail-room-field");
  var fieldGrid = document.getElementById("mail-room-artifacts");
  var searchInput = document.getElementById("mail-browse-query");
  var activeBar = document.getElementById("mail-browse-active");
  var activeText = document.getElementById("mail-browse-active-text");
  var clearButton = document.getElementById("mail-browse-clear");
  var emptyState = document.getElementById("mail-browse-empty");
  var loadMoreButton = document.getElementById("mail-browse-more");
  var readingDialog = document.getElementById("mail-reading");
  var readingPanel = null;
  var readingToolbar = document.querySelector(".mail-reading__toolbar");
  var readingBody = document.querySelector(".mail-reading__body");
  var readingTitle = document.getElementById("mail-reading-title");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var scrollRestore = 0;
  var lastTrigger = null;
  var readingNav = { source: "archive", ids: [], index: -1 };
  var activeThreadId = null;
  var readingNavStatus = null;

  function normalizeEra(era) {
    var value = String(era || "all").toLowerCase();
    if (value === "contemporary" || value === "historical") {
      return value;
    }
    return "all";
  }

  function artifactEra(artifact) {
    if (artifact && artifact.era) {
      return normalizeEra(artifact.era);
    }
    var presentation = String((artifact && artifact.presentation) || "").toLowerCase();
    if (presentation === "historical-paper") {
      return "historical";
    }
    return "contemporary";
  }

  var parsedBrowse = core ? core.normalizeBrowseState(core.parseState()) : { q: "", tags: [], tag: "" };
  var state = Object.assign({}, parsedBrowse, {
    era: normalizeEra(
      (typeof URLSearchParams !== "undefined" &&
        new URLSearchParams(window.location.search).get("era")) ||
        "all"
    ),
  });
  var revealedCount = INITIAL_BATCH;

  if (!fieldGrid || !core) {
    return;
  }

  function escapeHtml(value) {
    return core.escapeHtml(value);
  }

  function formatRoute(from, to) {
    return escapeHtml(from) + ' <span aria-hidden="true">→</span> ' + escapeHtml(to);
  }

  function artifactSearchItem(artifact) {
    return {
      title: artifact.from + " " + artifact.to,
      tags: artifact.tags || [],
      searchText: [
        artifact.from,
        artifact.to,
        artifact.date,
        artifact.searchText || "",
      ].join(" "),
    };
  }

  function transcriptionToHtml(text) {
    return text
      .trim()
      .split(/\n\s*\n/)
      .map(function (paragraph) {
        return "<p>" + escapeHtml(paragraph.trim()).replace(/\n/g, "<br />") + "</p>";
      })
      .join("");
  }

  function hasAfterLetterContent(afterLetter) {
    if (!afterLetter || afterLetter.status === "none") {
      return false;
    }

    if (afterLetter.status === "unknown") {
      return Boolean(afterLetter.body);
    }

    return Boolean(afterLetter.body);
  }

  function renderAfterLetter(afterLetter) {
    if (!hasAfterLetterContent(afterLetter)) {
      return "";
    }

    var title = afterLetter.heading || "After the letter";
    var copyClass =
      afterLetter.status === "unknown"
        ? "mail-reading__after-copy mail-reading__after-copy--unknown"
        : "mail-reading__after-copy";

    return (
      '<section class="mail-reading__after">' +
      '<h3 class="mail-reading__after-title">' +
      escapeHtml(title) +
      "</h3>" +
      '<p class="' +
      copyClass +
      '">' +
      escapeHtml(afterLetter.body) +
      "</p>" +
      "</section>"
    );
  }

  function renderPsConnection(psConnection) {
    if (!psConnection || !psConnection.href || !psConnection.label) {
      return "";
    }

    return (
      '<aside class="mail-reading__ps">' +
      '<a class="mail-reading__ps-link" href="' +
      escapeHtml(psConnection.href) +
      '">' +
      escapeHtml(psConnection.label) +
      "</a>" +
      "</aside>"
    );
  }

  function renderTranscriptionBlock(artifact) {
    var html =
      '<div class="mail-reading__transcription">' +
      transcriptionToHtml(artifact.transcription) +
      "</div>";

    var presentation = artifact.presentation;

    if (
      presentation === "historical-paper" ||
      presentation === "digital-message" ||
      presentation === "contemporary-letter"
    ) {
      return (
        '<article class="mail-reading__artifact mail-reading__artifact--' +
        escapeHtml(presentation) +
        '">' +
        html +
        "</article>"
      );
    }

    return html;
  }

  function renderArtifactReadingNotation(artifact) {
    var tags = visitorFacingTags(artifact);

    if (!tags.length) {
      return "";
    }

    return (
      '<footer class="mail-reading__notation">' +
      core.renderTagNotation(tags, ROOM_PATH, [], {
        variant: "notation",
        showLabel: true,
        labelText: "Tags",
        suppressBrowseActive: true,
      }) +
      "</footer>"
    );
  }

  function isReadableArtifact(artifact) {
    return Boolean(artifact && artifact.transcription);
  }

  function getThreadRegistryEntry(threadId) {
    return threads[String(threadId || "").trim()] || null;
  }

  function getThreadMemberIds(threadId) {
    var slug = String(threadId || "").trim();

    if (!slug || !getThreadRegistryEntry(slug)) {
      return [];
    }

    var members = artifacts.filter(function (artifact) {
      return artifact.threadId === slug && isReadableArtifact(artifact);
    });

    if (members.length < 2) {
      return [];
    }

    var entry = getThreadRegistryEntry(slug);
    var ordered = [];

    if (entry.memberOrder && entry.memberOrder.length) {
      entry.memberOrder.forEach(function (memberId) {
        if (
          members.some(function (artifact) {
            return artifact.id === memberId;
          })
        ) {
          ordered.push(memberId);
        }
      });

      members.forEach(function (artifact) {
        if (ordered.indexOf(artifact.id) === -1) {
          ordered.push(artifact.id);
        }
      });

      return ordered;
    }

    members.sort(function (a, b) {
      var orderA = typeof a.threadOrder === "number" ? a.threadOrder : Infinity;
      var orderB = typeof b.threadOrder === "number" ? b.threadOrder : Infinity;

      if (orderA !== orderB) {
        return orderA - orderB;
      }

      return a.id.localeCompare(b.id);
    });

    return members.map(function (artifact) {
      return artifact.id;
    });
  }

  function getThreadSiblingIds(artifactId) {
    var artifact = artifacts.find(function (item) {
      return item.id === artifactId;
    });

    if (!artifact || !artifact.threadId) {
      return [];
    }

    return getThreadMemberIds(artifact.threadId).filter(function (memberId) {
      return memberId !== artifactId;
    });
  }

  function artifactThreadLabel(artifact) {
    return artifact.from + " to " + artifact.to + ", " + artifact.date;
  }

  function renderReadingThread(artifact) {
    var siblingIds = getThreadSiblingIds(artifact.id);

    if (!siblingIds.length) {
      return "";
    }

    var items = siblingIds
      .map(function (memberId) {
        var member = artifacts.find(function (item) {
          return item.id === memberId;
        });

        if (!member) {
          return "";
        }

        var label = artifactThreadLabel(member);

        return (
          '<li class="mail-reading__thread-item">' +
          '<button type="button" class="mail-reading__thread-member" data-thread-member="' +
          escapeHtml(memberId) +
          '" aria-label="' +
          escapeHtml(label) +
          '">' +
          formatRoute(member.from, member.to) +
          '<span class="mail-reading__thread-date">' +
          escapeHtml(member.date) +
          "</span>" +
          "</button></li>"
        );
      })
      .join("");

    return (
      '<section class="mail-reading__thread" aria-label="Follow the thread">' +
      '<details class="mail-reading__thread-details">' +
      '<summary class="mail-reading__thread-summary">Follow the thread →</summary>' +
      '<ul class="mail-reading__thread-list">' +
      items +
      "</ul></details></section>"
    );
  }

  function visitorFacingTags(artifact) {
    return (artifact.tags || []).filter(function (tag) {
      return !EXCLUDED_SUBJECT_TAGS[String(tag || "").toLowerCase()];
    });
  }

  function renderDiscovery() {
    if (!discoveryMount) {
      return;
    }

    var items = artifacts.map(function (artifact) {
      var item = artifactSearchItem(artifact);
      item.tags = visitorFacingTags(artifact);
      return item;
    });

    var html = core.renderDiscoveryTags(items, ROOM_PATH, state.tags, {
      maxVisible: DISCOVERY_TAG_LIMIT,
      labelText: "Subject",
      multiSelect: true,
    });

    /* Hide the redundant label — outer Explore toggle already names the section. */
    discoveryMount.innerHTML = html
      ? html.replace(
          /<p class="room-discovery__label">[\s\S]*?<\/p>/,
          ""
        )
      : "";
  }

  /* Reading-dialog attribution only — envelope fronts never invent titles/summaries. */
  function envelopeTitle(artifact) {
    var from = String(artifact.from || "").trim();
    var to = String(artifact.to || "").trim();
    if (from && to) {
      return from + " → " + to;
    }
    return from || to || "Correspondence";
  }

  function envelopeAriaLabel(artifact, canOpen) {
    var from = String(artifact.from || "").trim();
    var to = String(artifact.to || "").trim();
    var prefix = canOpen ? "Open correspondence" : "Correspondence";
    if (from && to) {
      return prefix + " from " + from + " to " + to;
    }
    if (from) {
      return prefix + " from " + from;
    }
    if (to) {
      return prefix + " to " + to;
    }
    return prefix;
  }

  function stampPalette(id, index) {
    var colors = ["#C62828", "#00838F", "#6A1B9A", "#EF6C00"];
    var hash = 0;
    String(id || "")
      .split("")
      .forEach(function (ch) {
        hash = (hash + ch.charCodeAt(0)) * 17;
      });
    /* Prefer adjacent distinctness by rail index; fall back to id hash. */
    var colorIndex =
      typeof index === "number" ? index % colors.length : Math.abs(hash) % colors.length;
    return {
      color: colors[colorIndex],
      motif: Math.abs(hash) % 3
    };
  }

  function postalMotif(motif, safe, cx, cy) {
    var x = cx || 86;
    var y = cy || 36;
    if (motif === 1) {
      /* Decorative locomotive silhouette — no route/date text. */
      return (
        '<rect x="' +
        (x - 10.8) +
        '" y="' +
        (y - 7.8) +
        '" width="21.2" height="10.2" rx="1.1" fill="none" stroke="' +
        safe +
        '" stroke-width="1.45"/>' +
        '<path d="M' +
        (x - 9.2) +
        " " +
        (y - 7.8) +
        " V" +
        (y - 11.8) +
        " H" +
        (x + 1.2) +
        " L" +
        (x + 10.4) +
        " " +
        (y - 7.8) +
        '" fill="none" stroke="' +
        safe +
        '" stroke-width="1.45" stroke-linejoin="round"/>' +
        '<circle cx="' +
        (x - 5.4) +
        '" cy="' +
        (y + 5.8) +
        '" r="3" fill="none" stroke="' +
        safe +
        '" stroke-width="1.35"/>' +
        '<circle cx="' +
        (x - 5.4) +
        '" cy="' +
        (y + 5.8) +
        '" r="1.15" fill="' +
        safe +
        '"/>' +
        '<circle cx="' +
        (x + 5.8) +
        '" cy="' +
        (y + 5.8) +
        '" r="3" fill="none" stroke="' +
        safe +
        '" stroke-width="1.35"/>' +
        '<circle cx="' +
        (x + 5.8) +
        '" cy="' +
        (y + 5.8) +
        '" r="1.15" fill="' +
        safe +
        '"/>'
      );
    }
    if (motif === 2) {
      /* Decorative leaf — no botanical/place labeling. */
      return (
        '<path d="M' +
        x +
        " " +
        (y - 15.8) +
        "c9.4 5.4 11.2 16.2 0 26.4C" +
        (x - 11.2) +
        " " +
        (y + 0.4) +
        " " +
        (x - 9.4) +
        " " +
        (y - 10.4) +
        " " +
        x +
        " " +
        (y - 15.8) +
        'z" fill="none" stroke="' +
        safe +
        '" stroke-width="1.45" stroke-linejoin="round"/>' +
        '<path d="M' +
        x +
        " " +
        (y - 12.8) +
        " V" +
        (y + 8.8) +
        " M" +
        x +
        " " +
        (y - 5.2) +
        " C" +
        (x - 5.2) +
        " " +
        (y - 3.2) +
        " " +
        (x - 7.8) +
        " " +
        (y + 0.6) +
        " " +
        (x - 8.8) +
        " " +
        (y + 4.6) +
        " M" +
        x +
        " " +
        (y - 1.4) +
        " C" +
        (x + 5.1) +
        " " +
        (y + 0.4) +
        " " +
        (x + 7.4) +
        " " +
        (y + 3.6) +
        " " +
        (x + 8.4) +
        " " +
        (y + 7.2) +
        '" fill="none" stroke="' +
        safe +
        '" stroke-width="1.2" stroke-linecap="round"/>'
      );
    }
    /* Decorative pavilion / facade motif. */
    return (
      '<path d="M' +
      (x - 9.4) +
      " " +
      (y + 6.2) +
      " V" +
      (y - 5.2) +
      " L" +
      x +
      " " +
      (y - 13.6) +
      " L" +
      (x + 9.4) +
      " " +
      (y - 5.2) +
      " V" +
      (y + 6.2) +
      '" fill="none" stroke="' +
      safe +
      '" stroke-width="1.5" stroke-linejoin="round"/>' +
      '<path d="M' +
      (x - 6.6) +
      " " +
      (y + 6.2) +
      " V" +
      (y - 2.6) +
      " H" +
      (x + 6.6) +
      " V" +
      (y + 6.2) +
      " M" +
      (x - 3.4) +
      " " +
      (y - 2.6) +
      " V" +
      (y + 6.2) +
      " M" +
      x +
      " " +
      (y - 2.6) +
      " V" +
      (y + 6.2) +
      " M" +
      (x + 3.4) +
      " " +
      (y - 2.6) +
      " V" +
      (y + 6.2) +
      '" fill="none" stroke="' +
      safe +
      '" stroke-width="1.2"/>' +
      '<circle cx="' +
      x +
      '" cy="' +
      (y - 7.8) +
      '" r="1.45" fill="' +
      safe +
      '"/>'
    );
  }

  /* Decorative only — no dates, places, codes, or fabricated postal claims. */
  function postalMarkSvg(color, motif) {
    var safe = escapeHtml(color || "#C62828");
    var cx = 90;
    var cy = 38;
    var wave = function (y) {
      return (
        '<path d="M1 ' +
        y +
        ' c5.2 2.9 10.4 2.9 15.6 0s10.4-2.9 15.6 0 10.4 2.9 15.6 0 10.4-2.9 15.6 0 10.4 2.9 15.6 0"/>'
      );
    };
    return (
      '<svg class="mail-envelope__postal-svg" viewBox="0 0 136 96" aria-hidden="true" focusable="false">' +
      '<g fill="none" stroke="' +
      safe +
      '" stroke-width="1.55" stroke-linecap="round" opacity="0.95">' +
      wave(12) +
      wave(19.5) +
      wave(27) +
      wave(34.5) +
      wave(42) +
      wave(49.5) +
      "</g>" +
      /* Outer heavy cancellation ring + nested fine rings. */
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="31.5" fill="none" stroke="' +
      safe +
      '" stroke-width="3.4"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="28.2" fill="none" stroke="' +
      safe +
      '" stroke-width="1.15"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="25.4" fill="none" stroke="' +
      safe +
      '" stroke-width="1.55"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="22.6" fill="none" stroke="' +
      safe +
      '" stroke-width="0.85" stroke-dasharray="1.05 1.35" opacity="0.9"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="19.8" fill="none" stroke="' +
      safe +
      '" stroke-width="1.25"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="16.6" fill="none" stroke="' +
      safe +
      '" stroke-width="0.95" opacity="0.92"/>' +
      '<circle cx="' +
      cx +
      '" cy="' +
      cy +
      '" r="13.8" fill="none" stroke="' +
      safe +
      '" stroke-width="0.75" stroke-dasharray="0.9 1.2" opacity="0.85"/>' +
      postalMotif(motif, safe, cx, cy) +
      '<g fill="none" stroke="' +
      safe +
      '" stroke-width="1.15" stroke-linecap="round" opacity="0.9">' +
      '<path d="M' +
      cx +
      " 8.2 v3.4 M" +
      cx +
      " 64.4 v3.4 M61.2 " +
      cy +
      " h3.4 M115.4 " +
      cy +
      ' h3.4"/>' +
      '<path d="M71.2 16.6 l2.2 2.2 M108.8 16.6 l-2.2 2.2 M71.2 59.4 l2.2-2.2 M108.8 59.4 l-2.2-2.2"/>' +
      "</g>" +
      "</svg>"
    );
  }

  function renderArtifacts() {
    fieldGrid.innerHTML = artifacts
      .map(function (artifact, index) {
        var from = String(artifact.from || "").trim();
        var to = String(artifact.to || "").trim();
        var palette = stampPalette(artifact.id, index);
        var canOpen = Boolean(artifact.transcription);
        var label = envelopeAriaLabel(artifact, canOpen);
        var fromHtml = from
          ? '<p class="mail-envelope__from">' + escapeHtml(from) + "</p>"
          : "";
        var toHtml = to
          ? '<p class="mail-envelope__to">' +
            escapeHtml(to) +
            '<span class="mail-envelope__to-rule" aria-hidden="true"></span></p>'
          : from
            ? ""
            : '<p class="mail-envelope__to">Correspondence<span class="mail-envelope__to-rule" aria-hidden="true"></span></p>';

        return (
          '<article class="mail-envelope" id="artifact-' +
          escapeHtml(artifact.id) +
          '" data-artifact-id="' +
          escapeHtml(artifact.id) +
          '" data-era="' +
          escapeHtml(artifactEra(artifact)) +
          '"' +
          (canOpen
            ? ' data-open-artifact="' +
              escapeHtml(artifact.id) +
              '" role="button" tabindex="0"'
            : ' aria-disabled="true"') +
          ' aria-label="' +
          escapeHtml(label) +
          '" style="--stamp-color:' +
          palette.color +
          '"' +
          (index >= revealedCount ? " hidden" : "") +
          ">" +
          '<div class="mail-envelope__postal" aria-hidden="true">' +
          postalMarkSvg(palette.color, palette.motif) +
          "</div>" +
          '<div class="mail-envelope__face">' +
          fromHtml +
          toHtml +
          "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  function matchingArtifacts() {
    var era = normalizeEra(state.era);
    return artifacts.filter(function (artifact) {
      if (era !== "all" && artifactEra(artifact) !== era) {
        return false;
      }
      var item = artifactSearchItem(artifact);
      item.tags = visitorFacingTags(artifact);
      return core.matchesItem(item, state);
    });
  }

  function isMailFilterActive() {
    return (
      core.isFilterActive(state) || normalizeEra(state.era) !== "all"
    );
  }

  function describeActiveFilter() {
    var parts = [];
    var query = core.normalizeQuery(state.q);
    var tags = core.getStateTags(state);
    var era = normalizeEra(state.era);

    if (era !== "all") {
      parts.push(era === "contemporary" ? "Contemporary" : "Historical");
    }

    if (query) {
      parts.push('Search: "' + escapeHtml(state.q.trim()) + '"');
    }

    if (tags.length) {
      parts.push(
        "Tag: " +
          tags
            .map(function (tag) {
              return escapeHtml(core.tagLabel(tag));
            })
            .join(", ")
      );
    }

    return parts.join(" · ");
  }

  function updateActiveBar() {
    if (!activeBar || !activeText) {
      return;
    }

    var active = isMailFilterActive();

    activeBar.hidden = !active;

    if (active) {
      activeText.textContent = describeActiveFilter();
    }
  }

  function updateEraButtons() {
    if (!browseRoot) {
      return;
    }
    var era = normalizeEra(state.era);
    browseRoot.querySelectorAll("[data-mail-era]").forEach(function (button) {
      var value = normalizeEra(button.getAttribute("data-mail-era"));
      button.setAttribute("aria-pressed", value === era ? "true" : "false");
    });
  }

  function applyBrowse(options) {
    options = options || {};
    var historyMode = options.historyMode || "none";

    state = Object.assign(core.normalizeBrowseState(state), {
      era: normalizeEra(state.era),
    });

    var filterActive = isMailFilterActive();
    var matches = matchingArtifacts();
    var matchIds = matches.map(function (artifact) {
      return artifact.id;
    });
    var matchCount = matches.length;
    var visibleCap = filterActive ? matchCount : revealedCount;
    var visibleShown = 0;
    var anyVisible = false;

    artifacts.forEach(function (artifact) {
      var node = document.getElementById("artifact-" + artifact.id);

      if (!node) {
        return;
      }

      var isMatch = matchIds.indexOf(artifact.id) !== -1;
      var showMatch = isMatch && visibleShown < visibleCap;

      if (showMatch) {
        visibleShown += 1;
        anyVisible = true;
      }

      node.hidden = !showMatch;
    });

    if (emptyState) {
      emptyState.hidden = anyVisible || !filterActive;
    }

    if (loadMoreButton) {
      var poolCount = filterActive ? matchCount : artifacts.length;
      var useLoadMore = core.shouldUseLoadMore(poolCount, MIN_FOR_LOAD_MORE);
      var shown = filterActive ? visibleShown : revealedCount;
      var remaining = poolCount - shown;

      loadMoreButton.hidden = filterActive || !useLoadMore || remaining <= 0;
    }

    renderDiscovery();
    updateTagCurrentStates();
    updateEraButtons();
    updateActiveBar();

    writeMailState(historyMode);
  }

  function updateTagCurrentStates() {
    if (!browseRoot) {
      return;
    }

    browseRoot.querySelectorAll(".room-discovery__tag[data-room-tag]").forEach(function (link) {
      var tag = link.getAttribute("data-room-tag") || "";
      var selected = core.getStateTags(state).indexOf(tag) !== -1;

      if (selected) {
        link.setAttribute("aria-pressed", "true");
      } else {
        link.setAttribute("aria-pressed", "false");
      }

      link.removeAttribute("aria-current");
    });
  }

  function writeMailState(historyMode) {
    if (historyMode === "none") {
      return;
    }
    var url = new URL(window.location.href);
    if (state.q) url.searchParams.set("q", state.q);
    else url.searchParams.delete("q");

    var tags = core.getStateTags(state);
    url.searchParams.delete("tag");
    url.searchParams.delete("tags");
    if (tags.length === 1) {
      url.searchParams.set("tag", tags[0]);
    } else if (tags.length > 1) {
      url.searchParams.set("tags", tags.join(","));
    }

    if (normalizeEra(state.era) !== "all") {
      url.searchParams.set("era", state.era);
    } else {
      url.searchParams.delete("era");
    }

    var method = historyMode === "push" ? "pushState" : "replaceState";
    window.history[method]({}, "", url.pathname + url.search + url.hash);
  }

  function clearFilters() {
    state = { q: "", tags: [], tag: "", era: "all" };
    revealedCount = INITIAL_BATCH;

    if (searchInput) {
      searchInput.value = "";
    }

    renderArtifacts();
    applyBrowse({ historyMode: "push" });
  }

  function setEra(era) {
    state.era = normalizeEra(era);
    revealedCount = artifacts.length;
    renderArtifacts();
    applyBrowse({ historyMode: "push" });
  }

  function setExploreOpen(open) {
    if (!exploreToggle || !discoveryMount) {
      return;
    }
    exploreToggle.setAttribute("aria-expanded", open ? "true" : "false");
    discoveryMount.hidden = !open;
  }

  function toggleExploreTag(tag) {
    var slug = String(tag || "").trim();
    var tags = core.getStateTags(state).slice();

    if (!slug) {
      return;
    }

    var index = tags.indexOf(slug);

    if (index === -1) {
      tags.push(slug);
    } else {
      tags.splice(index, 1);
    }

    state = Object.assign(core.normalizeBrowseState({ q: state.q, tags: tags }), {
      era: state.era,
    });
    revealedCount = artifacts.length;
    renderArtifacts();
    applyBrowse({ historyMode: "push" });
  }

  function applyReaderTagFilter(tag) {
    var slug = String(tag || "").trim();

    if (!slug || EXCLUDED_SUBJECT_TAGS[slug.toLowerCase()]) {
      return;
    }

    closeReading();
    state = Object.assign(core.normalizeBrowseState({ q: "", tags: [slug] }), {
      era: "all",
    });
    revealedCount = artifacts.length;
    renderArtifacts();
    applyBrowse({ historyMode: "push" });
  }

  function scrollToWhatArrived() {
    if (!fieldSection) {
      return;
    }

    fieldSection.scrollIntoView({
      block: "start",
      behavior: "auto",
    });
  }

  function resetReadingPanelScroll() {
    if (!readingPanel && readingDialog) {
      readingPanel = readingDialog.querySelector(".mail-reading__panel");
    }

    if (readingPanel) {
      readingPanel.scrollTop = 0;
    }
  }

  function renderReadingToolbar() {
    if (!readingToolbar) {
      return;
    }

    var prevDisabled = readingNav.index <= 0;
    var nextDisabled = readingNav.index >= readingNav.ids.length - 1;
    var prevAriaLabel =
      readingNav.source === "thread" ? ' aria-label="Previous in thread"' : "";
    var nextAriaLabel =
      readingNav.source === "thread" ? ' aria-label="Next in thread"' : "";

    readingToolbar.innerHTML =
      '<button type="button" class="mail-reading__return">← BACK TO WHAT ARRIVED</button>' +
      '<div class="mail-reading__step">' +
      '<button type="button" class="mail-reading__prev"' +
      (prevDisabled ? ' disabled aria-disabled="true"' : "") +
      prevAriaLabel +
      ">Previous</button>" +
      '<button type="button" class="mail-reading__next"' +
      (nextDisabled ? ' disabled aria-disabled="true"' : "") +
      nextAriaLabel +
      ">Next</button>" +
      "</div>";
  }

  function renderReadingContent(artifact) {
    var excerptNote = artifact.excerpt ? " · " + (artifact.excerptLabel || "Excerpt") : "";

    renderReadingToolbar();

    readingBody.innerHTML =
      '<header class="mail-reading__identity">' +
      formatRoute(artifact.from, artifact.to) +
      "</header>" +
      '<p class="mail-reading__meta">' +
      escapeHtml(artifact.date) +
      excerptNote +
      "</p>" +
      renderTranscriptionBlock(artifact) +
      renderAfterLetter(artifact.afterLetter) +
      renderPsConnection(artifact.psConnection) +
      renderArtifactReadingNotation(artifact) +
      renderReadingThread(artifact);

    readingBody.querySelectorAll("[data-room-tag]").forEach(function (tagLink) {
      tagLink.addEventListener("click", function (event) {
        event.preventDefault();
        applyReaderTagFilter(tagLink.getAttribute("data-room-tag") || "");
      });
    });

    resetReadingPanelScroll();
  }

  function focusReadingIdentity() {
    var identity = readingBody && readingBody.querySelector(".mail-reading__identity");

    if (identity) {
      identity.setAttribute("tabindex", "-1");
      identity.focus();
    }
  }

  function stepReading(delta) {
    var nextIndex = readingNav.index + delta;

    if (nextIndex < 0 || nextIndex >= readingNav.ids.length) {
      return;
    }

    var artifact = artifacts.find(function (item) {
      return item.id === readingNav.ids[nextIndex];
    });

    if (!artifact || !artifact.transcription) {
      return;
    }

    readingNav.index = nextIndex;
    renderReadingContent(artifact);
    focusReadingIdentity();
  }

  function followThreadMember(artifactId, trigger) {
    var artifact = artifacts.find(function (item) {
      return item.id === artifactId;
    });

    if (!artifact || !artifact.transcription || !artifact.threadId || !readingBody) {
      return;
    }

    var memberIds = getThreadMemberIds(artifact.threadId);
    var memberIndex = memberIds.indexOf(artifactId);

    if (memberIndex === -1) {
      return;
    }

    var enteringThread = readingNav.source !== "thread";

    readingNav = {
      source: "thread",
      ids: memberIds,
      index: memberIndex,
    };
    activeThreadId = artifact.threadId;

    renderReadingContent(artifact);

    if (enteringThread && readingNavStatus) {
      readingNavStatus.textContent =
        "Reading within thread. Previous and Next move through thread correspondence.";
    }

    focusReadingIdentity();
  }

  function openReading(artifactId, trigger) {
    var artifact = artifacts.find(function (item) {
      return item.id === artifactId;
    });

    if (!artifact || !artifact.transcription || !readingDialog || !readingBody) {
      return;
    }

    lastTrigger = trigger || document.activeElement;
    scrollRestore = window.scrollY;

    var archiveIds = matchingArtifacts().map(function (item) {
      return item.id;
    });

    readingNav = {
      source: "archive",
      ids: archiveIds,
      index: archiveIds.indexOf(artifactId),
    };
    activeThreadId = null;

    renderReadingContent(artifact);

    if (typeof readingDialog.showModal === "function") {
      readingDialog.showModal();
    }

    resetReadingPanelScroll();

    var returnButton = readingToolbar && readingToolbar.querySelector(".mail-reading__return");

    if (returnButton) {
      returnButton.focus();
    }
  }

  function closeReading() {
    if (!readingDialog || !readingDialog.open) {
      return;
    }

    readingDialog.close();
    readingNav = { source: "archive", ids: [], index: -1 };
    activeThreadId = null;

    if (readingNavStatus) {
      readingNavStatus.textContent = "";
    }

    window.scrollTo(0, scrollRestore);

    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
    }
  }

  function updateHeroProgress() {
    if (!hero || reducedMotion) {
      return;
    }

    /*
     * Same root cause as the P.S. white-gap fix: progress used
     * hero.offsetTop + a span across the tall .mail-room-hero__track,
     * so early scroll felt dead and the empty track showed as a blank
     * band. Track is collapsed in CSS; drive blur/scale/kicker from
     * scrollY = 0 across the sticky stage height.
     */
    var sticky = hero.querySelector(".mail-room-hero__sticky");
    var stageH = sticky ? sticky.offsetHeight : hero.offsetHeight;
    var end = Math.max(stageH * 0.65, window.innerHeight * 0.22, 200);
    var linear = Math.min(Math.max(window.scrollY / end, 0), 1);
    var progress = Math.min(Math.max(Math.pow(linear, 0.6), 0), 1);

    hero.style.setProperty("--hero-scale", String(1 + progress * 0.065));
    hero.style.setProperty("--hero-blur", progress * 8 + "px");
    hero.style.setProperty("--hero-kicker-opacity", String(Math.min(progress * 1.35, 1)));
  }

  function bindEvents() {
    if (searchInput) {
      searchInput.value = state.q;

      searchInput.addEventListener(
        "input",
        core.debounce(function () {
          var era = state.era;
          state = Object.assign(
            core.normalizeBrowseState({
              q: searchInput.value,
              tags: state.tags,
            }),
            { era: era }
          );

          if (isMailFilterActive()) {
            revealedCount = artifacts.length;
          } else {
            revealedCount = INITIAL_BATCH;
          }

          renderArtifacts();
          applyBrowse({ historyMode: "replace" });
        }, 180)
      );

      searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
          clearFilters();
        }
      });
    }

    if (clearButton) {
      clearButton.addEventListener("click", clearFilters);
    }

    if (loadMoreButton) {
      loadMoreButton.addEventListener("click", function () {
        revealedCount += LOAD_BATCH;
        applyBrowse({ historyMode: "none" });
      });
    }

    function scrollEnvelopeRail(direction) {
      if (!fieldGrid) {
        return;
      }
      var amount = Math.max(fieldGrid.clientWidth * 0.72, 220);
      fieldGrid.scrollBy({
        left: direction * amount,
        behavior: reducedMotion ? "auto" : "smooth",
      });
    }

    var prevEnvelope = document.getElementById("mail-envelope-prev");
    var nextEnvelope = document.getElementById("mail-envelope-next");
    if (prevEnvelope) {
      prevEnvelope.addEventListener("click", function () {
        scrollEnvelopeRail(-1);
      });
    }
    if (nextEnvelope) {
      nextEnvelope.addEventListener("click", function () {
        scrollEnvelopeRail(1);
      });
    }

    if (exploreToggle) {
      exploreToggle.addEventListener("click", function () {
        var open = exploreToggle.getAttribute("aria-expanded") !== "true";
        setExploreOpen(open);
      });
      setExploreOpen(false);
    }

    if (browseRoot) {
      browseRoot.addEventListener("click", function (event) {
        var eraButton = event.target.closest("[data-mail-era]");
        if (eraButton) {
          event.preventDefault();
          setEra(eraButton.getAttribute("data-mail-era") || "all");
          return;
        }

        var tagLink = event.target.closest(".room-discovery__tag[data-room-tag]");

        if (!tagLink) {
          return;
        }

        event.preventDefault();
        toggleExploreTag(tagLink.getAttribute("data-room-tag") || "");
      });
    }

    fieldGrid.addEventListener("click", function (event) {
      var target = event.target.closest("[data-open-artifact]");

      if (!target) {
        return;
      }

      openReading(target.getAttribute("data-open-artifact"), target);
    });

    fieldGrid.addEventListener("keydown", function (event) {
      var target = event.target.closest("[data-open-artifact]");

      if (!target) {
        return;
      }

      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      event.preventDefault();
      openReading(target.getAttribute("data-open-artifact"), target);
    });

    var searchToggle = document.getElementById("mail-browse-search-toggle");
    if (searchToggle && searchInput) {
      searchToggle.addEventListener("click", function () {
        var open = searchInput.hasAttribute("hidden");
        if (open) {
          searchInput.removeAttribute("hidden");
          searchToggle.setAttribute("aria-expanded", "true");
          searchInput.focus();
        } else {
          searchInput.setAttribute("hidden", "");
          searchToggle.setAttribute("aria-expanded", "false");
        }
      });
    }

    if (readingDialog) {
      readingNavStatus = document.createElement("div");
      readingNavStatus.id = "mail-reading-nav-status";
      readingNavStatus.className = "visually-hidden";
      readingNavStatus.setAttribute("aria-live", "polite");
      readingNavStatus.setAttribute("aria-atomic", "true");

      readingPanel = readingDialog.querySelector(".mail-reading__panel");

      if (readingPanel) {
        readingPanel.appendChild(readingNavStatus);
      }

      readingDialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        closeReading();
      });

      readingDialog.addEventListener("click", function (event) {
        if (event.target === readingDialog) {
          closeReading();
          return;
        }

        if (event.target.closest(".mail-reading__return")) {
          closeReading();
          return;
        }

        var threadMember = event.target.closest(".mail-reading__thread-member");

        if (threadMember) {
          followThreadMember(
            threadMember.getAttribute("data-thread-member") || "",
            threadMember
          );
          return;
        }

        if (event.target.closest(".mail-reading__prev:not([disabled])")) {
          stepReading(-1);
          return;
        }

        if (event.target.closest(".mail-reading__next:not([disabled])")) {
          stepReading(1);
        }
      });
    }

    window.addEventListener("popstate", function () {
      var next = core.normalizeBrowseState(core.parseState());
      state = Object.assign({}, next, {
        era: normalizeEra(
          new URLSearchParams(window.location.search).get("era") || "all"
        ),
      });

      if (searchInput) {
        searchInput.value = state.q;
      }

      revealedCount = isMailFilterActive() ? artifacts.length : INITIAL_BATCH;
      renderArtifacts();
      applyBrowse({ historyMode: "none" });
    });

    if (!reducedMotion) {
      window.addEventListener("scroll", updateHeroProgress, { passive: true });
      window.addEventListener("resize", updateHeroProgress);
      updateHeroProgress();
    }
  }

  renderArtifacts();
  bindEvents();
  applyBrowse({ historyMode: "none" });

  if (core.isFilterActive(state)) {
    scrollToWhatArrived();
  }
})();
