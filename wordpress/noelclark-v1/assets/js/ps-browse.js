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
  var clearFiltersButton = document.getElementById("ps-browse-clear-filters");
  var discoveryMount = document.getElementById("ps-browse-discovery");
  var exploreToggle = document.getElementById("ps-explore-toggle");
  var emptyState = document.getElementById("ps-browse-empty");
  var loadMoreButton = document.getElementById("ps-browse-more");
  var membersDialog = document.getElementById("ps-members-note");
  var membersClose = document.querySelector(".ps-members-note__close");
  var searchForm = document.getElementById("ps-browse-search");
  var EXCLUDED_SUBJECT_TAGS = { humanity: true };
  var DISCOVERY_TAG_LIMIT = 12;

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

  if (!collection || !core) {
    return;
  }

  function visitorFacingTags(tags) {
    return (tags || []).filter(function (tag) {
      return !EXCLUDED_SUBJECT_TAGS[String(tag || "").toLowerCase()];
    });
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
    var browse = core.normalizeBrowseState({
      q: q,
      tag: params.get("tag") || "",
      tags: (params.get("tags") || "")
        .split(",")
        .map(function (t) {
          return t.trim();
        })
        .filter(Boolean),
    });
    var hashMedium = hash.split("/")[0];

    if (hashMedium === "writing" || hashMedium === "music" || hashMedium === "art") {
      medium = hashMedium;
    }

    if (["writing", "music", "art", "all"].indexOf(medium) === -1) {
      medium = "all";
    }

    return {
      q: browse.q,
      tags: visitorFacingTags(core.getStateTags(browse)),
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

    var tags = visitorFacingTags(core.getStateTags(state));
    url.searchParams.delete("tag");
    url.searchParams.delete("tags");
    if (tags.length === 1) url.searchParams.set("tag", tags[0]);
    else if (tags.length > 1) url.searchParams.set("tags", tags.join(","));

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
          tags: visitorFacingTags(work.tags),
          searchText: work.searchText,
          categories: work.categories,
        },
        { q: state.q, tags: state.tags, tag: state.tag }
      );
    });
  }

  function describeActiveFilter() {
    var parts = [];
    var tags = visitorFacingTags(core.getStateTags(state));
    if (core.normalizeQuery(state.q)) {
      parts.push('Search: "' + core.escapeHtml(state.q.trim()) + '"');
    }
    if (state.medium && state.medium !== "all") {
      parts.push(state.medium.charAt(0).toUpperCase() + state.medium.slice(1));
    }
    if (state.category && state.category !== "All") {
      parts.push(core.escapeHtml(state.category));
    }
    if (tags.length) {
      parts.push(
        "Tag: " +
          tags
            .map(function (tag) {
              return core.escapeHtml(core.tagLabel(tag));
            })
            .join(", ")
      );
    }
    return parts.join(" · ");
  }

  function isPsFilterActive() {
    return !!(
      core.normalizeQuery(state.q) ||
      (state.medium && state.medium !== "all") ||
      (state.category && state.category !== "All") ||
      visitorFacingTags(core.getStateTags(state)).length
    );
  }

  function updateActiveBar() {
    if (!activeBar || !activeText) return;
    var active = isPsFilterActive();
    activeBar.hidden = !active;
    if (active) activeText.textContent = describeActiveFilter();
  }

  function renderDiscovery() {
    if (!discoveryMount) return;
    var items = works.map(function (work) {
      return {
        title: work.title,
        tags: visitorFacingTags(work.tags),
        searchText: work.searchText || "",
      };
    });
    var html = core.renderDiscoveryTags(items, ROOM_PATH, state.tags, {
      maxVisible: DISCOVERY_TAG_LIMIT,
      labelText: "Subject",
      multiSelect: true,
    });
    discoveryMount.innerHTML = html
      ? html.replace(/<p class="room-discovery__label">[\s\S]*?<\/p>/, "")
      : "";
    discoveryMount.querySelectorAll(".room-discovery__tag[data-room-tag]").forEach(function (link) {
      var tag = link.getAttribute("data-room-tag") || "";
      var selected = core.getStateTags(state).indexOf(tag) !== -1;
      link.setAttribute("aria-pressed", selected ? "true" : "false");
      link.removeAttribute("aria-current");
    });
  }

  function setExploreOpen(open) {
    if (!exploreToggle || !discoveryMount) return;
    exploreToggle.setAttribute("aria-expanded", open ? "true" : "false");
    discoveryMount.hidden = !open;
  }

  function toggleExploreTag(tag) {
    var slug = String(tag || "").trim();
    if (!slug || EXCLUDED_SUBJECT_TAGS[slug.toLowerCase()]) return;
    var tags = visitorFacingTags(core.getStateTags(state)).slice();
    var index = tags.indexOf(slug);
    if (index === -1) tags.push(slug);
    else tags.splice(index, 1);
    state.tags = tags;
    state.tag = "";
    revealedCount = works.length;
    applyBrowse({ historyMode: "push" });
  }

  function syncSearchChrome() {
    if (!searchForm) return;
    searchForm.classList.toggle("has", !!core.normalizeQuery(state.q));
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

    renderDiscovery();
    syncSearchChrome();
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
    return isPsFilterActive() ? works.length : INITIAL_BATCH;
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
    if (clearFiltersButton) clearFiltersButton.addEventListener("click", clearFilters);

    if (exploreToggle) {
      exploreToggle.addEventListener("click", function () {
        setExploreOpen(exploreToggle.getAttribute("aria-expanded") !== "true");
      });
      setExploreOpen(false);
    }

    if (browseRoot) {
      browseRoot.addEventListener("click", function (event) {
        var tagLink = event.target.closest(".room-discovery__tag[data-room-tag]");
        if (!tagLink) return;
        event.preventDefault();
        toggleExploreTag(tagLink.getAttribute("data-room-tag") || "");
      });
    }

    if (loadMoreButton) {
      loadMoreButton.addEventListener("click", function () {
        revealedCount += LOAD_BATCH;
        applyBrowse({ historyMode: "none" });
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
      state.tags = next.tags || [];
      state.tag = "";
      if (searchInput) searchInput.value = state.q;
      revealedCount = filterRevealed();
      applyBrowse({ historyMode: "none" });
    });

    /* Trail Writing / Music / Art narrows the archive; P.S. restores All. */
    window.addEventListener("ps-archive-medium", function (event) {
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
  state.tags = parsed.tags || [];
  state.tag = "";

  indexWorks();
  bindEvents();
  applyBrowse({ historyMode: "none" });

  window.PS_BROWSE = {
    setMedium: setMedium,
    setCategory: setCategory,
    clearFilters: clearFilters,
  };
})();
