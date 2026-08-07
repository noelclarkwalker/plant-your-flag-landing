document.addEventListener("DOMContentLoaded", () => {
  const scene02 = document.querySelector("#scene-02");
  const storyRing = document.querySelector("#scene-02 .story-ring");

  if (!storyRing || !scene02) {
    return;
  }

  const AUTO_OPEN_DELAY_MS = 6000;

  let storyOpened = false;
  let autoOpenTimer = null;

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

  function openStory() {
    if (storyOpened) {
      return;
    }

    storyOpened = true;
    cancelAutoOpen();
    markStoryOpening();
  }

  storyRing.addEventListener("click", openStory);
  initAutoOpenObserver();
});
