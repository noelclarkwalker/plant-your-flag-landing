(function () {
  "use strict";

  var DESKTOP_MQ = window.matchMedia("(min-width: 52rem)");
  var REDUCED_MQ = window.matchMedia("(prefers-reduced-motion: reduce)");

  var GRAFFITI = {
    button: null,
    target: null,
    variant: "first",
  };

  var WALL = {
    button: null,
    target: null,
    variant: "second",
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
    return document.querySelector("[data-graffiti-proto]");
  }

  function getSlot(target) {
    return target ? target.closest(".about-graffiti-proto__slot") : null;
  }

  function updateStageHeight(target) {
    var slot = getSlot(target);
    var image = target ? target.querySelector(".about-reveal__image") : null;

    if (!slot || !image || !isDesktop()) {
      return;
    }

    function measure() {
      var top = parseFloat(getComputedStyle(target).getPropertyValue("--proto-top")) || 0;
      var displayedHeight = image.getBoundingClientRect().height;
      var width =
        parseFloat(getComputedStyle(target).getPropertyValue("--proto-width")) ||
        image.clientWidth;

      if (displayedHeight < 1 && image.naturalWidth > 0 && width > 0) {
        displayedHeight = (image.naturalHeight / image.naturalWidth) * width;
      }

      var stageHeight = top + displayedHeight + 28;

      slot.style.setProperty("--proto-stage-height", stageHeight + "px");
    }

    if (image.complete) {
      measure();
      return;
    }

    image.addEventListener("load", measure, { once: true });
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
        slot.style.removeProperty("--proto-stage-height");
      }
    }, reducedMotion() ? 0 : 780);
  }

  function otherPair(pair) {
    return pair === GRAFFITI ? WALL : GRAFFITI;
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

  function layoutDesktop(button, target, variant) {
    var thread = getThread();
    var slot = getSlot(target);

    if (!thread || !slot || !button || !target) {
      return;
    }

    var threadRect = thread.getBoundingClientRect();
    var slotRect = slot.getBoundingClientRect();
    var buttonRect = button.getBoundingClientRect();

    var top = Math.max(8, buttonRect.bottom - slotRect.top + (variant === "first" ? 8 : 20));
    var left =
      variant === "first"
        ? Math.min(threadRect.width * 0.34, 13.5 * 16)
        : Math.min(threadRect.width * 0.24, 11.5 * 16);
    var widthVw = variant === "first" ? 0.74 : 0.68;
    var width = Math.min(window.innerWidth * widthVw, threadRect.width - left - 20);

    target.style.setProperty("--proto-top", top + "px");
    target.style.setProperty("--proto-left", left + "px");
    target.style.setProperty("--proto-width", Math.max(width, 16 * 16) + "px");

    var figureScreenLeft = slotRect.left + left;
    var figureScreenTop = slotRect.top + top;
    var enterX = buttonRect.left + buttonRect.width * 0.5 - figureScreenLeft;
    var enterY = buttonRect.top + buttonRect.height * 0.5 - figureScreenTop;
    var enterScale = variant === "first" ? 0.14 : 0.18;

    if (variant === "second") {
      enterX += threadRect.width * 0.08;
      enterY += 24;
    }

    target.style.setProperty("--proto-enter-x", enterX + "px");
    target.style.setProperty("--proto-enter-y", enterY + "px");
    target.style.setProperty("--proto-enter-scale", String(enterScale));
  }

  function syncYield(activePair) {
    var sibling = otherPair(activePair);

    if (!sibling.target || sibling.target.hidden) {
      return;
    }

    if (activePair.target && !activePair.target.hidden && activePair.target.classList.contains("is-open")) {
      sibling.target.classList.add("is-yielded");
      sibling.target.classList.add("is-animating");
      return;
    }

    sibling.target.classList.remove("is-yielded");
  }

  function clearYield() {
    GRAFFITI.target.classList.remove("is-yielded");
    WALL.target.classList.remove("is-yielded");
  }

  async function openPortal(pair) {
    var button = pair.button;
    var target = pair.target;

    if (!button || !target) {
      return;
    }

    if (isDesktop()) {
      layoutDesktop(button, target, pair.variant);
    }

    target.hidden = false;
    target.classList.remove("is-closing");
    target.classList.add("is-animating");
    button.setAttribute("aria-expanded", "true");

    if (reducedMotion()) {
      target.classList.add("is-open");
      setSlotOpen(target, true);
      updateStageHeight(target);
      syncYield(pair);
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
    syncYield(pair);

    await waitForTransition(target);
    target.classList.remove("is-animating");
  }

  async function closePortal(pair) {
    var button = pair.button;
    var target = pair.target;

    if (!button || !target) {
      return;
    }

    button.setAttribute("aria-expanded", "false");
    clearYield();

    var sibling = otherPair(pair);

    if (
      sibling.target &&
      !sibling.target.hidden &&
      sibling.target.classList.contains("is-open")
    ) {
      sibling.target.classList.remove("is-yielded");
      sibling.target.classList.add("is-animating");
      window.setTimeout(function () {
        sibling.target.classList.remove("is-animating");
      }, 500);
    }

    if (reducedMotion()) {
      target.classList.remove("is-open", "is-animating", "is-yielded");
      setSlotOpen(target, false);
      target.hidden = true;
      button.focus();
      return;
    }

    setSlotOpen(target, false);
    target.classList.add("is-closing", "is-animating");
    target.classList.remove("is-open", "is-yielded");

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

  function bindClose(pair) {
    if (!pair.target) {
      return;
    }

    var closeButton = pair.target.querySelector(".about-graffiti-proto__close");

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

  function bindEscape() {
    document.addEventListener(
      "keydown",
      function (event) {
        if (event.key !== "Escape") {
          return;
        }

        var openPair = null;

        if (WALL.target && WALL.target.classList.contains("is-open") && !WALL.target.hidden) {
          openPair = WALL;
        } else if (
          GRAFFITI.target &&
          GRAFFITI.target.classList.contains("is-open") &&
          !GRAFFITI.target.hidden
        ) {
          openPair = GRAFFITI;
        }

        if (!openPair) {
          return;
        }

        event.stopImmediatePropagation();
        closePortal(openPair);
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

        if (GRAFFITI.target && GRAFFITI.target.classList.contains("is-open")) {
          layoutDesktop(GRAFFITI.button, GRAFFITI.target, "first");
          updateStageHeight(GRAFFITI.target);
        }

        if (WALL.target && WALL.target.classList.contains("is-open")) {
          layoutDesktop(WALL.button, WALL.target, "second");
          updateStageHeight(WALL.target);
        }
      }, 120);
    });
  }

  function init() {
    var thread = getThread();

    if (!thread) {
      return;
    }

    GRAFFITI.button = thread.querySelector('[aria-controls="about-proof-graffiti"]');
    GRAFFITI.target = document.getElementById("about-proof-graffiti");
    WALL.button = thread.querySelector('[aria-controls="about-proof-wall"]');
    WALL.target = document.getElementById("about-proof-wall");

    bindPortal(GRAFFITI);
    bindPortal(WALL);
    bindClose(GRAFFITI);
    bindClose(WALL);
    bindEscape();
    bindResize();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
