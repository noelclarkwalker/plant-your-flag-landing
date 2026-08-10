/**
 * Remembered social post — authentic interaction while the platform exists.
 */
document.addEventListener("DOMContentLoaded", () => {
  const scene = document.querySelector("#scene-02");
  if (!scene) {
    return;
  }

  const likeButton = scene.querySelector(".social-chrome--like");
  const commentButton = scene.querySelector(".social-chrome--comment");
  const saveButton = scene.querySelector(".social-chrome--save");
  const commentField = scene.querySelector("#comment-field");
  const commentInput = scene.querySelector("#comment-entry");
  const commentClose = scene.querySelector(".comment-field__close");
  const commentSend = scene.querySelector(".comment-field__send");
  const likesCount = scene.querySelector(".engagement-count--likes");
  const commentsCount = scene.querySelector(".engagement-count--comments");
  const savesCount = scene.querySelector(".engagement-count--saves");

  if (
    !likeButton ||
    !commentButton ||
    !saveButton ||
    !commentField ||
    !commentInput ||
    !commentClose ||
    !commentSend ||
    !likesCount ||
    !commentsCount ||
    !savesCount
  ) {
    return;
  }

  const VISITOR_KEY = "pyf-remembered-social";
  const METRICS_KEY = "pyf-social-metrics";
  const GUESTBOOK_KEY = "pyf-guestbook-entries";

  function readVisitorState() {
    try {
      return JSON.parse(localStorage.getItem(VISITOR_KEY) || "{}");
    } catch {
      return {};
    }
  }

  function writeVisitorState(state) {
    localStorage.setItem(VISITOR_KEY, JSON.stringify(state));
  }

  function readMetrics() {
    try {
      const metrics = JSON.parse(localStorage.getItem(METRICS_KEY) || "{}");
      return {
        likes: Number(metrics.likes) || 0,
        comments: Number(metrics.comments) || 0,
        saves: Number(metrics.saves) || 0,
      };
    } catch {
      return { likes: 0, comments: 0, saves: 0 };
    }
  }

  function writeMetrics(metrics) {
    localStorage.setItem(METRICS_KEY, JSON.stringify(metrics));
  }

  function readGuestBookEntries() {
    try {
      const entries = JSON.parse(localStorage.getItem(GUESTBOOK_KEY) || "[]");
      return Array.isArray(entries) ? entries : [];
    } catch {
      return [];
    }
  }

  function appendGuestBookEntry(text) {
    const entries = readGuestBookEntries();
    entries.push({
      text,
      rememberedAt: new Date().toISOString(),
      source: "remembered-social-post",
    });
    localStorage.setItem(GUESTBOOK_KEY, JSON.stringify(entries));
  }

  function isChromeActive(element) {
    return element && !element.classList.contains("social-chrome--hidden");
  }

  function formatCount(value) {
    return value.toLocaleString("en-US");
  }

  function syncCommentMetric(metrics) {
    const submitted = readGuestBookEntries().length;
    metrics.comments = submitted;
    return metrics;
  }

  function updateMetricsDisplay(metrics) {
    likesCount.textContent = formatCount(metrics.likes);
    commentsCount.textContent = formatCount(metrics.comments);
    savesCount.textContent = formatCount(metrics.saves);
  }

  function updateInteractionState(visitorState) {
    likeButton.setAttribute("aria-pressed", visitorState.liked ? "true" : "false");
    likeButton.classList.toggle("social-action--active", Boolean(visitorState.liked));
    likeButton.disabled = Boolean(visitorState.liked);

    saveButton.setAttribute("aria-pressed", visitorState.saved ? "true" : "false");
    saveButton.classList.toggle("social-action--active", Boolean(visitorState.saved));
    saveButton.disabled = Boolean(visitorState.saved);
  }

  function setCommentFieldOpen(isOpen) {
    commentField.hidden = !isOpen;
    commentField.setAttribute("aria-hidden", isOpen ? "false" : "true");
    commentButton.setAttribute("aria-expanded", isOpen ? "true" : "false");

    if (isOpen) {
      commentInput.focus();
      return;
    }

    commentInput.value = "";
  }

  function openCommentField() {
    if (!isChromeActive(commentButton)) {
      return;
    }

    setCommentFieldOpen(true);
  }

  function closeCommentField() {
    setCommentFieldOpen(false);
  }

  function submitComment() {
    const text = commentInput.value.trim();
    if (!text) {
      commentInput.focus();
      return;
    }

    appendGuestBookEntry(text);
    metrics.comments += 1;
    writeMetrics(metrics);
    updateMetricsDisplay(metrics);
    closeCommentField();
  }

  const visitorState = {
    liked: false,
    saved: false,
    ...readVisitorState(),
  };

  let metrics = readMetrics();
  metrics = syncCommentMetric(metrics);
  updateMetricsDisplay(metrics);
  updateInteractionState(visitorState);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !commentField.hidden) {
      closeCommentField();
    }
  });

  likeButton.addEventListener("click", () => {
    if (!isChromeActive(likeButton) || visitorState.liked) {
      return;
    }

    visitorState.liked = true;
    metrics.likes += 1;
    writeVisitorState(visitorState);
    writeMetrics(metrics);
    updateMetricsDisplay(metrics);
    updateInteractionState(visitorState);
  });

  commentButton.addEventListener("click", () => {
    if (!isChromeActive(commentButton)) {
      return;
    }

    if (commentField.hidden) {
      openCommentField();
      return;
    }

    closeCommentField();
  });

  commentSend.addEventListener("click", submitComment);

  commentClose.addEventListener("click", closeCommentField);

  commentInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      submitComment();
    }
  });

  saveButton.addEventListener("click", () => {
    if (!isChromeActive(saveButton) || visitorState.saved) {
      return;
    }

    visitorState.saved = true;
    metrics.saves += 1;
    writeVisitorState(visitorState);
    writeMetrics(metrics);
    updateMetricsDisplay(metrics);
    updateInteractionState(visitorState);
  });

  const commentChrome = scene.querySelector(".social-chrome--comment");
  if (commentChrome) {
    const dissolveObserver = new MutationObserver(() => {
      if (commentChrome.classList.contains("social-chrome--hidden")) {
        closeCommentField();
      }
    });

    dissolveObserver.observe(commentChrome, {
      attributes: true,
      attributeFilter: ["class"],
    });
  }
});
