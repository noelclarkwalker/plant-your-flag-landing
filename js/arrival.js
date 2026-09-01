/**
 * Feature 01 — Arrival
 *
 * Circular invitation click begins platform surrender.
 * Approved idle continuation begins Crossing after two Story Ring breath cycles.
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
  const monogramControl = document.querySelector(
    "#scene-02 .portal-signature__control",
  );
  const homepageDestination = document.querySelector("#scene-03");

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
  let idleScrollSettleTimer = null;
  let transitionStarted = false;
  let manifestoRevealed = false;
  let signatureRevealed = false;
  let handoffReleaseBound = false;
  let compositionSettleMaxPx = null;
  let compositionFrozen = false;

  const portalStillnessConfig = {
    /** Time after declaration + monogram are in view before stillness begins (ms). */
    settleDelayMs: 900,
    /** Engineering default — adjust via PortalStillness.setConfig({ stillnessHoldMs: N }). */
    stillnessHoldMs: 3500,
    purposeLineIntersection: 0.55,
    monogramIntersection: 0.35,
  };
  let portalStillnessActive = false;
  let shot001Complete = false;
  let portalSettleTimer = null;
  let portalHoldTimer = null;
  let purposeLineVisible = false;
  let monogramVisible = false;
  let homepageHandoffComplete = false;

  const bridgeSentence = socialPost.querySelector(".bridge-sentence");
  const writingContinuum = document.querySelector("#scene-02 .writing-continuum");

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

  function captureSpatialHandoff() {
    if (!bridgeSentence) {
      return;
    }

    document.dispatchEvent(new CustomEvent("pyf:prepare-crossing"));

    const postRect = socialPost.getBoundingClientRect();
    const bridgeRect = bridgeSentence.getBoundingClientRect();
    const headerBlock = bridgeRect.top - postRect.top;

    if (headerBlock > 0) {
      socialPost.style.setProperty("--handoff-header-block", `${headerBlock}px`);
    }

    const footerLayoutHeight =
      postRect.bottom -
      bridgeRect.bottom -
      parseFloat(getComputedStyle(socialPost).paddingBottom);

    if (footerLayoutHeight > 0) {
      manifestoDeclaration.style.setProperty(
        "--manifesto-lift",
        `${footerLayoutHeight}px`,
      );
    }

    compositionSettleMaxPx = measureCompositionSettleTargetPx();
  }

  function measureCompositionSettleTargetPx() {
    const navBottom =
      document.querySelector(".site-nav")?.getBoundingClientRect().bottom ?? 0;
    const topInset =
      navBottom + Math.max(32, Math.round(window.innerHeight * 0.06));
    const bridgeTop = bridgeSentence.getBoundingClientRect().top;

    return Math.max(0, Math.round(bridgeTop - topInset));
  }

  function easeSettleSegment(value) {
    const t = clamp01(value);

    return t * t * (3 - 2 * t);
  }

  function getCompositionSettleMaxPx() {
    if (!manifestoRevealed) {
      return 0;
    }

    if (compositionSettleMaxPx !== null) {
      return compositionSettleMaxPx;
    }

    return measureCompositionSettleTargetPx();
  }

  function applyCompositionSettle(progress) {
    if (compositionFrozen || !writingContinuum) {
      return;
    }

    if (clamp01(progress) < MANIFESTO_REVEAL_AT) {
      writingContinuum.style.setProperty("--text-composition-settle", "0px");
      return;
    }

    const segment =
      (clamp01(progress) - MANIFESTO_REVEAL_AT) / (1 - MANIFESTO_REVEAL_AT);
    const offset = Math.round(getCompositionSettleMaxPx() * easeSettleSegment(segment));

    writingContinuum.style.setProperty(
      "--text-composition-settle",
      `-${offset}px`,
    );
  }

  function getAffectedCompositionBottom() {
    if (!writingContinuum) {
      return Infinity;
    }

    let maxBottom = -Infinity;
    let afterSocialPost = false;

    for (const child of writingContinuum.children) {
      if (child === socialPost) {
        afterSocialPost = true;
        continue;
      }

      if (!afterSocialPost) {
        continue;
      }

      if (getComputedStyle(child).display === "none") {
        continue;
      }

      maxBottom = Math.max(maxBottom, child.getBoundingClientRect().bottom);
    }

    return maxBottom === -Infinity ? 0 : maxBottom;
  }

  function releaseSpatialHandoff() {
    if (compositionFrozen || !socialPost.classList.contains("platform-surrendered")) {
      return;
    }

    const headerBlock = parseFloat(
      getComputedStyle(socialPost).getPropertyValue("--handoff-header-block"),
    );

    if (!headerBlock) {
      window.removeEventListener("scroll", releaseSpatialHandoff);
      handoffReleaseBound = false;
      return;
    }

    if (getAffectedCompositionBottom() <= 0) {
      socialPost.style.setProperty("--handoff-header-block", "0px");
      window.removeEventListener("scroll", releaseSpatialHandoff);
      handoffReleaseBound = false;
    }
  }

  function bindHandoffRelease() {
    if (handoffReleaseBound) {
      releaseSpatialHandoff();
      return;
    }

    handoffReleaseBound = true;
    window.addEventListener("scroll", releaseSpatialHandoff, { passive: true });
    releaseSpatialHandoff();
  }

  function isolateFooterChrome() {
    const footerChrome = [
      document.querySelector("#scene-02 .social-actions"),
      document.querySelector("#scene-02 .social-metrics"),
      document.querySelector("#scene-02 .comment-field"),
    ];

    footerChrome.forEach((element) => {
      if (!element) {
        return;
      }

      element.setAttribute("aria-hidden", "true");

      element
        .querySelectorAll("button, input, textarea, select, a[href], [tabindex]")
        .forEach((focusable) => {
          focusable.setAttribute("tabindex", "-1");
        });
    });
  }

  function revealManifesto() {
    if (manifestoRevealed) {
      return;
    }

    manifestoRevealed = true;
    manifestoDeclaration.classList.add("is-revealed");
    manifestoDeclaration.setAttribute("aria-hidden", "false");
    socialPost.classList.add("manifesto-active");
    isolateFooterChrome();
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
    applyCompositionSettle(p);
  }

  function freezeCompositionForPortal() {
    if (compositionFrozen) {
      return;
    }

    compositionFrozen = true;

    if (handoffReleaseBound) {
      window.removeEventListener("scroll", releaseSpatialHandoff);
      handoffReleaseBound = false;
    }
  }

  function settleRingMotion() {
    ringLight.classList.add("story-ring-settled");
    storyRing.getAnimations().forEach((animation) => animation.cancel());
    ringLight.getAnimations().forEach((animation) => animation.cancel());
  }

  function revealSignaturePermanent() {
    if (signatureRevealed || !portalSignature) {
      return;
    }

    signatureRevealed = true;
    portalSignature.classList.add("is-revealed");
    updatePortalSettleTimer();
  }

  function finishPlatformSurrender() {
    applyPlatformSurrender(1);
    revealManifesto();
    socialPost.classList.add("platform-surrendered");
    applyCompositionSettle(1);
    settleRingMotion();
    bindHandoffRelease();

    if (typeof window.Feature01State?.requestManifestoEntry === "function") {
      window.Feature01State.requestManifestoEntry();
    }

    updatePortalSettleTimer();
  }

  function canBeginPortalStillness() {
    return (
      socialPost.classList.contains("platform-surrendered") &&
      signatureRevealed &&
      purposeLineVisible &&
      monogramVisible
    );
  }

  function updatePortalSettleTimer() {
    if (portalStillnessActive || shot001Complete) {
      return;
    }

    if (!canBeginPortalStillness()) {
      if (portalSettleTimer !== null) {
        clearTimeout(portalSettleTimer);
        portalSettleTimer = null;
      }

      return;
    }

    if (portalSettleTimer !== null) {
      return;
    }

    portalSettleTimer = setTimeout(() => {
      portalSettleTimer = null;

      if (canBeginPortalStillness()) {
        beginPortalStillness();
      }
    }, portalStillnessConfig.settleDelayMs);
  }

  function completeShot001Hold() {
    portalHoldTimer = null;
    shot001Complete = true;
  }

  function enableMonogramHandoff() {
    if (!monogramControl || homepageHandoffComplete) {
      return;
    }

    monogramControl.disabled = false;
    document.body.classList.add("monogram-actionable");
  }

  function performHomepageHandoff() {
    if (
      homepageHandoffComplete ||
      !monogramControl ||
      !homepageDestination ||
      monogramControl.disabled
    ) {
      return;
    }

    homepageHandoffComplete = true;
    monogramControl.disabled = true;
    document.body.classList.add("homepage-entered");

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const scrollTop =
          homepageDestination.getBoundingClientRect().top +
          (window.scrollY || document.documentElement.scrollTop);

        window.scrollTo({
          top: Math.max(0, Math.round(scrollTop)),
          behavior: prefersReducedMotion() ? "auto" : "smooth",
        });
      });
    });
  }

  function initMonogramHandoff() {
    if (!monogramControl || !homepageDestination) {
      return;
    }

    monogramControl.addEventListener("click", (event) => {
      event.preventDefault();
      performHomepageHandoff();
    });
  }

  function beginPortalStillness() {
    if (portalStillnessActive) {
      return;
    }

    portalStillnessActive = true;
    freezeCompositionForPortal();

    if (typeof window.Feature01State?.requestPortalEntry === "function") {
      window.Feature01State.requestPortalEntry();
    }

    document.body.classList.add("portal-stillness");
    enableMonogramHandoff();

    if (portalHoldTimer !== null) {
      clearTimeout(portalHoldTimer);
    }

    const holdMs = portalStillnessConfig.stillnessHoldMs;

    if (holdMs <= 0) {
      completeShot001Hold();
      return;
    }

    portalHoldTimer = setTimeout(completeShot001Hold, holdMs);
  }

  function initPortalStillnessWatch() {
    if (!purposeLine || !portalSignature) {
      return;
    }

    const purposeObserver = new IntersectionObserver(
      (entries) => {
        purposeLineVisible = entries.some((entry) => entry.isIntersecting);
        updatePortalSettleTimer();
      },
      { threshold: portalStillnessConfig.purposeLineIntersection },
    );

    const monogramObserver = new IntersectionObserver(
      (entries) => {
        monogramVisible = entries.some((entry) => entry.isIntersecting);
        updatePortalSettleTimer();
      },
      { threshold: portalStillnessConfig.monogramIntersection },
    );

    purposeObserver.observe(purposeLine);
    monogramObserver.observe(portalSignature);
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

  function getAnimationElapsedMs(animation) {
    if (!animation || animation.currentTime == null) {
      return null;
    }

    const currentTime = animation.currentTime;

    if (typeof currentTime === "number") {
      return currentTime;
    }

    if (typeof currentTime === "object" && currentTime !== null && "value" in currentTime) {
      return Number(currentTime.value);
    }

    const parsed = Number(currentTime);
    return Number.isFinite(parsed) ? parsed : null;
  }

  function getIdleBreathDelayMs() {
    const animation = ringLight.getAnimations()[0];
    const twoCyclesMs = IDLE_BREATH_CYCLES * BREATH_CYCLE_MS;
    const elapsed = getAnimationElapsedMs(animation);

    if (elapsed === null) {
      return twoCyclesMs;
    }

    const timeAtFire = elapsed + twoCyclesMs;
    const positionAtFire = timeAtFire % BREATH_CYCLE_MS;
    const alignToExhaleEnd =
      positionAtFire === 0 ? 0 : BREATH_CYCLE_MS - positionAtFire;

    return twoCyclesMs + alignToExhaleEnd;
  }

  function scheduleAutoOpen() {
    if (transitionStarted) {
      cancelAutoOpen();
      return;
    }

    cancelAutoOpen();
    autoOpenTimer = setTimeout(() => {
      beginTransition();
    }, getIdleBreathDelayMs());
  }

  function maybeArmIdleContinuation() {
    if (
      transitionStarted ||
      autoOpenTimer !== null ||
      typeof window.Feature01State === "undefined"
    ) {
      return;
    }

    const state = window.Feature01State.getCurrentState?.();

    if (state === "staticSocialPost") {
      scheduleAutoOpen();
      return;
    }

    if (
      state === "borrowedLand" &&
      typeof window.Feature01State.reconcileForIdleContinuation === "function" &&
      window.Feature01State.reconcileForIdleContinuation()
    ) {
      scheduleAutoOpen();
    }
  }

  function initIdleContinuation() {
    if (
      typeof window.Feature01State === "undefined" ||
      typeof window.Feature01State.subscribeStaticSocialPostAuthority !== "function"
    ) {
      return;
    }

    window.Feature01State.subscribeStaticSocialPostAuthority(scheduleAutoOpen);
    maybeArmIdleContinuation();

    window.addEventListener(
      "scroll",
      () => {
        if (transitionStarted) {
          return;
        }

        if (idleScrollSettleTimer !== null) {
          clearTimeout(idleScrollSettleTimer);
        }

        idleScrollSettleTimer = setTimeout(() => {
          idleScrollSettleTimer = null;
          maybeArmIdleContinuation();
        }, 200);
      },
      { passive: true },
    );
  }

  function beginTransition({ force = false } = {}) {
    cancelAutoOpen();

    if (transitionStarted) {
      return;
    }

    transitionStarted = true;
    document.dispatchEvent(new CustomEvent("pyf:prepare-crossing"));

    if (
      typeof window.Feature01State === "undefined" ||
      typeof window.Feature01State.requestMemoryCrossingEntry !== "function"
    ) {
      transitionStarted = false;
      console.warn(
        "Feature 01: Feature01State unavailable; Memory Crossing entry aborted.",
      );
      return;
    }

    if (!window.Feature01State.requestMemoryCrossingEntry()) {
      transitionStarted = false;
      maybeArmIdleContinuation();
      console.warn(
        "Feature 01: Memory Crossing entry refused; Static Social Post is not authoritative.",
      );
      return;
    }

    document.body.classList.add("story-opening");
    captureSpatialHandoff();

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

  storyRing.addEventListener("click", (event) => {
    event.preventDefault();
    beginTransition({ force: true });
  });

  window.beginArrivalTransition = beginTransition;

  initSignatureReveal();
  initIdleContinuation();
  initPortalStillnessWatch();
  initMonogramHandoff();

  window.PortalStillness = Object.freeze({
    getConfig() {
      return { ...portalStillnessConfig };
    },

    setConfig(partial) {
      if (!partial || typeof partial !== "object") {
        return;
      }

      Object.assign(portalStillnessConfig, partial);
    },

    isShot001Complete() {
      return shot001Complete;
    },
  });
});
