/**
 * Quiet navigation — visible from Story Ring onward.
 */
document.addEventListener("DOMContentLoaded", () => {
  const hero = document.querySelector("#scene-01");

  if (!hero) {
    document.body.classList.add("nav-visible");
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          document.body.classList.remove("nav-visible");
        } else {
          document.body.classList.add("nav-visible");
        }
      });
    },
    { threshold: 0.12 },
  );

  observer.observe(hero);
});
