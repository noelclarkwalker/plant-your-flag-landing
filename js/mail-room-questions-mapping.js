/**
 * BACKSTAGE ONLY — DO NOT load this file in mail-room.html or any visitor-facing page.
 *
 * Private mapping from neutral Mail Room optional-question response IDs to
 * Noèl's internal framework. Response letter suffix maps as:
 *   a → STACK
 *   b → STRIKE
 *   c → SCAN
 *   d → SPONGE
 *
 * Applies uniformly to mq01–mq05. Backend/editorial systems may import this
 * mapping; the visitor-facing form submits only neutral IDs (e.g. mq03_c).
 */
window.MAIL_ROOM_OPTIONAL_QUESTIONS_BACKSTAGE_MAPPING = {
  mq01: { a: "STACK", b: "STRIKE", c: "SCAN", d: "SPONGE" },
  mq02: { a: "STACK", b: "STRIKE", c: "SCAN", d: "SPONGE" },
  mq03: { a: "STACK", b: "STRIKE", c: "SCAN", d: "SPONGE" },
  mq04: { a: "STACK", b: "STRIKE", c: "SCAN", d: "SPONGE" },
  mq05: { a: "STACK", b: "STRIKE", c: "SCAN", d: "SPONGE" },
};
