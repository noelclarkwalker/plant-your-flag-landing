(function () {
  "use strict";

  var DESKTOP_MQ = window.matchMedia("(min-width: 52rem)");
  var REDUCED_MQ = window.matchMedia("(prefers-reduced-motion: reduce)");

  var STATIONERY = {
    button: null,
    target: null,
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

  function getThread() {
    return document.querySelector("[data-stationery-proto]");
  }

  function getSlot(target) {
    return target ? target.closest(".about-stationery-proto__slot") : null;
  }

  function graffitiIsOpen() {
    var graffiti = document.getElementById("about-proof-graffiti");
    var wall = document.getElementById("about-proof-wall");

    return (
      (graffiti &&
        graffiti.classList.contains("is-open") &&
        !graffiti.hidden) ||
      (wall && wall.classList.contains("is-open") && !wall.hidden)
    );
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
        slot.style.removeProperty("--stationery-stage-height");
      }
    }, reducedMotion() ? 0 : 720);
  }

  function updateStageHeight(target) {
    var slot = getSlot(target);

    if (!slot || !target || !isDesktop()) {
      return;
    }

    var top = parseFloat(getComputedStyle(target).getPropertyValue("--stationery-top")) || 0;
    var height = target.getBoundingClientRect().height;
    var stageHeight = top + height + 32;

    slot.style.setProperty("--stationery-stage-height", stageHeight + "px");
  }

  function layoutDesktop(button, target) {
    var thread = getThread();
    var slot = getSlot(target);

    if (!thread || !slot || !button || !target) {
      return;
    }

    var threadRect = thread.getBoundingClientRect();
    var slotRect = slot.getBoundingClientRect();
    var buttonRect = button.getBoundingClientRect();

    var top = Math.max(6, buttonRect.bottom - slotRect.top + 10);
    var left = Math.min(threadRect.width * 0.28, 11 * 16);
    var width = Math.min(window.innerWidth * 0.56, threadRect.width - left - 24, 52 * 16);

    target.style.setProperty("--stationery-top", top + "px");
    target.style.setProperty("--stationery-left", left + "px");
    target.style.setProperty("--stationery-width", Math.max(width, 22 * 16) + "px");
    target.style.setProperty("--stationery-enter-x", Math.min(window.innerWidth * 0.14, 12 * 16) + "px");
    target.style.setProperty("--stationery-enter-rotate", "1.1deg");
    target.style.setProperty("--stationery-settle-rotate", "-0.35deg");
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

  async function openStationery() {
    var button = STATIONERY.button;
    var target = STATIONERY.target;

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

  async function closeStationery() {
    var button = STATIONERY.button;
    var target = STATIONERY.target;

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

  async function toggleStationery() {
    var target = STATIONERY.target;

    if (!target) {
      return;
    }

    var isOpen = target.classList.contains("is-open") && !target.hidden;

    if (isOpen) {
      await closeStationery();
      return;
    }

    await openStationery();
  }

  function bindTrigger() {
    if (!STATIONERY.button) {
      return;
    }

    STATIONERY.button.addEventListener(
      "click",
      function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        toggleStationery();
      },
      true
    );
  }

  function bindClose() {
    if (!STATIONERY.target) {
      return;
    }

    var closeButton = STATIONERY.target.querySelector(".about-stationery-proto__close");

    if (!closeButton) {
      return;
    }

    closeButton.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();

      if (STATIONERY.target.classList.contains("is-open") && !STATIONERY.target.hidden) {
        closeStationery();
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

        if (
          !STATIONERY.target ||
          !STATIONERY.target.classList.contains("is-open") ||
          STATIONERY.target.hidden
        ) {
          return;
        }

        if (graffitiIsOpen()) {
          return;
        }

        event.stopImmediatePropagation();
        closeStationery();
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

        if (STATIONERY.target && STATIONERY.target.classList.contains("is-open")) {
          layoutDesktop(STATIONERY.button, STATIONERY.target);
          updateStageHeight(STATIONERY.target);
        }
      }, 120);
    });
  }

  function init() {
    var thread = getThread();

    if (!thread) {
      return;
    }

    STATIONERY.button = thread.querySelector('[aria-controls="about-proof-essay"]');
    STATIONERY.target = document.getElementById("about-proof-essay");

    bindTrigger();
    bindClose();
    bindEscape();
    bindResize();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
