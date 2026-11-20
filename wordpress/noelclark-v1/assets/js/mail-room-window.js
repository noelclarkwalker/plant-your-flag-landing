/**
 * Mail Room window scenery — reload selection only (no ambient rotation).
 */
(function () {
  "use strict";

  var cfg = window.noelclarkV1MailRoomWindow || {};
  var scenes = Array.isArray(cfg.scenes) ? cfg.scenes.slice() : [];
  var storageKey = cfg.storageKey || "noelclark-v1-mail-room-window-last";
  var root = document.querySelector("[data-mail-room-scenery]");
  var img = root ? root.querySelector(".mail-room-stage__scenery-img") : null;

  if (!root) {
    return;
  }

  function readLast() {
    try {
      return sessionStorage.getItem(storageKey) || "";
    } catch (e) {
      return "";
    }
  }

  function writeLast(id) {
    try {
      sessionStorage.setItem(storageKey, id || "");
    } catch (e) {
      /* ignore */
    }
  }

  function focalFor(scene) {
    var mqMobile = window.matchMedia("(max-width: 700px)").matches;
    var mqTablet = window.matchMedia("(max-width: 1100px)").matches;
    var focal = scene.focal_desktop || { x: 50, y: 45 };
    if (mqMobile) focal = scene.focal_mobile || focal;
    else if (mqTablet) focal = scene.focal_tablet || focal;
    return (focal.x != null ? focal.x : 50) + "% " + (focal.y != null ? focal.y : 45) + "%";
  }

  function pickScene() {
    if (!scenes.length) return null;
    if (scenes.length === 1) return scenes[0];
    var last = readLast();
    var pool = scenes.filter(function (s) {
      return s.id !== last;
    });
    if (!pool.length) pool = scenes;
    return pool[Math.floor(Math.random() * pool.length)];
  }

  function applyScene(scene) {
    if (!scene || !scene.url) {
      root.innerHTML = '<div class="mail-room-stage__fallback" aria-hidden="true"></div>';
      writeLast("");
      return;
    }

    var node = img;
    if (!node) {
      node = document.createElement("img");
      node.className = "mail-room-stage__scenery-img";
      node.alt = "";
      node.decoding = "async";
      node.width = 1672;
      node.height = 941;
      root.innerHTML = "";
      root.appendChild(node);
      img = node;
    }

    /* Only replace if different — avoid flash when SSR already chose this scene. */
    var currentId = node.getAttribute("data-scene-id") || "";
    if (currentId && currentId === scene.id && node.getAttribute("src")) {
      node.style.objectPosition = focalFor(scene);
      writeLast(scene.id);
      return;
    }

    var next = new Image();
    next.decoding = "async";
    next.onload = function () {
      node.src = scene.url;
      node.setAttribute("data-scene-id", scene.id);
      node.style.objectPosition = focalFor(scene);
      writeLast(scene.id);
    };
    next.onerror = function () {
      root.innerHTML = '<div class="mail-room-stage__fallback" aria-hidden="true"></div>';
      writeLast("");
    };
    next.src = scene.url;
  }

  /* Navtype reload / navigate: re-pick. Back-forward keeps painted scene. */
  var nav = performance.getEntriesByType && performance.getEntriesByType("navigation")[0];
  var navType = nav ? nav.type : "navigate";
  var existingId = img && img.getAttribute("data-scene-id");

  if (navType === "back_forward" && existingId) {
    writeLast(existingId);
    return;
  }

  applyScene(pickScene());

  window.addEventListener("resize", function () {
    if (!img) return;
    var id = img.getAttribute("data-scene-id");
    var scene = scenes.find(function (s) {
      return s.id === id;
    });
    if (scene) img.style.objectPosition = focalFor(scene);
  });
})();
