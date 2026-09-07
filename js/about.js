(function () {
  "use strict";

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");

  function setPortalOpen(button, target, open) {
    if (open) {
      target.hidden = false;
      target.classList.add("is-open");
      button.setAttribute("aria-expanded", "true");

      if (target.id === "about-soul-interruption") {
        target.setAttribute("aria-hidden", "false");
      }
      return;
    }

    target.hidden = true;
    target.classList.remove("is-open");
    button.setAttribute("aria-expanded", "false");

    if (target.id === "about-soul-interruption") {
      target.setAttribute("aria-hidden", "true");
    }

    button.focus();
  }

  function bindPortal(button) {
    var targetId = button.getAttribute("aria-controls");
    var target = targetId ? document.getElementById(targetId) : null;

    if (!target) {
      return;
    }

    function togglePortal() {
      var isOpen = !target.hidden;
      setPortalOpen(button, target, !isOpen);
    }

    button.addEventListener("click", togglePortal);
  }

  function bindPortals() {
    var portals = document.querySelectorAll(".about-portal[data-portal-target]");

    for (var i = 0; i < portals.length; i += 1) {
      bindPortal(portals[i]);
    }
  }

  function bindEscape() {
    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") {
        return;
      }

      var openButtons = document.querySelectorAll('.about-portal[aria-expanded="true"]');

      if (openButtons.length === 0) {
        return;
      }

      var lastButton = openButtons[openButtons.length - 1];
      var targetId = lastButton.getAttribute("aria-controls");
      var target = targetId ? document.getElementById(targetId) : null;

      if (target) {
        setPortalOpen(lastButton, target, false);
      }
    });
  }

  function setRilkeTranslation(rilke, showEnglish) {
    var german = rilke.querySelector(".about-rilke__layer--de");
    var english = rilke.querySelector(".about-rilke__layer--en");
    var toggle = rilke.querySelector(".about-rilke__toggle");

    if (!german || !english) {
      return;
    }

    rilke.classList.toggle("about-rilke--english", showEnglish);

    german.hidden = showEnglish;
    english.hidden = !showEnglish;

    if (toggle) {
      toggle.setAttribute("aria-pressed", showEnglish ? "true" : "false");
    }
  }

  function bindRilkeClose(wrap) {
    var closeButton = wrap.querySelector(".about-rilke__close");
    var targetId = wrap.id;
    var portalButton = targetId
      ? document.querySelector('.about-portal[aria-controls="' + targetId + '"]')
      : null;

    if (!closeButton || !portalButton) {
      return;
    }

    closeButton.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      setPortalOpen(portalButton, wrap, false);
    });
  }

  function bindRilkeCloses() {
    var wraps = document.querySelectorAll(".about-rilke-wrap");

    for (var i = 0; i < wraps.length; i += 1) {
      bindRilkeClose(wraps[i]);
    }
  }

  function bindRilke(rilke) {
    var toggle = rilke.querySelector(".about-rilke__toggle");
    var touchMode = !finePointer.matches;

    if (touchMode) {
      rilke.classList.add("about-rilke--touch");
    }

    function toggleRilke() {
      var showEnglish = !rilke.classList.contains("about-rilke--english");
      setRilkeTranslation(rilke, showEnglish);
    }

    if (toggle) {
      toggle.addEventListener("click", function (event) {
        event.stopPropagation();
        toggleRilke();
      });
    }

    if (touchMode) {
      rilke.addEventListener("click", function (event) {
        if (event.target.closest(".about-portal") || event.target.closest(".about-rilke__close")) {
          return;
        }

        toggleRilke();
      });
    } else {
      rilke.addEventListener("mouseenter", function () {
        setRilkeTranslation(rilke, true);
      });

      rilke.addEventListener("mouseleave", function () {
        setRilkeTranslation(rilke, false);
      });
    }
  }

  function bindRilkes() {
    var rilkes = document.querySelectorAll(".about-rilke");

    for (var i = 0; i < rilkes.length; i += 1) {
      bindRilke(rilkes[i]);
    }
  }

  function init() {
    bindPortals();
    bindRilkeCloses();
    bindRilkes();
    bindEscape();

    if (reducedMotion) {
      document.documentElement.classList.add("about-reduced-motion");
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
