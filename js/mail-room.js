(function () {
  "use strict";

  var core = window.BrowseCore;
  var artifacts = window.MAIL_ROOM_ARTIFACTS || [];

  var INITIAL_BATCH = 6;
  var LOAD_BATCH = 5;
  var MIN_FOR_LOAD_MORE = 7;
  var ROOM_PATH = "mail-room.html";

  var browseRoot = document.getElementById("mail-browse");
  var discoveryMount = document.getElementById("mail-browse-discovery");
  var DISCOVERY_TAG_LIMIT = 10;
  var hero = document.querySelector(".mail-room-hero");
  var fieldGrid = document.getElementById("mail-room-artifacts");
  var searchInput = document.getElementById("mail-browse-query");
  var activeBar = document.getElementById("mail-browse-active");
  var activeText = document.getElementById("mail-browse-active-text");
  var clearButton = document.getElementById("mail-browse-clear");
  var emptyState = document.getElementById("mail-browse-empty");
  var loadMoreButton = document.getElementById("mail-browse-more");
  var readingDialog = document.getElementById("mail-reading");
  var readingReturn = document.querySelector(".mail-reading__return");
  var readingBody = document.querySelector(".mail-reading__body");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var scrollRestore = 0;
  var lastTrigger = null;

  var state = core ? core.parseState() : { q: "", tag: "" };
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
      core.renderTagNotation(artifact.tags, ROOM_PATH, state.tag, {
        variant: "notation",
        showLabel: true,
        labelText: "Tags",
      }) +
      "</footer>"
    );
  }

  function renderDiscovery() {
    if (!discoveryMount) {
      return;
    }

    discoveryMount.innerHTML = core.renderDiscoveryTags(
      artifacts.map(artifactSearchItem),
      ROOM_PATH,
      state.tag,
      {
        maxVisible: DISCOVERY_TAG_LIMIT,
        labelText: "Explore",
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
    var tag = String(state.tag || "").trim();

    if (query) {
      parts.push('Search: "' + escapeHtml(state.q.trim()) + '"');
    }

    if (tag) {
      parts.push("Tag: " + escapeHtml(core.tagLabel(tag)));
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

  function applyBrowse() {
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
    core.writeState(state, ROOM_PATH);
  }

  function updateTagCurrentStates() {
    (browseRoot || document).querySelectorAll("[data-room-tag]").forEach(function (link) {
      if (link.getAttribute("data-room-tag") === state.tag) {
        link.setAttribute("aria-current", "true");
      } else {
        link.removeAttribute("aria-current");
      }
    });
  }

  function clearFilters() {
    state = { q: "", tag: "" };
    revealedCount = INITIAL_BATCH;

    if (searchInput) {
      searchInput.value = "";
    }

    renderArtifacts();
    applyBrowse();
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

    var excerptNote = artifact.excerpt ? " · " + (artifact.excerptLabel || "Excerpt") : "";

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
      renderArtifactReadingNotation(artifact);

    if (typeof readingDialog.showModal === "function") {
      readingDialog.showModal();
    }

    if (readingReturn) {
      readingReturn.focus();
    }
  }

  function closeReading() {
    if (!readingDialog || !readingDialog.open) {
      return;
    }

    readingDialog.close();
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
          state.q = searchInput.value;

          if (core.isFilterActive(state)) {
            revealedCount = artifacts.length;
          } else {
            revealedCount = INITIAL_BATCH;
          }

          renderArtifacts();
          applyBrowse();
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
        applyBrowse();
      });
    }

    if (browseRoot) {
      browseRoot.addEventListener("click", function (event) {
        var tagLink = event.target.closest("[data-room-tag]");

        if (!tagLink) {
          return;
        }

        event.preventDefault();
        state.tag = tagLink.getAttribute("data-room-tag") || "";
        revealedCount = artifacts.length;
        renderArtifacts();
        applyBrowse();
      });
    }

    fieldGrid.addEventListener("click", function (event) {
      var tagLink = event.target.closest("[data-room-tag]");

      if (tagLink) {
        event.preventDefault();
        state.tag = tagLink.getAttribute("data-room-tag") || "";
        revealedCount = artifacts.length;
        renderArtifacts();
        applyBrowse();
        return;
      }

      var button = event.target.closest("[data-open-artifact]");

      if (!button) {
        return;
      }

      openReading(button.getAttribute("data-open-artifact"), button);
    });

    if (readingReturn) {
      readingReturn.addEventListener("click", closeReading);
    }

    if (readingDialog) {
      readingDialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        closeReading();
      });

      readingDialog.addEventListener("click", function (event) {
        var tagLink = event.target.closest("[data-room-tag]");

        if (tagLink) {
          event.preventDefault();
          closeReading();
          state.tag = tagLink.getAttribute("data-room-tag") || "";
          revealedCount = artifacts.length;
          renderArtifacts();
          applyBrowse();
          return;
        }

        if (event.target === readingDialog) {
          closeReading();
        }
      });
    }

    window.addEventListener("popstate", function () {
      state = core.parseState();

      if (searchInput) {
        searchInput.value = state.q;
      }

      revealedCount = core.isFilterActive(state) ? artifacts.length : INITIAL_BATCH;
      renderArtifacts();
      applyBrowse();
    });

    if (!reducedMotion) {
      window.addEventListener("scroll", updateHeroProgress, { passive: true });
      window.addEventListener("resize", updateHeroProgress);
      updateHeroProgress();
    }
  }

  renderArtifacts();
  bindEvents();
  applyBrowse();
})();
