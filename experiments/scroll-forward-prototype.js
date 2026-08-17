/**
 * SCROLL-FORWARD PROTOTYPE — throwaway
 *
 * Question: Can scrolling stop meaning "down" and begin meaning "forward"?
 *
 * Remove: this file, scroll-forward-prototype.css, index.html lines.
 */
(function () {
  const MAX_FORWARD_Z = 540;
  const WHEEL_GAIN = 0.9;

  const signature = document.querySelector("#scene-02 .portal-signature");
  const continuum = document.querySelector("#scene-02 .writing-continuum");
  const surface = document.querySelector("#scene-02 .continuous-surface");

  if (!signature || !continuum || !surface) {
    return;
  }

  let armed = false;
  let active = false;
  let forwardZ = 0;
  let lockedScrollY = 0;

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

  function signatureReady() {
    return signature.classList.contains("is-revealed");
  }

  function updateArmState() {
    if (active || prefersReducedMotion()) {
      armed = false;
      return;
    }

    if (!signatureReady()) {
      armed = false;
      return;
    }

    const rect = signature.getBoundingClientRect();
    const inView =
      rect.top < window.innerHeight * 0.85 && rect.bottom > window.innerHeight * 0.15;

    armed = inView;
  }

  function setForwardOrigin() {
    const sigTarget = signature.querySelector("img") || signature;
    const cRect = continuum.getBoundingClientRect();
    const sRect = sigTarget.getBoundingClientRect();
    const originX = sRect.left + sRect.width / 2 - cRect.left;
    const originY = sRect.top + sRect.height / 2 - cRect.top;

    continuum.style.transformOrigin = `${originX}px ${originY}px`;
  }

  function applyForwardZ() {
    continuum.style.setProperty("--scroll-forward-z", `${forwardZ}px`);
  }

  function lockPageScroll() {
    lockedScrollY = window.scrollY;
    document.body.style.position = "fixed";
    document.body.style.top = `-${lockedScrollY}px`;
    document.body.style.left = "0";
    document.body.style.right = "0";
    document.body.style.width = "100%";
  }

  function activateForwardMode() {
    if (active) {
      return;
    }

    active = true;
    armed = false;
    document.body.dataset.scrollForward = "active";
    lockPageScroll();
    setForwardOrigin();
    applyForwardZ();
  }

  function onWheel(event) {
    updateArmState();

    if (!armed && !active) {
      return;
    }

    const delta = normalizeWheelDelta(event);

    if (armed && !active) {
      if (delta <= 0) {
        return;
      }

      event.preventDefault();
      activateForwardMode();
      forwardZ = Math.min(MAX_FORWARD_Z, forwardZ + delta * WHEEL_GAIN);
      setForwardOrigin();
      applyForwardZ();
      return;
    }

    if (!active) {
      return;
    }

    event.preventDefault();

    forwardZ = Math.min(MAX_FORWARD_Z, Math.max(0, forwardZ + delta * WHEEL_GAIN));
    setForwardOrigin();
    applyForwardZ();
  }

  function onResize() {
    if (active) {
      setForwardOrigin();
    } else {
      updateArmState();
    }
  }

  const armObserver = new IntersectionObserver(
    () => {
      updateArmState();
    },
    { threshold: [0, 0.25, 0.5, 0.75, 1] },
  );

  armObserver.observe(signature);

  window.addEventListener("wheel", onWheel, { passive: false });
  window.addEventListener("resize", onResize);

  const revealPoll = window.setInterval(() => {
    updateArmState();

    if (signatureReady()) {
      window.clearInterval(revealPoll);
    }
  }, 400);
})();
