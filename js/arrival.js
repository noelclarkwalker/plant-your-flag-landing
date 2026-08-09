/**
 * Feature 01 — Arrival
 *
 * Permanent principle: Borrowed land lifts away instead of remembered home
 * appearing. The Memory Field is uncovered beneath the borrowed veil — never
 * faded in as an asset or loaded as a new scene. See docs/DECISIONS.md.
 */
document.addEventListener("DOMContentLoaded", () => {
  const scene02 = document.querySelector("#scene-02");
  const storyRing = document.querySelector("#scene-02 .story-ring");
  const profile = document.querySelector("#scene-02 .profile");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const ringLight = document.querySelector("#scene-02 .ring-light");
  const memoryField = document.querySelector("#scene-02 .memory-field");
  const memoryEnvironment = document.querySelector(
    "#scene-02 .memory-field__environment",
  );
  const borrowedVeil = document.querySelector("#scene-02 .borrowed-veil");
  const narrativeVoice = document.querySelector("#scene-02 .narrative-voice");
  const journalLines = narrativeVoice
    ? [...narrativeVoice.querySelectorAll(".social-text")]
    : [];
  const waybackMemory = document.querySelector("#scene-02 .home-memory");

  if (
    !storyRing ||
    !scene02 ||
    !profile ||
    !socialPost ||
    !ringLight ||
    !memoryField ||
    !memoryEnvironment ||
    !borrowedVeil
  ) {
    return;
  }

  const AUTO_OPEN_DELAY_MS = 6000;

  const BEAT_0_THRESHOLD_MS = 200;
  const BEAT_2_PROFILE_MS = 800;
  const BEAT_3_CARD_MS = 1200;
  const BEAT_4_RING_MS = 700;
  const BEAT_5_HOLD_MS = 1500;

  const PLACE_RETURN_MS = BEAT_2_PROFILE_MS + BEAT_3_CARD_MS;

  const CINEMATIC_EASE = "power2.inOut";
  const RING_BREATH_IN_MS = BEAT_4_RING_MS / 2;
  const RING_BREATH_OUT_MS = BEAT_4_RING_MS / 2;

  let storyOpened = false;
  let autoOpenTimer = null;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

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

  function settleStoryRing() {
    ringLight.style.animation = "none";
    ringLight.classList.add("story-ring-settled");
  }

  function revealJournal() {
    journalLines.forEach((line) => {
      line.removeAttribute("aria-hidden");
    });

    if (waybackMemory) {
      waybackMemory.removeAttribute("aria-hidden");
    }
  }

  function prepareRememberedPlace() {
    memoryField.removeAttribute("aria-hidden");
    gsap.set(memoryField, { opacity: 1 });
    gsap.set(memoryEnvironment, { scale: 1.028 });
    gsap.set(borrowedVeil, { opacity: 1 });
  }

  function activateMemoryField() {
    document.body.classList.remove("story-opening");
    document.body.classList.add("memory-field-active");
    gsap.set(borrowedVeil, { opacity: 0 });
    gsap.set(memoryEnvironment, { scale: 1 });
    revealJournal();
  }

  function applyReducedMotionCrossingEndState() {
    gsap.set(profile, { opacity: 0 });
    profile.style.visibility = "hidden";

    gsap.set(socialPost, {
      backgroundColor: "rgba(18, 18, 18, 0)",
      borderColor: "rgba(255, 255, 255, 0)",
      boxShadow: "0 0 0 rgba(0, 0, 0, 0)",
      backdropFilter: "blur(0px)",
    });

    memoryField.removeAttribute("aria-hidden");
    gsap.set(memoryField, { opacity: 1 });
    gsap.set(memoryEnvironment, { scale: 1 });
    gsap.set(borrowedVeil, { opacity: 0 });
    gsap.set(ringLight, {
      opacity: 0.91,
      filter: "blur(26px)",
      scale: 1,
    });

    settleStoryRing();
    activateMemoryField();
  }

  function startCrossing() {
    if (typeof gsap === "undefined") {
      activateMemoryField();
      return;
    }

    if (prefersReducedMotion()) {
      applyReducedMotionCrossingEndState();
      return;
    }

    prepareRememberedPlace();

    const timeline = gsap.timeline({
      defaults: { ease: CINEMATIC_EASE },
      onComplete: activateMemoryField,
    });

    timeline.to({}, { duration: BEAT_0_THRESHOLD_MS / 1000 });

    timeline.to(
      borrowedVeil,
      {
        opacity: 0,
        duration: PLACE_RETURN_MS / 1000,
        ease: "power1.inOut",
      },
      ">",
    );

    timeline.to(
      memoryEnvironment,
      {
        scale: 1,
        duration: PLACE_RETURN_MS / 1000,
        ease: "power1.out",
      },
      "<",
    );

    timeline.to(
      profile,
      {
        opacity: 0,
        duration: BEAT_2_PROFILE_MS / 1000,
        onComplete: () => {
          profile.style.visibility = "hidden";
        },
      },
      "<0.1",
    );

    timeline.to(
      socialPost,
      {
        backgroundColor: "rgba(18, 18, 18, 0)",
        borderColor: "rgba(255, 255, 255, 0)",
        boxShadow: "0 0 0 rgba(0, 0, 0, 0)",
        backdropFilter: "blur(0px)",
        duration: BEAT_3_CARD_MS / 1000,
      },
      "<0.15",
    );

    timeline.call(() => {
      ringLight.style.animation = "none";
    });

    timeline.fromTo(
      ringLight,
      { opacity: 0.88, filter: "blur(24px)", scale: 1 },
      {
        opacity: 0.96,
        filter: "blur(32px)",
        scale: 2.6,
        duration: RING_BREATH_IN_MS / 1000,
        ease: "sine.inOut",
      },
    );

    timeline.to(ringLight, {
      opacity: 0.91,
      filter: "blur(26px)",
      scale: 1,
      duration: RING_BREATH_OUT_MS / 1000,
      ease: "sine.inOut",
      onComplete: settleStoryRing,
    });

    timeline.to({}, { duration: BEAT_5_HOLD_MS / 1000 }, ">");
  }

  function beginArrivalTransition() {
    lockArrivalScroll();
    startCrossing();
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
