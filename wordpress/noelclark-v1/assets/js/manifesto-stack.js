/**
 * Stacked Manifesto — approved scroll rise; arrived thoughts stay full white.
 */
(function () {
  const READING_LINE_RATIO = 0.68;
  const RISE_PX = 28;
  const OPACITY_INCOMING = 0.34;
  const OPACITY_ARRIVED = 1;
  const APPROACH_BAND_RATIO = 0.18;

  let thoughts = [];
  let initialized = false;
  let rafId = null;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function clamp01(value) {
    return Math.min(1, Math.max(0, value));
  }

  function easeSmoothstep(value) {
    const t = clamp01(value);
    return t * t * (3 - 2 * t);
  }

  function isVisible(element) {
    if (!element?.isConnected) {
      return false;
    }

    const style = getComputedStyle(element);

    if (style.display === "none" || style.visibility === "hidden") {
      return false;
    }

    return element.getClientRects().length > 0;
  }

  function setThoughtArrived(thought) {
    thought.arrived = true;
    thought.root.style.setProperty("--thought-y", "0px");
    thought.root.style.setProperty("--thought-opacity", String(OPACITY_ARRIVED));
  }

  function applyThoughtState(thought, viewportHeight) {
    if (thought.arrived) {
      return;
    }

    if (!isVisible(thought.root)) {
      return;
    }

    const rect = thought.root.getBoundingClientRect();
    const readingLine = viewportHeight * READING_LINE_RATIO;
    const approachBand = viewportHeight * APPROACH_BAND_RATIO;
    const anchorY = rect.top + rect.height * 0.38;
    const approachStart = readingLine + approachBand;

    let translateY = RISE_PX;
    let opacity = OPACITY_INCOMING;

    if (anchorY >= approachStart) {
      translateY = RISE_PX;
      opacity = OPACITY_INCOMING;
    } else if (anchorY > readingLine) {
      const progress = easeSmoothstep(1 - (anchorY - readingLine) / (approachStart - readingLine));
      translateY = RISE_PX * (1 - progress);
      opacity = OPACITY_INCOMING + (OPACITY_ARRIVED - OPACITY_INCOMING) * progress;
    } else {
      setThoughtArrived(thought);
      return;
    }

    thought.root.style.setProperty("--thought-y", `${translateY.toFixed(2)}px`);
    thought.root.style.setProperty("--thought-opacity", opacity.toFixed(3));
  }

  function setThoughtsSettled() {
    thoughts.forEach((thought) => {
      setThoughtArrived(thought);
    });
  }

  function updateAll() {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    thoughts.forEach((thought) => {
      applyThoughtState(thought, viewportHeight);
    });
  }

  function onScrollOrResize() {
    if (rafId !== null) {
      return;
    }

    rafId = window.requestAnimationFrame(() => {
      rafId = null;
      updateAll();
    });
  }

  function initManifestoStack() {
    if (initialized) {
      return;
    }

    const elements = document.querySelectorAll(
      "#scene-02 .cinema-manifesto__declaration [data-manifesto-thought]",
    );

    if (!elements.length) {
      return;
    }

    thoughts = Array.from(elements).map((element) => ({
      root: element,
      arrived: false,
    }));
    initialized = true;

    if (prefersReducedMotion()) {
      setThoughtsSettled();
      return;
    }

    window.addEventListener("scroll", onScrollOrResize, { passive: true });
    window.addEventListener("resize", onScrollOrResize, { passive: true });
    window.addEventListener("pagehide", setThoughtsSettled);

    updateAll();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initManifestoStack);
  } else {
    initManifestoStack();
  }

  window.initManifestoStack = initManifestoStack;
})();
