/**
 * Period Depth Prototype
 *
 * Ordinary punctuation → brief stillness → impossible interior.
 * Portal has not begun. Emergence has not begun.
 */
document.addEventListener("DOMContentLoaded", () => {
  const periodDot = document.querySelector(".period-dot");

  if (!periodDot) {
    return;
  }

  const STILLNESS_MS = 2800;

  window.setTimeout(() => {
    periodDot.classList.add("is-deepening");
  }, STILLNESS_MS);
});
