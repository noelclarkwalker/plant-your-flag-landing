/**
 * Feature 01 — Arrival
 *
 * Circular invitation click begins platform surrender.
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

  const DISSOLVE_DURATION_S = 4.4;
  const MANIFESTO_REVEAL_AT = 0.52;

  let dissolveTimeline = null;
  let transitionStarted = false;
  let manifestoRevealed = false;
  let signatureRevealed = false;
  let handoffReleaseBound = false;

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
    if (!socialPost.classList.contains("platform-surrendered")) {
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
    bindHandoffRelease();
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

  function beginTransition() {
    if (transitionStarted) {
      return;
    }

    transitionStarted = true;

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
    beginTransition();
  });

  window.beginArrivalTransition = beginTransition;

  initSignatureReveal();
});
