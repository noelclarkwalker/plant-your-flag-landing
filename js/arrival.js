/**
 * Feature 01 — Arrival
 *
 * Profile click or idle timeout begins platform surrender.
 * Manifesto remains hidden until the dissolve sequence starts.
 * The bridge sentence never disappears.
 */
document.addEventListener("DOMContentLoaded", () => {
  const experienceBridge = document.querySelector("#scene-02 .experience-bridge");
  const continuousSurface = document.querySelector("#scene-02 .continuous-surface");
  const storyRing = document.querySelector("#scene-02 .story-ring");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const ringLight = document.querySelector("#scene-02 .ring-light");
  const manifestoDeclaration = document.querySelector(
    "#scene-02 .cinema-manifesto__declaration",
  );

  const photoChrome = document.querySelector("#scene-02 .social-chrome--photo");
  const ringChrome = document.querySelector("#scene-02 .social-chrome--ring");
  const likeChrome = document.querySelector("#scene-02 .social-chrome--like");
  const likeCountChrome = document.querySelector("#scene-02 .social-chrome--like-count");
  const commentChrome = document.querySelector("#scene-02 .social-chrome--comment");
  const commentCountChrome = document.querySelector(
    "#scene-02 .social-chrome--comment-count",
  );
  const composerChrome = document.querySelector("#scene-02 .social-chrome--composer");
  const saveChrome = document.querySelector("#scene-02 .social-chrome--save");
  const saveCountChrome = document.querySelector("#scene-02 .social-chrome--save-count");
  const handleChrome = document.querySelector("#scene-02 .social-chrome--handle");
  const purposeLine = document.querySelector(
    "#scene-02 .manifesto-line--purpose",
  );
  const portalSignature = document.querySelector("#scene-02 .portal-signature");

  if (
    !experienceBridge ||
    !continuousSurface ||
    !storyRing ||
    !socialPost ||
    !ringLight ||
    !photoChrome ||
    !manifestoDeclaration
  ) {
    return;
  }

  const BREATH_HALF_MS = 1500;
  const BREATH_CYCLE_MS = BREATH_HALF_MS * 2;
  const IDLE_BREATH_CYCLES = 2;
  const DISSOLVE_DURATION_S = 4.4;
  const MANIFESTO_REVEAL_AT = 0.52;

  let dissolveTimeline = null;
  let autoOpenTimer = null;
  let transitionStarted = false;
  let manifestoRevealed = false;
  let signatureRevealed = false;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function clamp01(value) {
    return Math.min(1, Math.max(0, value));
  }

  function segmentOpacity(progress, start, end) {
    if (progress <= start) {
      return 1;
    }

    if (progress >= end) {
      return 0;
    }

    return 1 - (progress - start) / (end - start);
  }

  function setChromeOpacity(element, opacity) {
    if (!element) {
      return;
    }

    element.style.setProperty("--chrome-opacity", String(opacity));

    if (opacity <= 0.02) {
      element.classList.add("social-chrome--hidden");
    } else {
      element.classList.remove("social-chrome--hidden");
    }
  }

  function revealManifesto() {
    if (manifestoRevealed) {
      return;
    }

    manifestoRevealed = true;
    manifestoDeclaration.classList.add("is-revealed");
    manifestoDeclaration.setAttribute("aria-hidden", "false");
    socialPost.classList.add("manifesto-active");
  }

  function applyManifestoReveal(progress) {
    if (clamp01(progress) >= MANIFESTO_REVEAL_AT) {
      revealManifesto();
    }
  }

  function applyPlatformSurrender(progress) {
    const p = clamp01(progress);

    setChromeOpacity(photoChrome, segmentOpacity(p, 0, 0.1));
    setChromeOpacity(ringChrome, segmentOpacity(p, 0, 0.1));
    setChromeOpacity(likeChrome, segmentOpacity(p, 0.08, 0.18));
    setChromeOpacity(likeCountChrome, segmentOpacity(p, 0.16, 0.26));
    setChromeOpacity(commentChrome, segmentOpacity(p, 0.24, 0.34));
    setChromeOpacity(composerChrome, segmentOpacity(p, 0.24, 0.34));
    setChromeOpacity(commentCountChrome, segmentOpacity(p, 0.32, 0.42));
    setChromeOpacity(saveChrome, segmentOpacity(p, 0.4, 0.5));
    setChromeOpacity(saveCountChrome, segmentOpacity(p, 0.48, 0.58));
    setChromeOpacity(handleChrome, segmentOpacity(p, 0.56, 0.66));
    socialPost.style.setProperty(
      "--frame-presence",
      String(segmentOpacity(p, 0.62, 0.74)),
    );
    socialPost.style.setProperty(
      "--border-presence",
      String(segmentOpacity(p, 0.72, 0.84)),
    );
    socialPost.style.setProperty(
      "--platform-presence",
      String(segmentOpacity(p, 0.82, 0.94)),
    );

    applyManifestoReveal(p);
  }

  function revealSignaturePermanent() {
    if (signatureRevealed || !portalSignature) {
      return;
    }

    signatureRevealed = true;
    portalSignature.classList.add("is-revealed");
  }

  function finishPlatformSurrender() {
    applyPlatformSurrender(1);
    revealManifesto();
    socialPost.classList.add("platform-surrendered");
  }

  function initSignatureReveal() {
    if (!purposeLine || !portalSignature) {
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && manifestoRevealed) {
            revealSignaturePermanent();
          }
        });
      },
      { threshold: 0.6 },
    );

    observer.observe(purposeLine);
  }

  function cancelAutoOpen() {
    if (autoOpenTimer !== null) {
      clearTimeout(autoOpenTimer);
      autoOpenTimer = null;
    }
  }

  function getIdleBreathDelayMs() {
    const animation = ringLight.getAnimations()[0];
    const twoCyclesMs = IDLE_BREATH_CYCLES * BREATH_CYCLE_MS;

    if (!animation || animation.currentTime === null) {
      return twoCyclesMs;
    }

    const elapsed = Number(animation.currentTime);
    const timeAtFire = elapsed + twoCyclesMs;
    const positionAtFire = timeAtFire % BREATH_CYCLE_MS;
    const alignToExhaleEnd =
      positionAtFire === 0 ? 0 : BREATH_CYCLE_MS - positionAtFire;

    return twoCyclesMs + alignToExhaleEnd;
  }

  function beginTransition() {
    if (transitionStarted) {
      return;
    }

    transitionStarted = true;
    cancelAutoOpen();
    document.body.classList.add("story-opening");

    if (prefersReducedMotion()) {
      finishPlatformSurrender();
      revealSignaturePermanent();
      return;
    }

    if (typeof gsap === "undefined") {
      finishPlatformSurrender();
      return;
    }

    if (dissolveTimeline) {
      dissolveTimeline.kill();
    }

    const dissolveState = { progress: 0 };

    dissolveTimeline = gsap.to(dissolveState, {
      progress: 1,
      duration: DISSOLVE_DURATION_S,
      ease: "none",
      onUpdate: () => {
        applyPlatformSurrender(dissolveState.progress);
      },
      onComplete: finishPlatformSurrender,
    });
  }

  function scheduleAutoOpen() {
    cancelAutoOpen();
    autoOpenTimer = setTimeout(beginTransition, getIdleBreathDelayMs());
  }

  function initAutoOpenObserver() {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            scheduleAutoOpen();
          } else {
            cancelAutoOpen();
          }
        });
      },
      { threshold: 0.45 },
    );

    observer.observe(continuousSurface);
  }

  storyRing.addEventListener("click", (event) => {
    event.preventDefault();
    beginTransition();
  });

  window.beginArrivalTransition = beginTransition;

  initSignatureReveal();
  initAutoOpenObserver();
});
