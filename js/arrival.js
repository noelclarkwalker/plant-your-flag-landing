document.addEventListener("DOMContentLoaded", () => {
  const storyRing = document.querySelector("#scene-02 .story-ring");

  if (!storyRing) {
    return;
  }

  let storyOpened = false;

  function markStoryOpening() {
    document.body.classList.add("story-opening");
  }

  function openStory() {
    if (storyOpened) {
      return;
    }

    storyOpened = true;
    markStoryOpening();
  }

  storyRing.addEventListener("click", openStory);
});
