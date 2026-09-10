(function () {
  "use strict";

  var core = window.BrowseCore;
  var works = window.PS_WORKS || [];

  var INITIAL_BATCH = 4;
  var LOAD_BATCH = 4;
  var MIN_FOR_LOAD_MORE = 6;
  var ROOM_PATH = "ps.html";
  var DISCOVERY_TAG_LIMIT = 8;
  var WORK_TAG_LIMIT = 4;

  var collection = document.querySelector(".ps-collection");
  var browseRoot = document.getElementById("ps-browse");
  var searchInput = document.getElementById("ps-browse-query");
  var activeBar = document.getElementById("ps-browse-active");
  var activeText = document.getElementById("ps-browse-active-text");
  var clearButton = document.getElementById("ps-browse-clear");
  var emptyState = document.getElementById("ps-browse-empty");
  var loadMoreButton = document.getElementById("ps-browse-more");
  var discoveryMount = document.getElementById("ps-browse-discovery");
  var membersDialog = document.getElementById("ps-members-note");
  var membersClose = document.querySelector(".ps-members-note__close");

  var state = core.parseState();
  var revealedCount = INITIAL_BATCH;
  var elementMap = {};

  if (!collection || !core || !works.length) {
    return;
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
    works.forEach(function (work) {
      var node = document.getElementById(work.id);

      if (node) {
        node.setAttribute("data-work-id", work.id);
        elementMap[work.id] = node;
        applyMemberPrototype(work, node);
      }
    });
  }

  function applyMemberPrototype(work, node) {
    if (work.access !== "members") {
      return;
    }

    var primary = node.querySelector(".ps-preview__primary");

    if (!primary) {
      return;
    }

    var existing = primary.querySelector(
      ".ps-preview__read, .ps-preview__view, .ps-preview__watch, .ps-preview__listen, .ps-preview__access"
    );

    if (existing) {
      existing.remove();
    }

    var button = document.createElement("button");
    button.type = "button";
    button.className = "ps-preview__access ps-preview__access--members";
    button.setAttribute("data-members-prototype", work.id);
    button.innerHTML = '<span class="ps-preview__access-label">Members only</span>';

    primary.appendChild(button);
  }

  function renderDiscovery() {
    if (!discoveryMount) {
      return;
    }

    discoveryMount.innerHTML = core.renderDiscoveryTags(works, ROOM_PATH, state.tag, {
      maxVisible: DISCOVERY_TAG_LIMIT,
      labelText: "Explore",
    });
  }

  function matchingWorks() {
    return works.filter(function (work) {
      return core.matchesItem(work, state);
    });
  }

  function describeActiveFilter() {
    var parts = [];
    var query = core.normalizeQuery(state.q);
    var tag = String(state.tag || "").trim();

    if (query) {
      parts.push('Search: "' + core.escapeHtml(state.q.trim()) + '"');
    }

    if (tag) {
      parts.push("Tag: " + core.escapeHtml(core.tagLabel(tag)));
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

  function updateTagCurrentStates() {
    (browseRoot || document).querySelectorAll("[data-room-tag]").forEach(function (link) {
      if (link.getAttribute("data-room-tag") === state.tag) {
        link.setAttribute("aria-current", "true");
      } else {
        link.removeAttribute("aria-current");
      }
    });
  }

  function applyBrowse() {
    var filterActive = core.isFilterActive(state);
    var matches = matchingWorks();
    var matchIds = matches.map(function (work) {
      return work.id;
    });
    var matchCount = matches.length;
    var visibleCap = filterActive ? matchCount : revealedCount;
    var visibleShown = 0;
    var anyVisible = false;

    works.forEach(function (work) {
      var node = getWorkElement(work.id);

      if (!node) {
        return;
      }

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
      var poolCount = filterActive ? matchCount : works.length;
      var useLoadMore = core.shouldUseLoadMore(poolCount, MIN_FOR_LOAD_MORE);
      var shown = filterActive ? visibleShown : revealedCount;
      var remaining = poolCount - shown;

      loadMoreButton.hidden = filterActive || !useLoadMore || remaining <= 0;
    }

    var anySuppressed = works.some(function (work) {
      var node = getWorkElement(work.id);
      return node && node.hidden;
    });

    collection.classList.toggle("ps-collection--browse-narrowed", anySuppressed);

    renderDiscovery();
    updateActiveBar();
    updateTagCurrentStates();
    core.writeState(state, ROOM_PATH);
  }

  function browseUrlFromState() {
    var params = new URLSearchParams();
    var query = core.normalizeQuery(state.q);
    var tag = String(state.tag || "").trim();

    if (query) {
      params.set("q", query);
    }

    if (tag) {
      params.set("tag", tag);
    }

    var nextSearch = params.toString();

    return ROOM_PATH + (nextSearch ? "?" + nextSearch : "") + window.location.hash;
  }

  function pushTagFilterHistory() {
    window.history.pushState(null, "", browseUrlFromState());
  }

  function applyTagFilter(tag) {
    state.tag = tag || "";
    revealedCount = works.length;
    pushTagFilterHistory();
    applyBrowse();
  }

  function clearFilters() {
    state = { q: "", tag: "" };
    revealedCount = INITIAL_BATCH;

    if (searchInput) {
      searchInput.value = "";
    }

    applyBrowse();
  }

  function openMembersNote(workId) {
    if (!membersDialog) {
      return;
    }

    var work = getWorkById(workId);
    var title = document.querySelector(".ps-members-note__work");

    if (title && work) {
      title.textContent = work.title;
    }

    if (typeof membersDialog.showModal === "function") {
      membersDialog.showModal();
    }

    if (membersClose) {
      membersClose.focus();
    }
  }

  function closeMembersNote() {
    if (membersDialog && membersDialog.open) {
      membersDialog.close();
    }
  }

  function bindEvents() {
    if (searchInput) {
      searchInput.value = state.q;

      searchInput.addEventListener(
        "input",
        core.debounce(function () {
          state.q = searchInput.value;

          if (core.isFilterActive(state)) {
            revealedCount = works.length;
          } else {
            revealedCount = INITIAL_BATCH;
          }

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

        if (tagLink) {
          event.preventDefault();
          applyTagFilter(tagLink.getAttribute("data-room-tag") || "");
        }
      });
    }

    collection.addEventListener("click", function (event) {
      var tagLink = event.target.closest("[data-room-tag]");

      if (tagLink) {
        event.preventDefault();
        applyTagFilter(tagLink.getAttribute("data-room-tag") || "");
        return;
      }

      var memberTrigger = event.target.closest("[data-members-prototype]");

      if (memberTrigger) {
        event.preventDefault();
        openMembersNote(memberTrigger.getAttribute("data-members-prototype"));
      }
    });

    if (membersClose) {
      membersClose.addEventListener("click", closeMembersNote);
    }

    if (membersDialog) {
      membersDialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        closeMembersNote();
      });

      membersDialog.addEventListener("click", function (event) {
        if (event.target === membersDialog) {
          closeMembersNote();
        }
      });
    }

    window.addEventListener("popstate", function () {
      state = core.parseState();

      if (searchInput) {
        searchInput.value = state.q;
      }

      revealedCount = core.isFilterActive(state) ? works.length : INITIAL_BATCH;
      applyBrowse();
    });
  }

  indexWorks();
  bindEvents();
  applyBrowse();

  global.PS_BROWSE = {
    WORK_TAG_LIMIT: WORK_TAG_LIMIT,
  };
})();
