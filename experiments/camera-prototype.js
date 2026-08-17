/**
 * CAMERA PROTOTYPE — throwaway
 *
 * Phase 1 — Reading: normal scroll until manifesto done AND full monogram visible.
 * Phase 2 — Camera: next scroll down freezes page; dolly rolls with inertia.
 *
 * Final extension: dolly continues until watercolor nearly fills frame.
 *
 * Remove: this file, camera-prototype.css, index.html lines.
 */
(function () {
  const MAX_CAMERA_Z = 1820;
  const DOLLY_MS = 5200;
  const FULLY_VISIBLE_INSET = 10;
  const ALPHA_THRESHOLD = 12;

  const signature = document.querySelector("#scene-02 .portal-signature");
  const plane = document.querySelector("#scene-02 .continuous-surface");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const monogram = signature?.querySelector("img");

  if (!signature || !plane || !socialPost || !monogram) {
    return;
  }

  /** @type {"reading" | "camera" | "complete"} */
  let state = "reading";
  let rigInstalled = false;
  let cameraArmed = false;
  let dollyStartTime = 0;
  /** @type {{ x: number, y: number } | null} */
  let opticalCenterNorm = null;

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

  function easeDollyRail(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  }

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

  function updateCameraArm() {
    if (state !== "reading") {
      return;
    }

    cameraArmed = manifestoFinished() && isMonogramFullyVisible();
  }

  function computeOpticalCenter() {
    if (!monogram.complete || monogram.naturalWidth === 0) {
      return null;
    }

    try {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d", { willReadFrequently: true });
      if (!ctx) {
        return null;
      }

      canvas.width = monogram.naturalWidth;
      canvas.height = monogram.naturalHeight;
      ctx.drawImage(monogram, 0, 0);

      const { data, width, height } = ctx.getImageData(0, 0, canvas.width, canvas.height);
      let sumX = 0;
      let sumY = 0;
      let weight = 0;

      for (let y = 0; y < height; y += 1) {
        for (let x = 0; x < width; x += 1) {
          const alpha = data[(y * width + x) * 4 + 3];

          if (alpha > ALPHA_THRESHOLD) {
            sumX += x * alpha;
            sumY += y * alpha;
            weight += alpha;
          }
        }
      }

      if (weight <= 0) {
        return { x: 0.5, y: 0.5 };
      }

      return {
        x: sumX / weight / width,
        y: sumY / weight / height,
      };
    } catch {
      return { x: 0.5, y: 0.5 };
    }
  }

  function ensureOpticalCenter() {
    if (!opticalCenterNorm) {
      opticalCenterNorm = computeOpticalCenter() || { x: 0.5, y: 0.5 };
    }
  }

  function installRig() {
    if (rigInstalled || plane.closest(".camera-prototype-viewport")) {
      rigInstalled = true;
      return;
    }

    const viewport = document.createElement("div");
    viewport.className = "camera-prototype-viewport";
    const rig = document.createElement("div");
    rig.className = "camera-prototype-plane";

    plane.parentNode.insertBefore(viewport, plane);
    viewport.appendChild(rig);
    rig.appendChild(plane);
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

  function setOpticalFraming() {
    ensureOpticalCenter();

    const viewport = plane.closest(".camera-prototype-viewport");
    if (!viewport || !opticalCenterNorm) {
      return;
    }

    const imgRect = monogram.getBoundingClientRect();
    const viewportRect = viewport.getBoundingClientRect();
    const opticalX = imgRect.left + opticalCenterNorm.x * imgRect.width;
    const opticalY = imgRect.top + opticalCenterNorm.y * imgRect.height;

    viewport.style.setProperty("--camera-optical-x", `${opticalX - viewportRect.left}px`);
    viewport.style.setProperty("--camera-optical-y", `${opticalY - viewportRect.top}px`);
  }

  function applyCamera(progress) {
    const rig = plane.closest(".camera-prototype-plane");
    if (!rig) {
      return;
    }

    rig.style.setProperty("--camera-z", `${progress * MAX_CAMERA_Z}px`);
  }

  function dollyFrame(now) {
    if (state !== "camera") {
      return;
    }

    const progress = Math.min(1, (now - dollyStartTime) / DOLLY_MS);
    applyCamera(easeDollyRail(progress));

    if (progress < 1) {
      requestAnimationFrame(dollyFrame);
      return;
    }

    applyCamera(1);
    state = "complete";
    document.body.dataset.cameraPrototype = "complete";
  }

  function startDollyRoll() {
    dollyStartTime = performance.now();
    requestAnimationFrame(dollyFrame);
  }

  function beginCameraPhase() {
    installRig();
    lockPageScroll();
    cameraArmed = false;
    state = "camera";
    document.body.dataset.cameraPrototype = "camera";

    requestAnimationFrame(() => {
      setOpticalFraming();
      applyCamera(0);
      startDollyRoll();
    });
  }

  function onWheel(event) {
    if (prefersReducedMotion()) {
      return;
    }

    const delta = normalizeWheelDelta(event);

    if (state === "complete" || state === "camera") {
      event.preventDefault();
      return;
    }

    updateCameraArm();

    if (delta <= 0 || !cameraArmed) {
      return;
    }

    if (!manifestoFinished() || !isMonogramFullyVisible()) {
      cameraArmed = false;
      return;
    }

    event.preventDefault();
    beginCameraPhase();
  }

  if (monogram.complete) {
    opticalCenterNorm = computeOpticalCenter();
  } else {
    monogram.addEventListener(
      "load",
      () => {
        opticalCenterNorm = computeOpticalCenter();
      },
      { once: true },
    );
  }

  window.addEventListener("wheel", onWheel, { passive: false });
  window.addEventListener("scroll", updateCameraArm, { passive: true });
  window.addEventListener("resize", updateCameraArm);

  document.body.dataset.cameraPrototype = "reading";
  updateCameraArm();
})();
