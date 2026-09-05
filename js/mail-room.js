(function () {
  "use strict";

  var artifacts = window.MAIL_ROOM_ARTIFACTS || [];
  var hero = document.querySelector(".mail-room-hero");
  var fieldGrid = document.getElementById("mail-room-artifacts");
  var readingDialog = document.getElementById("mail-reading");
  var readingReturn = document.querySelector(".mail-reading__return");
  var readingBody = document.querySelector(".mail-reading__body");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var scrollRestore = 0;
  var lastTrigger = null;

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function formatRoute(from, to) {
    return escapeHtml(from) + " <span aria-hidden=\"true\">→</span> " + escapeHtml(to);
  }

  function transcriptionToHtml(text) {
    return text
      .trim()
      .split(/\n\s*\n/)
      .map(function (paragraph) {
        return "<p>" + escapeHtml(paragraph.trim()).replace(/\n/g, "<br />") + "</p>";
      })
      .join("");
  }

  function hasAfterLetterContent(afterLetter) {
    if (!afterLetter || afterLetter.status === "none") {
      return false;
    }

    if (afterLetter.status === "unknown") {
      return Boolean(afterLetter.body);
    }

    return Boolean(afterLetter.body);
  }

  function renderAfterLetter(afterLetter) {
    if (!hasAfterLetterContent(afterLetter)) {
      return "";
    }

    var title = afterLetter.heading || "After the letter";
    var copyClass =
      afterLetter.status === "unknown"
        ? "mail-reading__after-copy mail-reading__after-copy--unknown"
        : "mail-reading__after-copy";

    return (
      '<section class="mail-reading__after">' +
      "<h3 class=\"mail-reading__after-title\">" +
      escapeHtml(title) +
      "</h3>" +
      '<p class="' +
      copyClass +
      '">' +
      escapeHtml(afterLetter.body) +
      "</p>" +
      "</section>"
    );
  }

  function renderPsConnection(psConnection) {
    if (!psConnection || !psConnection.href || !psConnection.label) {
      return "";
    }

    return (
      '<aside class="mail-reading__ps">' +
      '<a class="mail-reading__ps-link" href="' +
      escapeHtml(psConnection.href) +
      '">' +
      escapeHtml(psConnection.label) +
      "</a>" +
      "</aside>"
    );
  }

  function renderTranscriptionBlock(artifact) {
    var html =
      '<div class="mail-reading__transcription">' +
      transcriptionToHtml(artifact.transcription) +
      "</div>";

    var presentation = artifact.presentation;

    if (
      presentation === "historical-paper" ||
      presentation === "digital-message" ||
      presentation === "contemporary-letter"
    ) {
      return (
        '<article class="mail-reading__artifact mail-reading__artifact--' +
        escapeHtml(presentation) +
        '">' +
        html +
        "</article>"
      );
    }

    return html;
  }

  function renderArtifacts() {
    if (!fieldGrid) {
      return;
    }

    fieldGrid.innerHTML = artifacts
      .map(function (artifact) {
        var excerptNote = artifact.excerpt
          ? '<p class="mail-artifact__note">' +
            escapeHtml(artifact.excerptLabel || "Excerpt") +
            "</p>"
          : "";
        var readControl = artifact.transcription
          ? '<button type="button" class="mail-artifact__open" data-open-artifact="' +
            escapeHtml(artifact.id) +
            '">Read →</button>'
          : "";
        var groupAttr = artifact.group
          ? ' data-group="' + escapeHtml(artifact.group) + '"'
          : "";

        return (
          '<article class="mail-artifact mail-artifact--' +
          escapeHtml(artifact.placement) +
          '" id="artifact-' +
          escapeHtml(artifact.id) +
          '" data-artifact-id="' +
          escapeHtml(artifact.id) +
          '"' +
          groupAttr +
          ">" +
          '<p class="mail-artifact__route">' +
          formatRoute(artifact.from, artifact.to) +
          "</p>" +
          '<div class="mail-artifact__meta">' +
          '<p class="mail-artifact__date">' +
          escapeHtml(artifact.date) +
          "</p>" +
          excerptNote +
          "</div>" +
          readControl +
          "</article>"
        );
      })
      .join("");
  }

  function openReading(artifactId, trigger) {
    var artifact = artifacts.find(function (item) {
      return item.id === artifactId;
    });

    if (!artifact || !artifact.transcription || !readingDialog || !readingBody) {
      return;
    }

    lastTrigger = trigger || document.activeElement;
    scrollRestore = window.scrollY;

    var excerptNote = artifact.excerpt ? " · " + (artifact.excerptLabel || "Excerpt") : "";

    readingBody.innerHTML =
      '<header class="mail-reading__identity">' +
      formatRoute(artifact.from, artifact.to) +
      "</header>" +
      '<p class="mail-reading__meta">' +
      escapeHtml(artifact.date) +
      excerptNote +
      "</p>" +
      renderTranscriptionBlock(artifact) +
      renderAfterLetter(artifact.afterLetter) +
      renderPsConnection(artifact.psConnection);

    if (typeof readingDialog.showModal === "function") {
      readingDialog.showModal();
    }

    if (readingReturn) {
      readingReturn.focus();
    }
  }

  function closeReading() {
    if (!readingDialog || !readingDialog.open) {
      return;
    }

    readingDialog.close();
    window.scrollTo(0, scrollRestore);

    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
    }
  }

  function updateHeroProgress() {
    if (!hero || reducedMotion) {
      return;
    }

    var start = hero.offsetTop;
    var end = start + hero.offsetHeight - window.innerHeight * 0.35;
    var scrollSpan = Math.max(end - start, 1);
    var progress = (window.scrollY - start) / scrollSpan;

    progress = Math.min(Math.max(progress, 0), 1);

    hero.style.setProperty("--hero-scale", String(1 + progress * 0.065));
    hero.style.setProperty("--hero-blur", progress * 8 + "px");
    hero.style.setProperty("--hero-kicker-opacity", String(Math.min(progress * 1.35, 1)));
  }

  function bindEvents() {
    if (fieldGrid) {
      fieldGrid.addEventListener("click", function (event) {
        var button = event.target.closest("[data-open-artifact]");

        if (!button) {
          return;
        }

        openReading(button.getAttribute("data-open-artifact"), button);
      });
    }

    if (readingReturn) {
      readingReturn.addEventListener("click", closeReading);
    }

    if (readingDialog) {
      readingDialog.addEventListener("cancel", function (event) {
        event.preventDefault();
        closeReading();
      });

      readingDialog.addEventListener("click", function (event) {
        if (event.target === readingDialog) {
          closeReading();
        }
      });
    }

    if (!reducedMotion) {
      window.addEventListener("scroll", updateHeroProgress, { passive: true });
      window.addEventListener("resize", updateHeroProgress);
      updateHeroProgress();
    }
  }

  renderArtifacts();
  bindEvents();
})();
