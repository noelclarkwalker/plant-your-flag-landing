/**
 * Feature 01 — State Owner
 *
 * Canonical Roadmap state vocabulary and authoritative current-state read access.
 * Transitions, subscriptions, and persistence belong to later implementation phases.
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

  window.Feature01State = Object.freeze({
    getCurrentState() {
      return currentState;
    },

    getStateVocabulary() {
      return STATE_VOCABULARY;
    },
  });
})();
