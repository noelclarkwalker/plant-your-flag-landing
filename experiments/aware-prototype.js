/**
 * AWARE PROTOTYPE 006 — throwaway
 *
 * Question: Can the monogram feel quietly aware without appearing animated?
 * Concept: Present depth — after a beat, the signature sits infinitesimally nearer.
 *
 * Remove: this file, aware-prototype.css, index.html lines.
 */
(function () {
  const PRESENCE_DELAY_MS = 1400;
  const FULLY_VISIBLE_INSET = 10;

  const signature = document.querySelector("#scene-02 .portal-signature");
  const continuum = document.querySelector("#scene-02 .writing-continuum");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const monogram = signature?.querySelector("img");

  if (!signature || !continuum || !socialPost || !monogram) {
    return;
  }

  let presenceTimer = null;
  let presenceActive = false;

  function manifestoFinished() {
    return (
      socialPost.classList.contains("platform-surrendered") &&
      signature.classList.contains("is-revealed")
    );
  }

  function isMonogramFullyVisible() {
    const rect = monogram.getBoundingClientRect();

    return (
      rect.width > 0 &&
      rect.height > 0 &&
      rect.top >= FULLY_VISIBLE_INSET &&
      rect.bottom <= window.innerHeight - FULLY_VISIBLE_INSET &&
      rect.left >= FULLY_VISIBLE_INSET &&
      rect.right <= window.innerWidth - FULLY_VISIBLE_INSET
    );
  }

  function cameraHasStarted() {
    const phase = document.body.dataset.cameraPrototype;
    return phase === "camera" || phase === "complete";
  }

  function setOpticalOrigin() {
    const continuumRect = continuum.getBoundingClientRect();
    const monogramRect = monogram.getBoundingClientRect();
    const originX = monogramRect.left + monogramRect.width / 2 - continuumRect.left;
    const originY = monogramRect.top + monogramRect.height / 2 - continuumRect.top;

    continuum.style.setProperty("--aware-ox", `${originX}px`);
    continuum.style.setProperty("--aware-oy", `${originY}px`);
  }

  function clearPresence() {
    if (presenceTimer !== null) {
      window.clearTimeout(presenceTimer);
      presenceTimer = null;
    }

    presenceActive = false;
    document.body.classList.remove("aware-prototype-present");
  }

  function activatePresence() {
    if (presenceActive || cameraHasStarted()) {
      return;
    }

    setOpticalOrigin();
    presenceActive = true;
    document.body.classList.add("aware-prototype-present");
  }

  function schedulePresence() {
    if (presenceActive || cameraHasStarted()) {
      return;
    }

    if (!manifestoFinished() || !isMonogramFullyVisible()) {
      if (presenceTimer !== null) {
        window.clearTimeout(presenceTimer);
        presenceTimer = null;
      }

      return;
    }

    if (presenceTimer !== null) {
      return;
    }

    presenceTimer = window.setTimeout(() => {
      presenceTimer = null;

      if (manifestoFinished() && isMonogramFullyVisible() && !cameraHasStarted()) {
        activatePresence();
      }
    }, PRESENCE_DELAY_MS);
  }

  function updatePresence() {
    if (cameraHasStarted()) {
      clearPresence();
      return;
    }

    schedulePresence();
  }

  const cameraObserver = new MutationObserver(updatePresence);
  cameraObserver.observe(document.body, {
    attributes: true,
    attributeFilter: ["data-camera-prototype"],
  });

  window.addEventListener("scroll", updatePresence, { passive: true });
  window.addEventListener("resize", updatePresence);

  updatePresence();
})();
