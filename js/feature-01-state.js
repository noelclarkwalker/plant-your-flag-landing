/**
 * Feature 01 — State Owner
 *
 * Canonical Version 1.0 state vocabulary and authoritative current-state read access.
 * Implemented transitions: plantYourFlag → borrowedLand → staticSocialPost → memoryCrossing.
 * Static Social Post authority follows meaningful social-post presence, not Story Ring activation.
 */
(function () {
  const STATE_VOCABULARY = Object.freeze([
    "plantYourFlag",
    "borrowedLand",
    "staticSocialPost",
    "memoryCrossing",
    "manifesto",
    "portal",
    "emergence",
    "closingPlantYourFlag",
    "wander",
  ]);

  let currentState = "plantYourFlag";

  let storyRing = null;
  let socialPost = null;
  let openingField = null;
  let touchStartY = null;
  let pendingForwardIntent = false;
  let intentScrollY = 0;
  let entryListenersBound = false;
  let completionListenersBound = false;
  let staticSocialPostAuthorityNotified = false;
  const staticSocialPostAuthorityListeners = [];

  function notifyStaticSocialPostAuthority() {
    if (staticSocialPostAuthorityNotified || currentState !== "staticSocialPost") {
      return;
    }

    staticSocialPostAuthorityNotified = true;

    for (const listener of staticSocialPostAuthorityListeners) {
      listener();
    }

    staticSocialPostAuthorityListeners.length = 0;
  }

  function subscribeStaticSocialPostAuthority(listener) {
    if (typeof listener !== "function") {
      return;
    }

    if (staticSocialPostAuthorityNotified) {
      listener();
      return;
    }

    staticSocialPostAuthorityListeners.push(listener);
  }

  function getScrollY() {
    return window.scrollY || document.documentElement.scrollTop || 0;
  }

  function isSocialPostMeaningfullyPresent() {
    if (!socialPost) {
      return false;
    }

    const postRect = socialPost.getBoundingClientRect();
    const viewportHeight = window.innerHeight;

    if (postRect.height <= 0 || viewportHeight <= 0) {
      return false;
    }

    const visibleTop = Math.max(postRect.top, 0);
    const visibleBottom = Math.min(postRect.bottom, viewportHeight);
    const visibleHeight = Math.max(0, visibleBottom - visibleTop);
    const visibleRatio = visibleHeight / postRect.height;

    if (visibleRatio < 0.28) {
      return false;
    }

    if (postRect.top > viewportHeight * 0.72) {
      return false;
    }

    if (!openingField) {
      return true;
    }

    const heroRect = openingField.getBoundingClientRect();

    if (heroRect.bottom > viewportHeight * 0.88 && postRect.top > viewportHeight * 0.35) {
      return false;
    }

    return true;
  }

  function hasLegitimateSocialPostArrival() {
    return isSocialPostMeaningfullyPresent();
  }

  function reconcileStaticSocialPostAuthority() {
    if (currentState === "staticSocialPost" || currentState === "memoryCrossing") {
      return;
    }

    if (!isSocialPostMeaningfullyPresent()) {
      return;
    }

    if (currentState === "borrowedLand") {
      evaluateBorrowedLandCompletion();
      return;
    }

    if (currentState === "plantYourFlag" && hasLegitimateSocialPostArrival()) {
      enterBorrowedLand();
    }
  }

  function evaluateBorrowedLandCompletion() {
    if (currentState !== "borrowedLand") {
      return;
    }

    if (!isSocialPostMeaningfullyPresent()) {
      return;
    }

    currentState = "staticSocialPost";
    unbindCompletionListeners();
    notifyStaticSocialPostAuthority();
  }

  function enterBorrowedLand() {
    if (currentState !== "plantYourFlag") {
      return;
    }

    pendingForwardIntent = false;
    currentState = "borrowedLand";
    unbindEntryListeners();
    bindCompletionListeners();
    evaluateBorrowedLandCompletion();
  }

  function recordForwardIntent() {
    if (currentState !== "plantYourFlag") {
      return;
    }

    pendingForwardIntent = true;
    intentScrollY = getScrollY();
  }

  function tryEnterBorrowedLandFromForwardMovement() {
    if (currentState !== "plantYourFlag" || !pendingForwardIntent) {
      return;
    }

    const scrollY = getScrollY();

    if (scrollY > intentScrollY) {
      enterBorrowedLand();
      return;
    }

    if (scrollY < intentScrollY) {
      pendingForwardIntent = false;
    }
  }

  function isInteractiveTarget(target) {
    if (!target || !(target instanceof Element)) {
      return false;
    }

    return (
      target.closest(
        "a[href], button, input, textarea, select, option, summary, label, [contenteditable='true'], [role='button'], [role='link'], [role='menuitem'], [role='tab']",
      ) !== null
    );
  }

  function onWheel(event) {
    if (event.deltaY > 0) {
      recordForwardIntent();
    }
  }

  function onTouchStart(event) {
    if (event.touches.length !== 1) {
      touchStartY = null;
      return;
    }

    touchStartY = event.touches[0].clientY;
  }

  function onTouchMove(event) {
    if (touchStartY === null || event.touches.length !== 1) {
      return;
    }

    if (event.touches[0].clientY < touchStartY) {
      recordForwardIntent();
    }
  }

  function onTouchEnd() {
    touchStartY = null;
  }

  function onKeyDown(event) {
    if (isInteractiveTarget(event.target)) {
      return;
    }

    const { key, shiftKey } = event;

    if (key === "ArrowDown" || key === "PageDown") {
      recordForwardIntent();
      return;
    }

    if (key === " " && !shiftKey) {
      recordForwardIntent();
    }
  }

  function onEntryScroll() {
    tryEnterBorrowedLandFromForwardMovement();
    reconcileStaticSocialPostAuthority();
  }

  function onCompletionEvaluate() {
    reconcileStaticSocialPostAuthority();
  }

  function bindEntryListeners() {
    if (entryListenersBound) {
      return;
    }

    entryListenersBound = true;
    window.addEventListener("wheel", onWheel, { passive: true });
    window.addEventListener("touchstart", onTouchStart, { passive: true });
    window.addEventListener("touchmove", onTouchMove, { passive: true });
    window.addEventListener("touchend", onTouchEnd, { passive: true });
    window.addEventListener("touchcancel", onTouchEnd, { passive: true });
    window.addEventListener("keydown", onKeyDown);
    window.addEventListener("scroll", onEntryScroll, { passive: true });
  }

  function unbindEntryListeners() {
    if (!entryListenersBound) {
      return;
    }

    entryListenersBound = false;
    window.removeEventListener("wheel", onWheel);
    window.removeEventListener("touchstart", onTouchStart);
    window.removeEventListener("touchmove", onTouchMove);
    window.removeEventListener("touchend", onTouchEnd);
    window.removeEventListener("touchcancel", onTouchEnd);
    window.removeEventListener("keydown", onKeyDown);
    window.removeEventListener("scroll", onEntryScroll);
    touchStartY = null;
    pendingForwardIntent = false;
    intentScrollY = 0;
  }

  function bindCompletionListeners() {
    if (completionListenersBound) {
      return;
    }

    completionListenersBound = true;
    window.addEventListener("scroll", onCompletionEvaluate, { passive: true });
    window.addEventListener("resize", onCompletionEvaluate);
  }

  function unbindCompletionListeners() {
    if (!completionListenersBound) {
      return;
    }

    completionListenersBound = false;
    window.removeEventListener("scroll", onCompletionEvaluate);
    window.removeEventListener("resize", onCompletionEvaluate);
  }

  function initOpeningStateChain() {
    openingField = document.querySelector("#scene-01");
    storyRing = document.querySelector("#scene-02 .story-ring");
    socialPost = document.querySelector("#scene-02 .social-post");

    if (!storyRing || !socialPost) {
      return;
    }

    if (currentState === "plantYourFlag") {
      bindEntryListeners();
      reconcileStaticSocialPostAuthority();
      return;
    }

    if (currentState === "borrowedLand") {
      bindCompletionListeners();
      reconcileStaticSocialPostAuthority();
    }

    notifyStaticSocialPostAuthority();
  }

  function reconcileForIdleContinuation() {
    reconcileStaticSocialPostAuthority();
    return currentState === "staticSocialPost";
  }

  function requestMemoryCrossingEntry() {
    if (currentState === "memoryCrossing") {
      return false;
    }

    reconcileStaticSocialPostAuthority();

    if (currentState !== "staticSocialPost") {
      return false;
    }

    currentState = "memoryCrossing";
    return true;
  }

  window.Feature01State = Object.freeze({
    getCurrentState() {
      return currentState;
    },

    getStateVocabulary() {
      return STATE_VOCABULARY;
    },

    requestMemoryCrossingEntry() {
      return requestMemoryCrossingEntry();
    },

    reconcileForIdleContinuation() {
      return reconcileForIdleContinuation();
    },

    subscribeStaticSocialPostAuthority(listener) {
      subscribeStaticSocialPostAuthority(listener);
    },
  });

  document.addEventListener("DOMContentLoaded", initOpeningStateChain);
})();
