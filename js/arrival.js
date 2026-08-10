/**
 * Feature 01 — Arrival
 *
 * One cinematic shot. The glow expands into soft white light. As the light
 * recedes, the visitor realizes they are reading remembered NoelClark.com.
 * The page was already there — clarity returns over two seconds.
 *
 * Story Ring idle pulse: CSS storyRingGlow in social.css (approved).
 * Ring stays inside .story-ring-wrapper — never reparented.
 */
document.addEventListener("DOMContentLoaded", () => {
  const scene02 = document.querySelector("#scene-02");
  const storyRing = document.querySelector("#scene-02 .story-ring");
  const socialPost = document.querySelector("#scene-02 .social-post");
  const ringLight = document.querySelector("#scene-02 .ring-light");
  const rememberedHome = document.querySelector("#scene-02 .remembered-home");
  const rememberedPage = document.querySelector(
    "#scene-02 .remembered-home__page",
  );
  const rememberingLight = document.querySelector("#scene-02 .remembering-light");

  if (
    !storyRing ||
    !scene02 ||
    !socialPost ||
    !ringLight ||
    !rememberedHome ||
    !rememberedPage ||
    !rememberingLight
  ) {
    return;
  }

  /* Idle breath timing — must match storyRingGlow / storyRingAlive in social.css */
  const BREATH_HALF_MS = 1500;
  const BREATH_CYCLE_MS = BREATH_HALF_MS * 2;
  const IDLE_BREATH_CYCLES = 2;

  const BEAT_0_THRESHOLD_MS = 200;
  const GLOW_EXPAND_MS = 950;
  const WHITE_FILL_MS = 450;
  const WHITE_HOLD_MS = 380;
  const WHITE_RECEDE_MS = 750;
  const CLARITY_MS = 2000;

  const CINEMATIC_EASE = "power2.inOut";

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

  function scheduleAutoOpen() {
    cancelAutoOpen();
    autoOpenTimer = setTimeout(openStory, getIdleBreathDelayMs());
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

  function clearStoryRingInlineStyles() {
    ringLight.classList.remove("story-ring-settled");
    gsap.set(ringLight, {
      clearProps: "scale,opacity,filter,transform",
    });
    ringLight.style.removeProperty("animation");
  }

  function settleRemembering() {
    clearStoryRingInlineStyles();
    document.body.classList.remove("story-opening");
    document.body.classList.add("remembering-active");
    gsap.set(socialPost, { opacity: 0, visibility: "hidden" });
    gsap.set(rememberingLight, { opacity: 0 });
    gsap.set(rememberedHome, { opacity: 1 });
    gsap.set(rememberedPage, { filter: "blur(0px)", opacity: 1 });
  }

  function applyReducedMotionEndState() {
    rememberedHome.removeAttribute("aria-hidden");
    rememberingLight.removeAttribute("aria-hidden");
    settleRemembering();
  }

  function startRemembering() {
    if (typeof gsap === "undefined") {
      rememberedHome.removeAttribute("aria-hidden");
      settleRemembering();
      return;
    }

    if (prefersReducedMotion()) {
      applyReducedMotionEndState();
      return;
    }

    rememberedHome.removeAttribute("aria-hidden");
    rememberingLight.removeAttribute("aria-hidden");
    gsap.set(rememberedHome, { opacity: 0 });
    gsap.set(rememberingLight, { opacity: 0 });
    gsap.set(rememberedPage, { filter: "blur(5px)", opacity: 0.78 });

    const timeline = gsap.timeline({
      defaults: { ease: CINEMATIC_EASE },
      onComplete: settleRemembering,
    });

    timeline.call(() => {
      ringLight.style.animationPlayState = "paused";
      storyRing.style.animationPlayState = "paused";
    });

    timeline.to({}, { duration: BEAT_0_THRESHOLD_MS / 1000 });

    timeline.add("glow");

    timeline.call(() => {
      ringLight.style.animation = "none";
      storyRing.style.animation = "none";
    }, null, "glow");

    timeline.fromTo(
      ringLight,
      { opacity: 0.88, filter: "blur(24px)", scale: 1 },
      {
        opacity: 1,
        filter: "blur(64px)",
        scale: 18,
        duration: GLOW_EXPAND_MS / 1000,
        ease: "sine.inOut",
      },
      "glow",
    );

    timeline.to(
      socialPost,
      {
        opacity: 0,
        duration: GLOW_EXPAND_MS / 1000,
        ease: "power1.inOut",
      },
      "glow+=0.1",
    );

    timeline.to(
      rememberingLight,
      {
        opacity: 1,
        duration: WHITE_FILL_MS / 1000,
        ease: "power1.in",
      },
      `glow+=${(GLOW_EXPAND_MS * 0.48) / 1000}`,
    );

    timeline.to(
      ringLight,
      {
        opacity: 0,
        duration: WHITE_FILL_MS / 1000,
        ease: "power1.in",
      },
      "<",
    );

    timeline.to({}, { duration: WHITE_HOLD_MS / 1000 });

    timeline.add("recede");

    timeline.call(() => {
      gsap.set(rememberedHome, { opacity: 1 });
    }, null, "recede");

    timeline.to(
      rememberingLight,
      {
        opacity: 0,
        duration: WHITE_RECEDE_MS / 1000,
        ease: "power1.inOut",
      },
      "recede",
    );

    timeline.to(
      rememberedPage,
      {
        filter: "blur(0px)",
        opacity: 1,
        duration: CLARITY_MS / 1000,
        ease: "power1.out",
      },
      "recede+=0.12",
    );
  }

  function beginArrivalTransition() {
    lockArrivalScroll();
    startRemembering();
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
