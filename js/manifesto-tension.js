/**
 * Manifesto tension — environmental motion only.
 * Does not alter typography, spacing, or composition.
 */
(function () {
  let initialized = false;
  let ambientTween = null;
  let breatheTween = null;
  let scrollTrigger = null;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function teardown() {
    ambientTween?.kill();
    breatheTween?.kill();
    scrollTrigger?.kill();
    ambientTween = null;
    breatheTween = null;
    scrollTrigger = null;
    initialized = false;
  }

  function initManifestoTension() {
    if (initialized || prefersReducedMotion()) {
      return;
    }

    if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") {
      return;
    }

    const cinemaManifesto = document.querySelector("#scene-02 .cinema-manifesto");
    const cinemaStage = document.querySelector(
      "#scene-02 .cinema-manifesto__stage",
    );

    if (!cinemaManifesto || !cinemaStage) {
      return;
    }

    teardown();
    gsap.registerPlugin(ScrollTrigger);

    const drift = { x: 0, y: 0, breathe: 0 };

    ambientTween = gsap.to(drift, {
      x: 1,
      y: 1,
      duration: 52,
      repeat: -1,
      yoyo: true,
      ease: "sine.inOut",
      onUpdate: () => {
        const x = gsap.utils.interpolate(-3.5, 3.5, drift.x);
        const y = gsap.utils.interpolate(-2.5, 2.5, drift.y);
        cinemaManifesto.style.setProperty("--env-drift-x", `${x}px`);
        cinemaManifesto.style.setProperty("--env-drift-y", `${y}px`);
      },
    });

    breatheTween = gsap.to(drift, {
      breathe: 1,
      duration: 9.5,
      repeat: -1,
      yoyo: true,
      ease: "sine.inOut",
      onUpdate: () => {
        cinemaManifesto.style.setProperty(
          "--env-breathe",
          String(drift.breathe),
        );
      },
    });

    scrollTrigger = ScrollTrigger.create({
      scroller: cinemaManifesto,
      trigger: cinemaStage,
      start: "top top",
      end: "bottom bottom",
      scrub: 0.65,
      onUpdate: (self) => {
        cinemaManifesto.style.setProperty(
          "--env-tension",
          self.progress.toFixed(4),
        );
      },
    });

    ScrollTrigger.refresh();
    initialized = true;
  }

  window.initManifestoTension = initManifestoTension;
})();
