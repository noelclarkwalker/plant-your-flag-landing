(function () {
  "use strict";

  var DESKTOP_MQ = window.matchMedia("(min-width: 52rem)");
  var REDUCED_MQ = window.matchMedia("(prefers-reduced-motion: reduce)");

  var BRUNO = {
    button: null,
    duo: null,
    photo: null,
    videoWrap: null,
    video: null,
  };

  var RTN = {
    button: null,
    target: null,
    variant: "rtn",
  };

  function reducedMotion() {
    return (
      REDUCED_MQ.matches ||
      document.documentElement.classList.contains("about-reduced-motion")
    );
  }

  function isDesktop() {
    return DESKTOP_MQ.matches;
  }

  function getZone() {
    return document.querySelector("[data-proof-proto]");
  }

  function getSlot(target) {
    return target ? target.closest(".about-proof-proto__slot") : null;
  }

  function higherDiscoveryOpen() {
    var essay = document.getElementById("about-proof-essay");
    var graffiti = document.getElementById("about-proof-graffiti");
    var wall = document.getElementById("about-proof-wall");

    if (essay && essay.classList.contains("is-open") && !essay.hidden) {
      return true;
    }

    if (
      (graffiti && graffiti.classList.contains("is-open") && !graffiti.hidden) ||
      (wall && wall.classList.contains("is-open") && !wall.hidden)
    ) {
      return true;
    }

    return false;
  }

  function setSlotOpen(target, open) {
    var slot = getSlot(target);

    if (!slot) {
      return;
    }

    if (open) {
      slot.classList.add("is-open");
      return;
    }

    slot.classList.remove("is-open");

    window.setTimeout(function () {
      if (!slot.classList.contains("is-open")) {
        slot.style.removeProperty("--proof-stage-height");
      }
    }, reducedMotion() ? 0 : 760);
  }

  function updateStageHeight(target) {
    var slot = getSlot(target);

    if (!slot || !target || !isDesktop()) {
      return;
    }

    var top = parseFloat(getComputedStyle(target).getPropertyValue("--proof-top")) || 0;
    var height = target.getBoundingClientRect().height;
    var stageHeight = top + height + 28;

    slot.style.setProperty("--proof-stage-height", stageHeight + "px");
  }

  function layoutDesktop(button, target) {
    var zone = getZone();
    var slot = getSlot(target);

    if (!zone || !slot || !button || !target) {
      return;
    }

    var zoneRect = zone.getBoundingClientRect();
    var slotRect = slot.getBoundingClientRect();
    var buttonRect = button.getBoundingClientRect();

    var top = Math.max(8, buttonRect.bottom - slotRect.top + 14);
    var left = Math.min(zoneRect.width * 0.3, 12 * 16);
    var width = Math.min(window.innerWidth * 0.44, zoneRect.width - left - 24);

    target.style.setProperty("--proof-top", top + "px");
    target.style.setProperty("--proof-left", left + "px");
    target.style.setProperty("--proof-width", Math.max(width, 18 * 16) + "px");
    target.style.setProperty(
      "--proof-enter-x",
      Math.min(window.innerWidth * 0.12, 11 * 16) + "px"
    );
    target.style.setProperty("--proof-enter-rotate", "0.5deg");
  }

  function waitForTransition(target) {
    return new Promise(function (resolve) {
      if (reducedMotion()) {
        resolve();
        return;
      }

      var done = false;

      function finish() {
        if (done) {
          return;
        }

        done = true;
        target.removeEventListener("transitionend", onEnd);
        resolve();
      }

      function onEnd(event) {
        if (event.target !== target) {
          return;
        }

        finish();
      }

      target.addEventListener("transitionend", onEnd);
      window.setTimeout(finish, 900);
    });
  }

  function playBrunoVideo() {
    var video = BRUNO.video;

    if (!video) {
      return;
    }

    video.loop = false;
    video.muted = false;

    var playAttempt = video.play();

    if (playAttempt && typeof playAttempt.catch === "function") {
      playAttempt.catch(function () {
        /* Browser may block audible autoplay; controls remain available. */
      });
    }
  }

  function revealBruno() {
    var button = BRUNO.button;
    var duo = BRUNO.duo;
    var photo = BRUNO.photo;
    var videoWrap = BRUNO.videoWrap;

    if (!button || !duo || !photo || !videoWrap || duo.classList.contains("is-discovered")) {
      return;
    }

    photo.hidden = false;
    photo.setAttribute("aria-hidden", "false");
    videoWrap.hidden = false;
    videoWrap.setAttribute("aria-hidden", "false");
    duo.setAttribute("aria-hidden", "false");
    button.setAttribute("aria-expanded", "true");
    playBrunoVideo();

    if (reducedMotion()) {
      duo.classList.add("is-discovered");
      return;
    }

    duo.classList.add("is-animating");

    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        duo.classList.add("is-discovered");
      });
    });

    window.setTimeout(function () {
      duo.classList.remove("is-animating");
    }, 700);
  }

  function bindBruno() {
    if (!BRUNO.button) {
      return;
    }

    BRUNO.button.addEventListener(
      "click",
      function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        revealBruno();
      },
      true
    );
  }

  async function openPortal(pair) {
    var button = pair.button;
    var target = pair.target;

    if (!button || !target) {
      return;
    }

    if (isDesktop()) {
      layoutDesktop(button, target);
    }

    target.hidden = false;
    target.classList.remove("is-closing");
    target.classList.add("is-animating");
    button.setAttribute("aria-expanded", "true");

    if (reducedMotion()) {
      target.classList.add("is-open");
      setSlotOpen(target, true);
      updateStageHeight(target);
      return;
    }

    target.classList.remove("is-open");
    setSlotOpen(target, true);

    await new Promise(function (resolve) {
      requestAnimationFrame(function () {
        requestAnimationFrame(resolve);
      });
    });

    target.classList.add("is-open");
    updateStageHeight(target);

    await waitForTransition(target);
    target.classList.remove("is-animating");
    updateStageHeight(target);
  }

  async function closePortal(pair) {
    var button = pair.button;
    var target = pair.target;

    if (!button || !target) {
      return;
    }

    button.setAttribute("aria-expanded", "false");

    if (reducedMotion()) {
      target.classList.remove("is-open", "is-animating");
      setSlotOpen(target, false);
      target.hidden = true;
      button.focus();
      return;
    }

    setSlotOpen(target, false);
    target.classList.add("is-closing", "is-animating");
    target.classList.remove("is-open");

    await waitForTransition(target);

    target.hidden = true;
    target.classList.remove("is-closing", "is-animating");
    setSlotOpen(target, false);
    button.focus();
  }

  async function togglePortal(pair) {
    if (!pair.target) {
      return;
    }

    var isOpen = pair.target.classList.contains("is-open") && !pair.target.hidden;

    if (isOpen) {
      await closePortal(pair);
      return;
    }

    await openPortal(pair);
  }

  function bindPortal(pair) {
    if (!pair.button || !pair.target) {
      return;
    }

    pair.button.addEventListener(
      "click",
      function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        togglePortal(pair);
      },
      true
    );
  }

  function bindClose(pair) {
    if (!pair.target) {
      return;
    }

    var closeButton = pair.target.querySelector(".about-proof-proto__close");

    if (!closeButton) {
      return;
    }

    closeButton.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();

      if (pair.target.classList.contains("is-open") && !pair.target.hidden) {
        closePortal(pair);
      }
    });
  }

  function bindEscape() {
    document.addEventListener(
      "keydown",
      function (event) {
        if (event.key !== "Escape") {
          return;
        }

        if (higherDiscoveryOpen()) {
          return;
        }

        if (!RTN.target || !RTN.target.classList.contains("is-open") || RTN.target.hidden) {
          return;
        }

        event.stopImmediatePropagation();
        closePortal(RTN);
      },
      true
    );
  }

  function bindResize() {
    var timer = null;

    window.addEventListener("resize", function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        if (!isDesktop()) {
          return;
        }

        if (RTN.target && RTN.target.classList.contains("is-open")) {
          layoutDesktop(RTN.button, RTN.target);
          updateStageHeight(RTN.target);
        }
      }, 120);
    });
  }

  function init() {
    var zone = getZone();

    if (!zone) {
      return;
    }

    BRUNO.button = zone.querySelector('[aria-controls="about-proof-bruno"]');
    BRUNO.duo = document.querySelector("[data-bruno-duo]");
    BRUNO.photo = zone.querySelector(".about-proof-proto__bruno-photo");
    BRUNO.videoWrap = zone.querySelector(".about-proof-proto__bruno-video-wrap");
    BRUNO.video = BRUNO.videoWrap
      ? BRUNO.videoWrap.querySelector(".about-proof-proto__bruno-video")
      : null;
    RTN.button = zone.querySelector('[aria-controls="about-proof-rtn"]');
    RTN.target = document.getElementById("about-proof-rtn");

    bindBruno();
    bindPortal(RTN);
    bindClose(RTN);
    bindEscape();
    bindResize();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
