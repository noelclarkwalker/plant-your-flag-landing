(function () {
  "use strict";

  var core = window.BrowseCore;
  var works = window.PS_WORKS || [];
  var ROOM_PATH = "ps.html";
  var TAG_LIMIT = (window.PS_BROWSE && window.PS_BROWSE.WORK_TAG_LIMIT) || 4;

  if (!core || !works.length) {
    return;
  }

  function findWorkId() {
    var mount = document.querySelector("[data-ps-tags]");

    if (mount && mount.getAttribute("data-ps-work-id")) {
      return mount.getAttribute("data-ps-work-id");
    }

    var main = document.querySelector("main[id^='ps-work-'], main[id^='ps-preview-']");

    if (!main) {
      return null;
    }

    var byMain = works.find(function (item) {
      return item.id === main.id;
    });

    if (byMain) {
      return byMain.id;
    }

    return main.id;
  }

  function findNotationHost() {
    var mount = document.querySelector("[data-ps-tags]");

    if (mount) {
      return mount;
    }

    var main = document.querySelector("main.ps-work");

    if (!main) {
      return null;
    }

    var host =
      main.querySelector(".ps-work__essay, .ps-work__photo, .ps-work__song, .ps-work__motion") ||
      main;

    mount = document.createElement("div");
    mount.className = "ps-work__notation";
    mount.setAttribute("data-ps-tags", "");
    host.appendChild(mount);

    return mount;
  }

  function renderWorkNotation() {
    var mount = findNotationHost();

    if (!mount) {
      return;
    }

    var workId = mount.getAttribute("data-ps-work-id") || findWorkId();

    if (!workId) {
      mount.hidden = true;
      return;
    }

    mount.setAttribute("data-ps-work-id", workId);

    var work = works.find(function (item) {
      return item.id === workId;
    });

    if (!work) {
      mount.hidden = true;
      return;
    }

    if (work.tags && work.tags.length) {
      mount.innerHTML = core.renderTagNotation(work.tags, ROOM_PATH, "", {
        variant: "notation",
        maxVisible: TAG_LIMIT,
        showLabel: true,
        labelText: "Tags",
      });
      mount.hidden = false;
      return;
    }

    if (work.format) {
      mount.innerHTML = core.renderFormatNotation(work.format, {
        labelText: "Format",
      });
      mount.hidden = false;
      return;
    }

    mount.hidden = true;
  }

  renderWorkNotation();
})();
