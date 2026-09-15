(function () {
  "use strict";

  var motionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");

  function initGateways() {
    var stage = document.getElementById("rtn-gateways-stage");
    if (!stage) {
      return;
    }

    var triggers = document.querySelectorAll(".rtn-gateway__trigger");
    var panels = stage.querySelectorAll(".rtn-gateway-panel");
    var openGateway = null;

    function pausePanelVideos(panel) {
      panel.querySelectorAll("video").forEach(function (video) {
        video.pause();
      });
    }

    function closeAll() {
      triggers.forEach(function (trigger) {
        trigger.setAttribute("aria-expanded", "false");
      });

      panels.forEach(function (panel) {
        pausePanelVideos(panel);
        panel.hidden = true;
      });

      stage.hidden = true;
      openGateway = null;
    }

    function openPanel(gatewayId, trigger) {
      if (openGateway === gatewayId) {
        closeAll();
        return;
      }

      closeAll();
      openGateway = gatewayId;
      stage.hidden = false;

      var panel = document.getElementById("rtn-panel-" + gatewayId);
      if (panel) {
        panel.hidden = false;
      }

      trigger.setAttribute("aria-expanded", "true");

      if (!motionQuery.matches) {
        stage.scrollIntoView({ behavior: "smooth", block: "nearest" });
      } else {
        stage.scrollIntoView({ block: "nearest" });
      }
    }

    triggers.forEach(function (trigger) {
      var gateway = trigger.closest(".rtn-gateway");
      if (!gateway) {
        return;
      }

      var gatewayId = gateway.getAttribute("data-gateway");
      if (!gatewayId) {
        return;
      }

      trigger.addEventListener("click", function () {
        openPanel(gatewayId, trigger);
      });

      trigger.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && openGateway === gatewayId) {
          closeAll();
          trigger.focus();
        }
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && openGateway) {
        var activeTrigger = document.querySelector(
          '.rtn-gateway[data-gateway="' + openGateway + '"] .rtn-gateway__trigger'
        );
        closeAll();
        if (activeTrigger) {
          activeTrigger.focus();
        }
      }
    });
  }

  function initVoicesRail() {
    var rail = document.getElementById("rtn-voices-rail");
    var frame = document.querySelector(".rtn-voices__frame");
    var prevButton = document.querySelector(".rtn-voices__arrow--prev");
    var nextButton = document.querySelector(".rtn-voices__arrow--next");

    if (!rail || !frame) {
      return;
    }

    var slides = rail.querySelectorAll(".rtn-voices__slide");
    if (!slides.length) {
      return;
    }

    function syncSlideWidths() {
      var width = frame.clientWidth;
      slides.forEach(function (slide) {
        slide.style.flexBasis = width + "px";
        slide.style.width = width + "px";
        slide.style.minWidth = width + "px";
        slide.style.maxWidth = width + "px";
      });
    }

    function getActiveIndex() {
      var width = frame.clientWidth || 1;
      return Math.round(rail.scrollLeft / width);
    }

    function updateArrows() {
      var index = getActiveIndex();
      var lastIndex = slides.length - 1;

      if (prevButton) {
        prevButton.hidden = index <= 0;
      }

      if (nextButton) {
        nextButton.hidden = index >= lastIndex;
      }
    }

    function scrollToIndex(index) {
      var width = frame.clientWidth;
      rail.scrollTo({
        left: index * width,
        behavior: motionQuery.matches ? "auto" : "smooth",
      });
    }

    function scrollByDirection(direction) {
      scrollToIndex(getActiveIndex() + direction);
    }

    if (prevButton) {
      prevButton.addEventListener("click", function () {
        scrollByDirection(-1);
      });
    }

    if (nextButton) {
      nextButton.addEventListener("click", function () {
        scrollByDirection(1);
      });
    }

    rail.addEventListener("keydown", function (event) {
      if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") {
        return;
      }

      event.preventDefault();
      scrollByDirection(event.key === "ArrowRight" ? 1 : -1);
    });

    rail.addEventListener("scroll", updateArrows, { passive: true });
    window.addEventListener("resize", function () {
      syncSlideWidths();
      scrollToIndex(getActiveIndex());
      updateArrows();
    });

    syncSlideWidths();
    updateArrows();
  }

  initGateways();
  initVoicesRail();
})();
