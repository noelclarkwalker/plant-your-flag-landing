/**
 * Manifesto atmosphere — scroll-driven only (MANIFESTO_TREATMENT).
 * Vignette, grain, paragraph clarity. No autoplay.
 */
(function () {
  let initialized = false;
  const lineTriggers = [];

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function teardown() {
    lineTriggers.forEach((trigger) => trigger.kill());
    lineTriggers.length = 0;
    initialized = false;
  }

  function initManifestoTension() {
    if (initialized || prefersReducedMotion()) {
      return;
    }

    if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") {
      return;
    }

    const declaration = document.querySelector(
      "#scene-02 .cinema-manifesto__declaration",
    );
    const lines = document.querySelectorAll(
      "#scene-02 .cinema-manifesto__declaration .manifesto-line",
    );

    if (!declaration || !lines.length) {
      return;
    }

    teardown();
    gsap.registerPlugin(ScrollTrigger);

    lines.forEach((line) => {
      line.style.setProperty("--line-clarity", "0");

      const trigger = ScrollTrigger.create({
        trigger: line,
        start: "top 78%",
        end: "top 56%",
        scrub: 0.85,
        onUpdate: (self) => {
          line.style.setProperty("--line-clarity", self.progress.toFixed(3));
        },
        onLeave: () => {
          line.style.setProperty("--line-clarity", "1");
        },
        onLeaveBack: () => {
          line.style.setProperty("--line-clarity", "0");
        },
      });

      lineTriggers.push(trigger);
    });

    ScrollTrigger.refresh();
    initialized = true;
  }

  window.initManifestoTension = initManifestoTension;
})();
