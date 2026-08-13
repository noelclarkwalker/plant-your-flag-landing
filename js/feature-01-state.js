/**
 * Feature 01 — State Owner
 *
 * Canonical Roadmap state vocabulary and authoritative current-state read access.
 * Tracks the approved opening chain: plantYourFlag → borrowedLand → staticSocialPost.
 */
(function () {
  const STATE_VOCABULARY = Object.freeze([
    "plantYourFlag",
    "borrowedLand",
    "staticSocialPost",
    "memoryCrossing",
    "memoryField",
    "journal",
    "waybackMemory",
    "typewriter",
    "memoryCascade",
    "expandingRoom",
    "manifesto",
    "plantingMyFlag",
    "portal",
    "emergence",
    "wander",
  ]);

  let currentState = "plantYourFlag";

  let openingField = null;
  let storyRing = null;
  let touchStartY = null;
  let pendingForwardIntent = false;
  let intentScrollY = 0;
  let entryListenersBound = false;
  let completionListenersBound = false;

  function getScrollY() {
    return window.scrollY || document.documentElement.scrollTop || 0;
  }

  function isOpeningFullyDeparted() {
    return openingField.getBoundingClientRect().bottom <= 0;
  }

  function isStoryRingIntersectingViewport() {
    const rect = storyRing.getBoundingClientRect();

    return (
      rect.bottom > 0 &&
      rect.top < window.innerHeight &&
      rect.right > 0 &&
      rect.left < window.innerWidth
    );
  }

  function evaluateBorrowedLandCompletion() {
    if (currentState !== "borrowedLand") {
      return;
    }

    if (!isOpeningFullyDeparted() || !isStoryRingIntersectingViewport()) {
      return;
    }

    currentState = "staticSocialPost";
    unbindCompletionListeners();
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
  }

  function onCompletionEvaluate() {
    evaluateBorrowedLandCompletion();
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

    if (!openingField || !storyRing) {
      return;
    }

    if (currentState === "plantYourFlag") {
      bindEntryListeners();
      return;
    }

    if (currentState === "borrowedLand") {
      bindCompletionListeners();
      evaluateBorrowedLandCompletion();
    }
  }

  window.Feature01State = Object.freeze({
    getCurrentState() {
      return currentState;
    },

    getStateVocabulary() {
      return STATE_VOCABULARY;
    },
  });

  document.addEventListener("DOMContentLoaded", initOpeningStateChain);
})();
