/**
 * REFRAME PROTOTYPE — throwaway
 *
 * Medium shot → close-up. Camera changes subject to the monogram.
 * Stops when the complete NC is centered. No forward dolly.
 *
 * Remove: this file, reframe-prototype.css, index.html lines.
 */
(function () {
  const REFRAME_MS = 1320;

  const manifesto = document.querySelector("#scene-02 .cinema-manifesto__declaration");
  const signature = document.querySelector("#scene-02 .portal-signature");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const monogram = signature?.querySelector("img");

  if (!manifesto || !signature || !socialPost || !monogram) {
    return;
  }

  let state = "idle";
  let reframeStart = 0;
  let manifestoExitY = 0;
  let signatureDeltaX = 0;
  let signatureDeltaY = 0;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  }

  function normalizeWheelDelta(event) {
    let delta = event.deltaY;

    if (event.deltaMode === 1) {
      delta *= 16;
    } else if (event.deltaMode === 2) {
      delta *= window.innerHeight;
    }

    return delta;
  }

  function manifestoFinished() {
    return (
      socialPost.classList.contains("platform-surrendered") &&
      signature.classList.contains("is-revealed")
    );
  }

  function isScrollingPastMonogram() {
    const rect = monogram.getBoundingClientRect();

    return rect.width > 0 && rect.top < window.innerHeight * 0.92 && rect.bottom > 0;
  }

  function lockPageScroll() {
    const scrollY = window.scrollY;
    document.body.style.position = "fixed";
    document.body.style.top = `-${scrollY}px`;
    document.body.style.left = "0";
    document.body.style.right = "0";
    document.body.style.width = "100%";
  }

  function captureReframeTargets() {
    const monoRect = monogram.getBoundingClientRect();
    const manRect = manifesto.getBoundingClientRect();
    const targetX = window.innerWidth / 2;
    const targetY = window.innerHeight / 2;
    const monoCenterX = monoRect.left + monoRect.width / 2;
    const monoCenterY = monoRect.top + monoRect.height / 2;

    signatureDeltaX = targetX - monoCenterX;
    signatureDeltaY = targetY - monoCenterY;
    manifestoExitY = -(manRect.bottom + 32);
  }

  function applyReframe(progress) {
    const t = easeInOutCubic(progress);

    document.body.style.setProperty(
      "--reframe-manifesto-y",
      `${t * manifestoExitY}px`,
    );
    document.body.style.setProperty("--reframe-sig-x", `${t * signatureDeltaX}px`);
    document.body.style.setProperty("--reframe-sig-y", `${t * signatureDeltaY}px`);
  }

  function completeReframe() {
    applyReframe(1);
    state = "centered";
    document.body.dataset.reframePrototype = "centered";
  }

  function reframeFrame(now) {
    if (state !== "reframing") {
      return;
    }

    const progress = Math.min(1, (now - reframeStart) / REFRAME_MS);
    applyReframe(progress);

    if (progress < 1) {
      requestAnimationFrame(reframeFrame);
      return;
    }

    completeReframe();
  }

  function beginReframe() {
    lockPageScroll();
    captureReframeTargets();
    state = "reframing";
    document.body.dataset.reframePrototype = "reframing";
    reframeStart = performance.now();
    requestAnimationFrame(reframeFrame);
  }

  function onWheel(event) {
    if (prefersReducedMotion()) {
      return;
    }

    if (state === "centered") {
      event.preventDefault();
      return;
    }

    if (state === "reframing") {
      event.preventDefault();
      return;
    }

    if (state !== "idle") {
      return;
    }

    const delta = normalizeWheelDelta(event);

    if (delta <= 0 || !manifestoFinished() || !isScrollingPastMonogram()) {
      return;
    }

    event.preventDefault();
    beginReframe();
  }

  window.addEventListener("wheel", onWheel, { passive: false });
})();
