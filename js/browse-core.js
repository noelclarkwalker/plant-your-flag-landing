/**
 * Shared browse/filter utilities for NoelClark.com rooms.
 * Room presentation stays in room CSS; this module handles state and matching only.
 */
(function (global) {
  "use strict";

  var FORMAT_LABELS = {
    essay: "Essay",
    photography: "Photography",
    video: "Video",
    music: "Music",
  };

  var TAG_LABELS = {
    introspection: "Introspection",
    faith: "Faith",
    healing: "Healing",
    vulnerability: "Vulnerability",
    accountability: "Accountability",
    grief: "Grief",
    family: "Family",
    memory: "Memory",
    loss: "Loss",
    curiosity: "Curiosity",
    nature: "Nature",
    digital: "Digital",
    freedom: "Freedom",
    justice: "Justice",
    slavery: "Slavery",
    abolition: "Abolition",
    "civil-war": "Civil War",
  };

  function normalizeQuery(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .replace(/\s+/g, " ");
  }

  function parseState(search) {
    var params = new URLSearchParams(search || global.location.search);

    return {
      q: params.get("q") || "",
      tag: params.get("tag") || "",
    };
  }

  function writeState(state, pathname) {
    var params = new URLSearchParams();
    var query = normalizeQuery(state.q);
    var tag = String(state.tag || "").trim();

    if (query) {
      params.set("q", query);
    }

    if (tag) {
      params.set("tag", tag);
    }

    var nextSearch = params.toString();
    var url =
      (pathname || global.location.pathname) +
      (nextSearch ? "?" + nextSearch : "") +
      global.location.hash;

    global.history.replaceState(null, "", url);
  }

  function buildSearchHaystack(item) {
    var parts = [item.title || ""];

    if (item.format && FORMAT_LABELS[item.format]) {
      parts.push(item.format, FORMAT_LABELS[item.format]);
    }

    if (item.tags && item.tags.length) {
      item.tags.forEach(function (tag) {
        parts.push(tag);

        if (TAG_LABELS[tag]) {
          parts.push(TAG_LABELS[tag]);
        }
      });
    }

    if (item.searchText) {
      parts.push(item.searchText);
    }

    return normalizeQuery(parts.join(" "));
  }

  function matchesItem(item, state) {
    var tag = String(state.tag || "").trim();
    var query = normalizeQuery(state.q);
    var hasFilter = Boolean(query || tag);

    if (tag) {
      if (!item.tags || item.tags.indexOf(tag) === -1) {
        return false;
      }
    }

    if (!query) {
      return true;
    }

    return buildSearchHaystack(item).indexOf(query) !== -1;
  }

  function isFilterActive(state) {
    return Boolean(normalizeQuery(state.q) || String(state.tag || "").trim());
  }

  function formatLabel(slug) {
    return FORMAT_LABELS[slug] || slug;
  }

  function tagLabel(slug) {
    return TAG_LABELS[slug] || slug;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function renderTagLinks(tags, roomPath, activeTag, options) {
    options = options || {};

    if (!tags || !tags.length) {
      return "";
    }

    var maxVisible = options.maxVisible || tags.length;
    var visible = tags.slice(0, maxVisible);
    var variant = options.variant || "list";

    if (variant === "notation" || variant === "discovery") {
      return renderTagNotation(visible, roomPath, activeTag, options);
    }

    var links = visible
      .map(function (tag) {
        var href = roomPath + "?tag=" + encodeURIComponent(tag);
        var label = escapeHtml(tagLabel(tag));
        var current = activeTag === tag ? ' aria-current="true"' : "";

        return (
          '<li><a class="room-tag" href="' +
          href +
          '" data-room-tag="' +
          escapeHtml(tag) +
          '"' +
          current +
          ">" +
          label +
          "</a></li>"
        );
      })
      .join("");

    return '<ul class="room-tags" aria-label="Tags">' + links + "</ul>";
  }

  function renderTagNotation(tags, roomPath, activeTag, options) {
    options = options || {};

    if (!tags || !tags.length) {
      return "";
    }

    var maxVisible = options.maxVisible || tags.length;
    var visible = tags.slice(0, maxVisible);
    var variant = options.variant || "notation";
    var showLabel = options.showLabel !== false;
    var labelText = options.labelText || "Tags";
    var links = visible
      .map(function (tag, index) {
        var separator =
          index > 0
            ? '<span class="room-notation__sep" aria-hidden="true"> · </span>'
            : "";
        var href = roomPath + "?tag=" + encodeURIComponent(tag);
        var label = escapeHtml(tagLabel(tag));
        var current = activeTag === tag ? ' aria-current="true"' : "";

        return (
          separator +
          '<a class="room-notation__tag" href="' +
          href +
          '" data-room-tag="' +
          escapeHtml(tag) +
          '"' +
          current +
          ">" +
          label +
          "</a>"
        );
      })
      .join("");

    var labelMarkup = showLabel
      ? '<span class="room-notation__label">' + escapeHtml(labelText) + "</span>"
      : "";

    return (
      '<p class="room-notation room-notation--' +
      escapeHtml(variant) +
      '" role="note">' +
      labelMarkup +
      '<span class="room-notation__tags">' +
      links +
      "</span></p>"
    );
  }

  function renderFormatNotation(format, options) {
    options = options || {};

    if (!format) {
      return "";
    }

    var labelText = options.labelText || "Format";
    var value = formatLabel(format);

    return (
      '<p class="room-notation room-notation--format" role="note">' +
      '<span class="room-notation__label">' +
      escapeHtml(labelText) +
      "</span>" +
      '<span class="room-notation__value">' +
      escapeHtml(value) +
      "</span></p>"
    );
  }

  function collectTagsFromItems(items) {
    var seen = {};
    var collected = [];

    (items || []).forEach(function (item) {
      (item.tags || []).forEach(function (tag) {
        if (!seen[tag]) {
          seen[tag] = true;
          collected.push(tag);
        }
      });
    });

    collected.sort(function (a, b) {
      return tagLabel(a).localeCompare(tagLabel(b));
    });

    return collected;
  }

  function renderDiscoveryTags(items, roomPath, activeTag, options) {
    options = options || {};

    var tags = collectTagsFromItems(items);
    var maxVisible = options.maxVisible || 8;
    var labelText = options.labelText || "Explore";

    if (!tags.length) {
      return "";
    }

    var links = tags.slice(0, maxVisible)
      .map(function (tag) {
        var href = roomPath + "?tag=" + encodeURIComponent(tag);
        var label = escapeHtml(tagLabel(tag));
        var current = activeTag === tag ? ' aria-current="true"' : "";

        return (
          '<a class="room-discovery__tag" href="' +
          href +
          '" data-room-tag="' +
          escapeHtml(tag) +
          '"' +
          current +
          ">" +
          label +
          "</a>"
        );
      })
      .join("");

    return (
      '<div class="room-discovery" aria-label="' +
      escapeHtml(labelText) +
      '">' +
      '<p class="room-discovery__label">' +
      escapeHtml(labelText) +
      "</p>" +
      '<div class="room-discovery__tags">' +
      links +
      "</div></div>"
    );
  }

  function debounce(fn, wait) {
    var timer = null;

    return function () {
      var context = this;
      var args = arguments;

      if (timer !== null) {
        clearTimeout(timer);
      }

      timer = setTimeout(function () {
        timer = null;
        fn.apply(context, args);
      }, wait);
    };
  }

  function shouldUseLoadMore(totalCount, minCount) {
    return totalCount > minCount;
  }

  global.BrowseCore = {
    FORMAT_LABELS: FORMAT_LABELS,
    TAG_LABELS: TAG_LABELS,
    normalizeQuery: normalizeQuery,
    parseState: parseState,
    writeState: writeState,
    buildSearchHaystack: buildSearchHaystack,
    matchesItem: matchesItem,
    isFilterActive: isFilterActive,
    formatLabel: formatLabel,
    tagLabel: tagLabel,
    escapeHtml: escapeHtml,
    renderTagLinks: renderTagLinks,
    renderTagNotation: renderTagNotation,
    renderFormatNotation: renderFormatNotation,
    collectTagsFromItems: collectTagsFromItems,
    renderDiscoveryTags: renderDiscoveryTags,
    debounce: debounce,
    shouldUseLoadMore: shouldUseLoadMore,
  };
})(window);
