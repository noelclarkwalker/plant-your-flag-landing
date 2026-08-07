document.addEventListener("DOMContentLoaded", () => {
  const scene02 = document.querySelector("#scene-02");
  const storyRing = document.querySelector("#scene-02 .story-ring");
  const profile = document.querySelector("#scene-02 .profile");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const ringLight = document.querySelector("#scene-02 .ring-light");

  if (!storyRing || !scene02 || !profile || !socialPost || !ringLight) {
    return;
  }

  const AUTO_OPEN_DELAY_MS = 6000;

  const BEAT_0_THRESHOLD_MS = 200;
  const BEAT_2_PROFILE_MS = 800;
  const BEAT_3_CARD_MS = 1200;
  const BEAT_4_RING_MS = 700;
  const BEAT_5_HOLD_MS = 1500;

  const CINEMATIC_EASE = "power2.inOut";
  const RING_BREATH_IN_MS = BEAT_4_RING_MS / 2;
  const RING_BREATH_OUT_MS = BEAT_4_RING_MS / 2;

  let storyOpened = false;
  let autoOpenTimer = null;

  function cancelAutoOpen() {
    if (autoOpenTimer !== null) {
      clearTimeout(autoOpenTimer);
      autoOpenTimer = null;
    }
  }

  function scheduleAutoOpen() {
    cancelAutoOpen();
    autoOpenTimer = setTimeout(openStory, AUTO_OPEN_DELAY_MS);
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
      { threshold: 0.5 },
    );

    observer.observe(scene02);
  }

  function markStoryOpening() {
    document.body.classList.add("story-opening");
  }

  function lockArrivalScroll() {
    document.documentElement.style.overflow = "hidden";
    document.body.style.overflow = "hidden";
  }

  function unlockArrivalScroll() {
    document.documentElement.style.overflow = "";
    document.body.style.overflow = "";
  }

  function settleStoryRing() {
    ringLight.style.animation = "none";
    ringLight.classList.add("story-ring-settled");
  }

  function applyReducedMotionEndState() {
    gsap.set(profile, { opacity: 0 });
    profile.style.visibility = "hidden";

    gsap.set(socialPost, {
      backgroundColor: "rgba(18, 18, 18, 0)",
      borderColor: "rgba(255, 255, 255, 0)",
      boxShadow: "0 0 0 rgba(0, 0, 0, 0)",
      backdropFilter: "blur(0px)",
    });

    settleStoryRing();
  }

  function startSocialDissolve() {
    if (typeof gsap === "undefined") {
      unlockArrivalScroll();
      return;
    }

    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    ).matches;

    if (prefersReducedMotion) {
      applyReducedMotionEndState();
      unlockArrivalScroll();
      return;
    }

    const timeline = gsap.timeline({
      defaults: { ease: CINEMATIC_EASE },
      onComplete: unlockArrivalScroll,
    });

    timeline.to({}, { duration: BEAT_0_THRESHOLD_MS / 1000 });

    timeline.to(profile, {
      opacity: 0,
      duration: BEAT_2_PROFILE_MS / 1000,
      onComplete: () => {
        profile.style.visibility = "hidden";
      },
    });

    timeline.to(
      socialPost,
      {
        backgroundColor: "rgba(18, 18, 18, 0)",
        borderColor: "rgba(255, 255, 255, 0)",
        boxShadow: "0 0 0 rgba(0, 0, 0, 0)",
        backdropFilter: "blur(0px)",
        duration: BEAT_3_CARD_MS / 1000,
      },
      ">",
    );

    timeline.call(() => {
      ringLight.style.animation = "none";
    });

    timeline.fromTo(
      ringLight,
      { opacity: 0.88, filter: "blur(24px)" },
      {
        opacity: 0.94,
        filter: "blur(28px)",
        duration: RING_BREATH_IN_MS / 1000,
        ease: "sine.inOut",
      },
    );

    timeline.to(ringLight, {
      opacity: 0.91,
      filter: "blur(26px)",
      duration: RING_BREATH_OUT_MS / 1000,
      ease: "sine.inOut",
      onComplete: settleStoryRing,
    });

    timeline.to({}, { duration: BEAT_5_HOLD_MS / 1000 }, ">");
  }

  function beginArrivalTransition() {
    lockArrivalScroll();
    startSocialDissolve();
  }

  function openStory() {
    if (storyOpened) {
      return;
    }

    storyOpened = true;
    cancelAutoOpen();
    markStoryOpening();
    beginArrivalTransition();
  }

  storyRing.addEventListener("click", openStory);
  initAutoOpenObserver();
});
