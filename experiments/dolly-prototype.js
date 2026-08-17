/**
 * DOLLY PROTOTYPE — throwaway
 *
 * Camera language only. Monogram stays one object. No timers.
 *
 * Remove: this file, dolly-prototype.css, index.html lines.
 */
(function () {
  const MAX_DOLLY_Z = 680;
  const WHEEL_TO_PROGRESS = 0.00016;
  const DOLLY_SMOOTH = 0.038;

  const signature = document.querySelector("#scene-02 .portal-signature");
  const surface = document.querySelector("#scene-02 .continuous-surface");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const monogram = signature?.querySelector("img");

  if (!signature || !surface || !socialPost || !monogram) {
    return;
  }

  let state = "idle";
  let targetProgress = 0;
  let currentProgress = 0;
  let animating = false;
  let rigInstalled = false;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
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

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
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

  function installRig() {
    if (rigInstalled || surface.closest(".dolly-viewport")) {
      rigInstalled = true;
      return;
    }

    const viewport = document.createElement("div");
    viewport.className = "dolly-viewport";
    const rig = document.createElement("div");
    rig.className = "dolly-rig";

    surface.parentNode.insertBefore(viewport, surface);
    viewport.appendChild(rig);
    rig.appendChild(surface);
    rigInstalled = true;
  }

  function lockPageScroll() {
    const scrollY = window.scrollY;
    document.body.style.position = "fixed";
    document.body.style.top = `-${scrollY}px`;
    document.body.style.left = "0";
    document.body.style.right = "0";
    document.body.style.width = "100%";
  }

  function lockDollyAnchor() {
    const rig = surface.closest(".dolly-rig");
    const viewport = surface.closest(".dolly-viewport");

    if (!rig || !viewport) {
      return;
    }

    const rigRect = rig.getBoundingClientRect();
    const viewportRect = viewport.getBoundingClientRect();
    const monogramRect = monogram.getBoundingClientRect();
    const anchorX = monogramRect.left + monogramRect.width / 2 - rigRect.left;
    const anchorY = monogramRect.top + monogramRect.height / 2 - rigRect.top;
    const viewX = monogramRect.left + monogramRect.width / 2 - viewportRect.left;
    const viewY = monogramRect.top + monogramRect.height / 2 - viewportRect.top;

    rig.style.setProperty("--dolly-origin-x", `${anchorX}px`);
    rig.style.setProperty("--dolly-origin-y", `${anchorY}px`);
    viewport.style.setProperty("--dolly-vp-x", `${viewX}px`);
    viewport.style.setProperty("--dolly-vp-y", `${viewY}px`);
  }

  function applyDolly(progress) {
    const rig = surface.closest(".dolly-rig");
    if (!rig) {
      return;
    }

    rig.style.setProperty("--dolly-z", `${progress * MAX_DOLLY_Z}px`);
  }

  function beginDolly() {
    installRig();
    lockPageScroll();
    lockDollyAnchor();
    applyDolly(0);
    state = "moving";
    document.body.dataset.dollyPrototype = "moving";
  }

  function tick() {
    currentProgress += (targetProgress - currentProgress) * DOLLY_SMOOTH;

    if (Math.abs(targetProgress - currentProgress) < 0.00008) {
      currentProgress = targetProgress;
    }

    applyDolly(currentProgress);

    if (currentProgress >= 0.9995 && targetProgress >= 1) {
      currentProgress = 1;
      targetProgress = 1;
      applyDolly(1);
      state = "complete";
      document.body.dataset.dollyPrototype = "complete";
      animating = false;
      return;
    }

    if (Math.abs(targetProgress - currentProgress) > 0.00008) {
      requestAnimationFrame(tick);
      return;
    }

    animating = false;
  }

  function ensureTicking() {
    if (!animating) {
      animating = true;
      requestAnimationFrame(tick);
    }
  }

  function onWheel(event) {
    if (prefersReducedMotion()) {
      return;
    }

    const delta = normalizeWheelDelta(event);

    if (state === "complete") {
      event.preventDefault();
      return;
    }

    if (state === "idle") {
      if (delta <= 0 || !manifestoFinished() || !isScrollingPastMonogram()) {
        return;
      }

      event.preventDefault();
      beginDolly();
      targetProgress = clamp(delta * WHEEL_TO_PROGRESS, 0, 1);
      ensureTicking();
      return;
    }

    event.preventDefault();
    targetProgress = clamp(targetProgress + delta * WHEEL_TO_PROGRESS, 0, 1);
    ensureTicking();
  }

  function onResize() {
    if (state !== "moving" && state !== "complete") {
      return;
    }

    lockDollyAnchor();
    applyDolly(currentProgress);
  }

  window.addEventListener("wheel", onWheel, { passive: false });
  window.addEventListener("resize", onResize);
})();
