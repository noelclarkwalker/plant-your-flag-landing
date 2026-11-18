/**
 * P.S. unified archive browse — medium + journey category filters.
 * DOM articles from PHP are the source of truth; PS_WORKS supplies
 * historical thematic tags for search only.
 */
(function () {
  "use strict";

  var core = window.BrowseCore;
  var registry = window.PS_WORKS || [];

  var INITIAL_BATCH = 12;
  var LOAD_BATCH = 8;
  var MIN_FOR_LOAD_MORE = 14;
  var ROOM_PATH =
    (window.noelclarkV1Ps && noelclarkV1Ps.roomPath) ||
    window.location.pathname;

  var collection = document.getElementById("ps-archive-collection") || document.querySelector(".ps-collection");
  var browseRoot = document.getElementById("ps-browse");
  var searchInput = document.getElementById("ps-browse-query");
  var activeBar = document.getElementById("ps-browse-active");
  var activeText = document.getElementById("ps-browse-active-text");
  var clearButton = document.getElementById("ps-browse-clear");
  var emptyState = document.getElementById("ps-browse-empty");
  var loadMoreButton = document.getElementById("ps-browse-more");
  var membersDialog = document.getElementById("ps-members-note");
  var membersClose = document.querySelector(".ps-members-note__close");
  var mediumMount = document.getElementById("ps-archive-mediums");
  var categoryRow = document.getElementById("ps-archive-category-row");
  var categoryMount = document.getElementById("ps-archive-categories");
  var categoryMapNode = document.getElementById("ps-archive-category-map");

  var categoryMap = { writing: [], music: [], art: [] };
  if (categoryMapNode) {
    try {
      categoryMap = JSON.parse(categoryMapNode.textContent || "{}") || categoryMap;
    } catch (e) {
      /* keep defaults */
    }
  }

  var state = {
    q: "",
    medium: "all",
    category: "All",
    tags: [],
    tag: "",
  };
  var revealedCount = INITIAL_BATCH;
  var works = [];
  var elementMap = {};
  var trailSync = true;

  if (!collection || !core) {
    return;
  }

  function registryById(id) {
    return registry.find(function (work) {
      return work.id === id;
    });
  }

  function worksFromDom() {
    return Array.prototype.map
      .call(collection.querySelectorAll("[data-work-id]"), function (node) {
        var id = node.getAttribute("data-work-id") || node.id;
        var reg = registryById(id) || {};
        var titleEl = node.querySelector(".ps-preview__title");
        var link = node.querySelector(
          ".ps-preview__read, .ps-preview__view, .ps-preview__watch, .ps-preview__listen"
        );
        return {
          id: id,
          title: titleEl ? titleEl.textContent.trim() : id,
          medium: node.getAttribute("data-medium") || "",
          format: node.getAttribute("data-format") || reg.format || "",
          access: node.getAttribute("data-access") || reg.access || "public",
          categories: (node.getAttribute("data-categories") || "")
            .split(",")
            .map(function (t) {
              return t.trim();
            })
            .filter(Boolean),
          tags: reg.tags || [],
          href: link ? link.getAttribute("href") : reg.href || null,
          searchText: node.getAttribute("data-search") || reg.searchText || "",
        };
      });
  }

  function getWorkElement(id) {
    return elementMap[id] || document.getElementById(id);
  }

  function getWorkById(id) {
    return works.find(function (work) {
      return work.id === id;
    });
  }

  function indexWorks() {
    works = worksFromDom();
    works.forEach(function (work) {
      var node = document.getElementById(work.id);
      if (node) {
        node.setAttribute("data-work-id", work.id);
        elementMap[work.id] = node;
      }
    });
  }

  function parseArchiveState() {
    var params = new URLSearchParams(window.location.search);
    var hash = decodeURIComponent(window.location.hash.replace(/^#/, ""));
    var medium = params.get("medium") || "all";
    var category = params.get("category") || "All";
    var q = params.get("q") || "";
    var hashMedium = hash.split("/")[0];

    if (hashMedium === "writing" || hashMedium === "music" || hashMedium === "art") {
      medium = hashMedium;
    }

    if (["writing", "music", "art", "all"].indexOf(medium) === -1) {
      medium = "all";
    }

    return {
      q: q,
      tags: [],
      tag: "",
      medium: medium,
      category: category || "All",
    };
  }

  function writeArchiveState(historyMode) {
    if (historyMode === "none") return;
    var url = new URL(window.location.href);
    if (state.q) url.searchParams.set("q", state.q);
    else url.searchParams.delete("q");
    if (state.medium && state.medium !== "all") url.searchParams.set("medium", state.medium);
    else url.searchParams.delete("medium");
    if (state.category && state.category !== "All") url.searchParams.set("category", state.category);
    else url.searchParams.delete("category");

    var method = historyMode === "push" ? "pushState" : "replaceState";
    window.history[method]({}, "", url.pathname + url.search + url.hash);
  }

  function matchingWorks() {
    return works.filter(function (work) {
      if (state.medium && state.medium !== "all" && work.medium !== state.medium) {
        return false;
      }
      if (state.category && state.category !== "All") {
        if ((work.categories || []).indexOf(state.category) === -1) {
          return false;
        }
      }
      return core.matchesItem(
        {
          title: work.title,
          format: work.format,
          tags: work.tags,
          searchText: work.searchText,
          categories: work.categories,
        },
        { q: state.q, tags: state.tags, tag: state.tag }
      );
    });
  }

  function describeActiveFilter() {
    var parts = [];
    if (core.normalizeQuery(state.q)) {
      parts.push('Search: "' + core.escapeHtml(state.q.trim()) + '"');
    }
    if (state.medium && state.medium !== "all") {
      parts.push(state.medium.charAt(0).toUpperCase() + state.medium.slice(1));
    }
    if (state.category && state.category !== "All") {
      parts.push(core.escapeHtml(state.category));
    }
    return parts.join(" · ");
  }

  function updateActiveBar() {
    if (!activeBar || !activeText) return;
    var active = !!(
      core.normalizeQuery(state.q) ||
      (state.medium && state.medium !== "all") ||
      (state.category && state.category !== "All")
    );
    activeBar.hidden = !active;
    if (active) activeText.textContent = describeActiveFilter();
  }

  function renderCategoryChips() {
    if (!categoryMount || !categoryRow) return;

    var medium = state.medium;
    if (!medium || medium === "all") {
      categoryRow.hidden = true;
      categoryMount.innerHTML = "";
      return;
    }

    var labels = ["All"].concat(categoryMap[medium] || []);
    categoryRow.hidden = false;
    categoryMount.innerHTML = labels
      .map(function (label) {
        return (
          '<button type="button" class="ps-archive-chip" data-archive-category="' +
          core.escapeHtml(label) +
          '" aria-pressed="' +
          (label === state.category ? "true" : "false") +
          '">' +
          core.escapeHtml(label) +
          "</button>"
        );
      })
      .join("");
  }

  function updateMediumChips() {
    if (!mediumMount) return;
    mediumMount.querySelectorAll("[data-archive-medium]").forEach(function (btn) {
      var value = btn.getAttribute("data-archive-medium") || "all";
      btn.setAttribute("aria-pressed", value === state.medium ? "true" : "false");
    });
  }

  function applyBrowse(options) {
    options = options || {};
    var historyMode = options.historyMode || "none";

    if (state.medium === "all") {
      state.category = "All";
    }

    var matches = matchingWorks();
    var matchIds = matches.map(function (work) {
      return work.id;
    });
    var filterActive = !!(
      core.normalizeQuery(state.q) ||
      (state.medium && state.medium !== "all") ||
      (state.category && state.category !== "All")
    );
    var visibleCap = filterActive ? matches.length : revealedCount;
    var visibleShown = 0;
    var anyVisible = false;

    works.forEach(function (work) {
      var node = getWorkElement(work.id);
      if (!node) return;
      var isMatch = matchIds.indexOf(work.id) !== -1;
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
      var poolCount = filterActive ? matches.length : works.length;
      var useLoadMore = core.shouldUseLoadMore(poolCount, MIN_FOR_LOAD_MORE);
      var shown = filterActive ? visibleShown : revealedCount;
      loadMoreButton.hidden = filterActive || !useLoadMore || poolCount - shown <= 0;
    }

    var anySuppressed = works.some(function (work) {
      var node = getWorkElement(work.id);
      return node && node.hidden;
    });
    collection.classList.toggle("ps-collection--browse-narrowed", anySuppressed);

    updateMediumChips();
    renderCategoryChips();
    updateActiveBar();
    writeArchiveState(historyMode);
  }

  function setMedium(medium, historyMode) {
    state.medium = medium || "all";
    state.category = "All";
    revealedCount = filterRevealed();
    applyBrowse({ historyMode: historyMode || "push" });
  }

  function setCategory(category, historyMode) {
    state.category = category || "All";
    revealedCount = filterRevealed();
    applyBrowse({ historyMode: historyMode || "push" });
  }

  function filterRevealed() {
    return core.normalizeQuery(state.q) || state.medium !== "all" || state.category !== "All"
      ? works.length
      : INITIAL_BATCH;
  }

  function clearFilters() {
    state = { q: "", medium: "all", category: "All", tags: [], tag: "" };
    revealedCount = INITIAL_BATCH;
    if (searchInput) searchInput.value = "";
    applyBrowse({ historyMode: "push" });
  }

  function openMembersNote(workId) {
    if (!membersDialog) return;
    var work = getWorkById(workId);
    var title = document.querySelector(".ps-members-note__work");
    if (title && work) title.textContent = work.title;
    if (typeof membersDialog.showModal === "function") membersDialog.showModal();
    if (membersClose) membersClose.focus();
  }

  function closeMembersNote() {
    if (membersDialog && membersDialog.open) membersDialog.close();
  }

  function bindEvents() {
    if (searchInput) {
      searchInput.value = state.q;
      searchInput.addEventListener(
        "input",
        core.debounce(function () {
          state.q = searchInput.value;
          revealedCount = filterRevealed();
          applyBrowse({ historyMode: "replace" });
        }, 180)
      );
      searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Escape") clearFilters();
      });
    }

    if (clearButton) clearButton.addEventListener("click", clearFilters);

    if (loadMoreButton) {
      loadMoreButton.addEventListener("click", function () {
        revealedCount += LOAD_BATCH;
        applyBrowse({ historyMode: "none" });
      });
    }

    if (mediumMount) {
      mediumMount.addEventListener("click", function (event) {
        var btn = event.target.closest("[data-archive-medium]");
        if (!btn) return;
        trailSync = false;
        setMedium(btn.getAttribute("data-archive-medium") || "all", "push");
      });
    }

    if (categoryMount) {
      categoryMount.addEventListener("click", function (event) {
        var btn = event.target.closest("[data-archive-category]");
        if (!btn) return;
        setCategory(btn.getAttribute("data-archive-category") || "All", "push");
      });
    }

    collection.addEventListener("click", function (event) {
      var memberTrigger = event.target.closest("[data-members-prototype]");
      if (memberTrigger) {
        event.preventDefault();
        openMembersNote(memberTrigger.getAttribute("data-members-prototype"));
      }
    });

    if (membersClose) membersClose.addEventListener("click", closeMembersNote);
    if (membersDialog) {
      membersDialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        closeMembersNote();
      });
      membersDialog.addEventListener("click", function (event) {
        if (event.target === membersDialog) closeMembersNote();
      });
    }

    window.addEventListener("popstate", function () {
      var next = parseArchiveState();
      state.q = next.q || "";
      state.medium = next.medium || "all";
      state.category = next.category || "All";
      if (searchInput) searchInput.value = state.q;
      revealedCount = filterRevealed();
      applyBrowse({ historyMode: "none" });
    });

    /* Trail Writing / Music / Art narrows the archive; P.S. restores All. */
    window.addEventListener("ps-archive-medium", function (event) {
      if (!trailSync && event.detail && event.detail.source === "trail") {
        /* User overrode via archive chips; still accept explicit trail events. */
      }
      trailSync = true;
      var medium = (event.detail && event.detail.medium) || "all";
      if (["writing", "music", "art", "all"].indexOf(medium) === -1) medium = "all";
      state.medium = medium;
      state.category = "All";
      revealedCount = filterRevealed();
      applyBrowse({ historyMode: "replace" });
    });
  }

  /* Extend normalize if needed */
  var parsed = parseArchiveState();
  state.q = parsed.q || "";
  state.medium = parsed.medium || "all";
  state.category = parsed.category || "All";

  indexWorks();
  bindEvents();
  applyBrowse({ historyMode: "none" });

  window.PS_BROWSE = {
    setMedium: setMedium,
    setCategory: setCategory,
    clearFilters: clearFilters,
  };
})();
