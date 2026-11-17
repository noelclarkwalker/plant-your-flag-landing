(function () {
  "use strict";

  var hero = document.querySelector(".ps-hero");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function updateHeroProgress() {
    if (!hero || reducedMotion) {
      return;
    }

    /*
     * Cause of the scroll pause (not a timer / GSAP scrub):
     * 1) start = hero.offsetTop ignored the first ~100px of scroll while the
     *    site header cleared — a hard dead zone before blur moved.
     * 2) end spanned sticky + ~115vh track minus 0.35*vh (~1100px on phone),
     *    so early scroll after that produced near-invisible blur.
     *
     * Fix: drive progress from scrollY = 0, and finish when the journey
     * trail (.ps-bar) reaches the viewport so blur and nav stay in sync.
     */
    var bar = document.getElementById("ps-bar");
    var end = bar
      ? Math.max(bar.offsetTop - window.innerHeight, 1)
      : Math.max(hero.offsetHeight - window.innerHeight * 0.35, 1);
    var linear = Math.min(Math.max(window.scrollY / end, 0), 1);
    /* Mild front-load so the first flick of scroll is already visible. */
    var progress = Math.min(Math.max(Math.pow(linear, 0.6), 0), 1);

    hero.style.setProperty("--ps-hero-scale", String(1 + progress * 0.065));
    hero.style.setProperty("--ps-hero-blur", progress * 8 + "px");
  }

  var HERO_GAZE_HOLD_MS = 1500;

  function initHeroVideo() {
    var video = document.querySelector(".ps-hero__video");
    var activateButton = document.querySelector(".ps-hero__activate");
    var holdTimer = null;

    if (!video || !hero) {
      return;
    }

    video.removeAttribute("loop");
    video.loop = false;

    if (reducedMotion) {
      hero.classList.remove("ps-hero--awaiting-autoplay", "ps-hero--autoplay-blocked", "ps-hero--motion-active");
      video.removeAttribute("autoplay");
      video.pause();

      function showFirstFrame() {
        video.pause();

        try {
          video.currentTime = 0;
        } catch (error) {
          /* static fallback is shown via CSS */
        }
      }

      if (video.readyState >= 2) {
        showFirstFrame();
      } else {
        video.addEventListener("loadeddata", showFirstFrame, { once: true });
      }

      return;
    }

    video.defaultMuted = true;
    video.muted = true;
    video.playsInline = true;

    function clearHold() {
      if (holdTimer !== null) {
        clearTimeout(holdTimer);
        holdTimer = null;
      }
    }

    function enterMotionActive() {
      hero.classList.remove("ps-hero--awaiting-autoplay", "ps-hero--autoplay-blocked");
      hero.classList.add("ps-hero--motion-active");
    }

    function enterAutoplayBlocked() {
      hero.classList.remove("ps-hero--awaiting-autoplay", "ps-hero--motion-active");
      hero.classList.add("ps-hero--autoplay-blocked");
      video.pause();
    }

    function playFromStart() {
      clearHold();
      video.currentTime = 0;
      video.muted = true;

      return video.play();
    }

    function tryAutoplay() {
      if (!video.paused) {
        enterMotionActive();
        return Promise.resolve();
      }

      video.muted = true;

      return video.play().then(enterMotionActive).catch(enterAutoplayBlocked);
    }

    video.addEventListener(
      "ended",
      function () {
        clearHold();
        holdTimer = setTimeout(function () {
          playFromStart().catch(function () {
            /* replay blocked */
          });
        }, HERO_GAZE_HOLD_MS);
      },
      false
    );

    if (activateButton) {
      activateButton.addEventListener("click", function () {
        playFromStart().then(enterMotionActive).catch(function () {
          /* gesture play blocked */
        });
      });
    }

    video.addEventListener("canplay", tryAutoplay, { once: true });

    if (video.readyState >= 3) {
      tryAutoplay();
    }
  }

  function initMotionPreviews() {
    if (reducedMotion) {
      var videos = document.querySelectorAll(".ps-preview__video");
      var i;

      for (i = 0; i < videos.length; i += 1) {
        (function (video) {
          video.removeAttribute("autoplay");

          function showFirstFrame() {
            video.pause();

            try {
              video.currentTime = 0.01;
            } catch (error) {
              video.currentTime = 0;
            }
          }

          if (video.readyState >= 2) {
            showFirstFrame();
          } else {
            video.addEventListener("loadeddata", showFirstFrame, { once: true });
          }

          video.load();
        })(videos[i]);
      }
    }
  }

  function bindEvents() {
    if (!reducedMotion && hero) {
      window.addEventListener("scroll", updateHeroProgress, { passive: true });
      window.addEventListener("resize", updateHeroProgress);
      updateHeroProgress();
    }

    initHeroVideo();
    initMotionPreviews();
  }

  bindEvents();
})();
