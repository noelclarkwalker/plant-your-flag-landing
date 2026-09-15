(function () {
  "use strict";

  var core = window.BrowseCore;
  var artifacts = window.MAIL_ROOM_ARTIFACTS || [];
  var threads = window.MAIL_ROOM_THREADS || {};

  var INITIAL_BATCH = 6;
  var LOAD_BATCH = 5;
  var MIN_FOR_LOAD_MORE = 7;
  var ROOM_PATH =
    (window.noelclarkV1MailRoom && noelclarkV1MailRoom.roomPath) ||
    window.location.pathname;


  var browseRoot = document.getElementById("mail-browse");
  var discoveryMount = document.getElementById("mail-browse-discovery");
  var DISCOVERY_TAG_LIMIT = 10;
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

  var state = core ? core.normalizeBrowseState(core.parseState()) : { q: "", tags: [], tag: "" };
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
    if (!artifact.tags || !artifact.tags.length) {
      return "";
    }

    return (
      '<footer class="mail-reading__notation">' +
      core.renderTagNotation(artifact.tags, ROOM_PATH, [], {
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

  function renderDiscovery() {
    if (!discoveryMount) {
      return;
    }

    discoveryMount.innerHTML = core.renderDiscoveryTags(
      artifacts.map(artifactSearchItem),
      ROOM_PATH,
      state.tags,
      {
        maxVisible: DISCOVERY_TAG_LIMIT,
        labelText: "Explore",
        multiSelect: true,
      }
    );
  }

  function renderArtifacts() {
    fieldGrid.innerHTML = artifacts
      .map(function (artifact) {
        var excerptNote = artifact.excerpt
          ? '<p class="mail-artifact__note">' +
            escapeHtml(artifact.excerptLabel || "Excerpt") +
            "</p>"
          : "";
        var readControl = artifact.transcription
          ? '<button type="button" class="mail-artifact__open" data-open-artifact="' +
            escapeHtml(artifact.id) +
            '">Read →</button>'
          : "";
        var groupAttr = artifact.group
          ? ' data-group="' + escapeHtml(artifact.group) + '"'
          : "";

        return (
          '<article class="mail-artifact mail-artifact--' +
          escapeHtml(artifact.placement) +
          '" id="artifact-' +
          escapeHtml(artifact.id) +
          '" data-artifact-id="' +
          escapeHtml(artifact.id) +
          '"' +
          groupAttr +
          ">" +
          '<p class="mail-artifact__route">' +
          formatRoute(artifact.from, artifact.to) +
          "</p>" +
          '<div class="mail-artifact__meta">' +
          '<p class="mail-artifact__date">' +
          escapeHtml(artifact.date) +
          "</p>" +
          excerptNote +
          "</div>" +
          readControl +
          "</article>"
        );
      })
      .join("");
  }

  function matchingArtifacts() {
    return artifacts.filter(function (artifact) {
      return core.matchesItem(artifactSearchItem(artifact), state);
    });
  }

  function describeActiveFilter() {
    var parts = [];
    var query = core.normalizeQuery(state.q);
    var tags = core.getStateTags(state);

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

    var active = core.isFilterActive(state);

    activeBar.hidden = !active;

    if (active) {
      activeText.textContent = describeActiveFilter();
    }
  }

  function applyBrowse(options) {
    options = options || {};
    var historyMode = options.historyMode || "none";

    state = core.normalizeBrowseState(state);

    var filterActive = core.isFilterActive(state);
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
    updateActiveBar();

    if (historyMode !== "none") {
      core.writeState(state, ROOM_PATH, historyMode);
    }
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

  function clearFilters() {
    state = { q: "", tags: [], tag: "" };
    revealedCount = INITIAL_BATCH;

    if (searchInput) {
      searchInput.value = "";
    }

    renderArtifacts();
    applyBrowse({ historyMode: "push" });
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

    state = core.normalizeBrowseState({ q: state.q, tags: tags });
    revealedCount = artifacts.length;
    renderArtifacts();
    applyBrowse({ historyMode: "push" });
  }

  function applyReaderTagFilter(tag) {
    var slug = String(tag || "").trim();

    if (!slug) {
      return;
    }

    closeReading();
    state = core.normalizeBrowseState({ q: "", tags: [slug] });
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

    var start = hero.offsetTop;
    var end = start + hero.offsetHeight - window.innerHeight * 0.35;
    var scrollSpan = Math.max(end - start, 1);
    var progress = (window.scrollY - start) / scrollSpan;

    progress = Math.min(Math.max(progress, 0), 1);

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
          state = core.normalizeBrowseState({
            q: searchInput.value,
            tags: state.tags,
          });

          if (core.isFilterActive(state)) {
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

    if (browseRoot) {
      browseRoot.addEventListener("click", function (event) {
        var tagLink = event.target.closest(".room-discovery__tag[data-room-tag]");

        if (!tagLink) {
          return;
        }

        event.preventDefault();
        toggleExploreTag(tagLink.getAttribute("data-room-tag") || "");
      });
    }

    fieldGrid.addEventListener("click", function (event) {
      var button = event.target.closest("[data-open-artifact]");

      if (!button) {
        return;
      }

      openReading(button.getAttribute("data-open-artifact"), button);
    });

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
      state = core.normalizeBrowseState(core.parseState());

      if (searchInput) {
        searchInput.value = state.q;
      }

      revealedCount = core.isFilterActive(state) ? artifacts.length : INITIAL_BATCH;
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
