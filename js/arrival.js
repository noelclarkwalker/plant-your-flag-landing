document.addEventListener("DOMContentLoaded", () => {
  const scene02 = document.querySelector("#scene-02");
  const storyRing = document.querySelector("#scene-02 .story-ring");
  const profile = document.querySelector("#scene-02 .profile");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const ringLight = document.querySelector("#scene-02 .ring-light");
  const memoryScene = document.querySelector("#scene-02 .memory-scene");
  const narrativeVoice = document.querySelector("#scene-02 .narrative-voice");
  const voiceLines = narrativeVoice
    ? [...narrativeVoice.querySelectorAll(".social-text")]
    : [];
  const voiceLineTwo = voiceLines[1];
  const voiceLineThree = voiceLines[2];
  const homeMemory = document.querySelector("#scene-02 .home-memory");

  if (
    !storyRing ||
    !scene02 ||
    !profile ||
    !socialPost ||
    !ringLight ||
    !memoryScene
  ) {
    return;
  }

  const AUTO_OPEN_DELAY_MS = 6000;

  const BEAT_0_THRESHOLD_MS = 200;
  const BEAT_2_PROFILE_MS = 800;
  const BEAT_3_CARD_MS = 1200;
  const BEAT_4_RING_MS = 700;
  const BEAT_5_HOLD_MS = 1500;

  const READING_BREATH_MS = 900;
  const VOICE_CONTINUE_MS = 1200;
  const TWO_LINE_HOLD_MS = 2000;

  const AFTERGLOW_MS = 400;
  const ATTENTION_CONTINUE_MS = 1800;
  const WORDS_MEMORY_SETTLE_MS = 650;
  const THREE_LINE_HOLD_MS = 2400;

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

  function unlockArrivalScroll() {
    document.documentElement.style.overflow = "";
    document.body.style.overflow = "";
  }

  function settleStoryRing() {
    ringLight.style.animation = "none";
    ringLight.classList.add("story-ring-settled");
  }

  function continueVoice(timeline, line) {
    timeline.call(() => {
      line.removeAttribute("aria-hidden");
    });

    timeline.to(line, {
      opacity: 1,
      duration: VOICE_CONTINUE_MS / 1000,
      ease: "power1.inOut",
    });
  }

  function showVoiceLine(line) {
    if (!line) {
      return;
    }

    line.removeAttribute("aria-hidden");
    gsap.set(line, { opacity: 1 });
  }

  function prepareMemoryScene() {
    if (voiceLineThree) {
      voiceLineThree.removeAttribute("aria-hidden");
      gsap.set(voiceLineThree, { opacity: 1 });
    }

    if (homeMemory) {
      homeMemory.removeAttribute("aria-hidden");
    }

    const sceneRect = memoryScene.getBoundingClientRect();
    const lineTwoRect = voiceLineTwo.getBoundingClientRect();
    const clipBottom = Math.max(0, sceneRect.bottom - lineTwoRect.bottom);

    memoryScene.style.setProperty("--memory-scene-clip", `${clipBottom}px`);
    memoryScene.classList.add("memory-scene-ready");
  }

  function inviteMemoryAttention() {
    memoryScene.classList.add("memory-scene-attention");
  }

  function applyReducedMotionVoiceContinuation() {
    showVoiceLine(voiceLineTwo);
  }

  function applyReducedMotionHomeExperience() {
    prepareMemoryScene();
    inviteMemoryAttention();
  }

  function beginHomeExperience() {
    if (!voiceLineThree || !homeMemory || typeof gsap === "undefined") {
      return;
    }

    if (prefersReducedMotion()) {
      inviteMemoryAttention();
      return;
    }

    const timeline = gsap.timeline();

    timeline.call(inviteMemoryAttention);
    timeline.to({}, { duration: AFTERGLOW_MS / 1000 });
    timeline.to({}, { duration: READING_BREATH_MS / 1000 });
    timeline.to({}, { duration: ATTENTION_CONTINUE_MS / 1000 });
    timeline.to({}, { duration: WORDS_MEMORY_SETTLE_MS / 1000 });
    timeline.to({}, { duration: THREE_LINE_HOLD_MS / 1000 });
  }

  function beginNarrativeContinuation() {
    if (!voiceLineTwo || typeof gsap === "undefined") {
      return;
    }

    if (prefersReducedMotion()) {
      applyReducedMotionVoiceContinuation();
      prepareMemoryScene();
      beginHomeExperience();
      return;
    }

    const timeline = gsap.timeline({
      onComplete: beginHomeExperience,
    });

    timeline.to({}, { duration: READING_BREATH_MS / 1000 });
    continueVoice(timeline, voiceLineTwo);
    timeline.call(prepareMemoryScene);
    timeline.to({}, { duration: TWO_LINE_HOLD_MS / 1000 });
  }

  function completeArrivalSequence() {
    unlockArrivalScroll();
    beginNarrativeContinuation();
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
    applyReducedMotionVoiceContinuation();
    applyReducedMotionHomeExperience();
  }

  function startSocialDissolve() {
    if (typeof gsap === "undefined") {
      completeArrivalSequence();
      return;
    }

    if (prefersReducedMotion()) {
      applyReducedMotionEndState();
      completeArrivalSequence();
      return;
    }

    const timeline = gsap.timeline({
      defaults: { ease: CINEMATIC_EASE },
      onComplete: completeArrivalSequence,
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
