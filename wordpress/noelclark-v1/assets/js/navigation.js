/**
 * Quiet navigation — available from first paint (nav-visible also set via body_class).
 * Retained for compatibility if body_class did not run on a non-WP preview shell.
 */
document.addEventListener("DOMContentLoaded", () => {
  document.body.classList.add("nav-visible");
});
