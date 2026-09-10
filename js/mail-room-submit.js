(function () {
  "use strict";

  var TOTAL_STEPS = 6;
  var LETTER_MAX = 50000;
  var questions = window.MAIL_ROOM_OPTIONAL_QUESTIONS || [];

  var PERMISSION_OPTIONS = [
    {
      value: "not_public",
      title: "NOT FOR PUBLIC SHARING",
      subline: "Keep this between you and me.",
      detail:
        "Your letter, identity, quotations, and source material are not authorized for public sharing. It may still influence independent thought or creative work.",
    },
    {
      value: "anonymous",
      title: "SHARE ANONYMOUSLY",
      subline: "You may share from my letter, but don't identify me.",
      detail:
        "You authorize approved public use without identifying you. Submission does not guarantee selection or publication.",
    },
    {
      value: "named_credit",
      title: "SHARE WITH MY NAME",
      subline: "You may share from my letter and credit me using the name I provide.",
      detail:
        "You authorize approved public use with the public credit name you provide below. It is separate from your private email.",
    },
  ];

  var submitDialog = document.getElementById("mail-submit");
  var submitBody = document.getElementById("mail-submit-body");
  var submitReturn = document.querySelector(".mail-submit__return");
  var submitDevPreview = document.querySelector(".mail-submit__dev-preview");
  var writeCta = document.getElementById("mail-room-send-letter");
  var stepLive = document.getElementById("mail-submit-step-live");

  var scrollRestore = 0;
  var lastTrigger = null;

  var state = createInitialState();

  if (!submitDialog || !submitBody || !writeCta) {
    return;
  }

  function createInitialState() {
    return {
      step: 1,
      ageAttestation: false,
      letterBody: "",
      optionalResponses: {},
      permissionTier: "",
      publicCreditName: "",
      privateEmail: "",
      termsAccepted: false,
      sendAttempted: false,
    };
  }

  function isDevHost() {
    var host = window.location.hostname;
    return host === "localhost" || host === "127.0.0.1";
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || "").trim());
  }

  function maskEmail(value) {
    var email = String(value || "").trim();
    var at = email.indexOf("@");

    if (at <= 0) {
      return email;
    }

    return email.charAt(0) + "•••" + email.slice(at);
  }

  function permissionLabel(value) {
    var match = PERMISSION_OPTIONS.find(function (option) {
      return option.value === value;
    });

    return match ? match.title : "";
  }

  function optionalAnsweredCount() {
    return Object.keys(state.optionalResponses).filter(function (key) {
      return state.optionalResponses[key];
    }).length;
  }

  function announceStep(step) {
    if (!stepLive) {
      return;
    }

    stepLive.textContent = "Step " + step + " of " + TOTAL_STEPS;
  }

  function stepError(message) {
    return '<p class="mail-submit__error" role="alert">' + escapeHtml(message) + "</p>";
  }

  function navButtons(showBack, continueLabel, continueDisabled) {
    var back = showBack
      ? '<button type="button" class="mail-submit__back">Back</button>'
      : "";

    return (
      '<div class="mail-submit__nav">' +
      back +
      '<button type="button" class="mail-submit__continue"' +
      (continueDisabled ? ' disabled aria-disabled="true"' : "") +
      ">" +
      escapeHtml(continueLabel) +
      " →</button>" +
      "</div>"
    );
  }

  function renderStep1() {
    return (
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Before you write</h2>' +
      '<label class="mail-submit__check">' +
      '<input type="checkbox" class="mail-submit__check-input" data-field="ageAttestation"' +
      (state.ageAttestation ? " checked" : "") +
      " />" +
      "<span>I am 18 or older.</span>" +
      "</label>" +
      '<p class="mail-submit__helper">Only Mail Room submission requires 18+. You may still read and explore NoelClark.com.</p>' +
      navButtons(false, "Continue", !state.ageAttestation) +
      "</section>"
    );
  }

  function renderStep2() {
    return (
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Write your letter</h2>' +
      '<label class="mail-submit__label" for="mail-submit-letter">Your letter</label>' +
      '<textarea id="mail-submit-letter" class="mail-submit__textarea mail-submit__textarea--letter" data-field="letterBody" rows="14" maxlength="' +
      LETTER_MAX +
      '" placeholder="Begin your letter here...">' +
      escapeHtml(state.letterBody) +
      "</textarea>" +
      navButtons(true, "Continue", false) +
      "</section>"
    );
  }

  function renderStep3() {
    var html =
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">A few optional questions</h2>' +
      "<p class=\"mail-submit__helper\">These questions help me connect with you on a deeper level. Answer as many or as few as you'd like. You don't need to answer all five.</p>";

    questions.forEach(function (question) {
      var groupName = "mail-submit-" + question.id;
      var selected = state.optionalResponses[question.id] || "";

      html +=
        '<fieldset class="mail-submit__question">' +
        '<legend class="mail-submit__question-prompt">' +
        escapeHtml(question.prompt) +
        "</legend>";

      question.responses.forEach(function (response) {
        html +=
          '<label class="mail-submit__choice">' +
          '<input type="radio" name="' +
          groupName +
          '" value="' +
          escapeHtml(response.id) +
          '" data-question-id="' +
          escapeHtml(question.id) +
          '"' +
          (selected === response.id ? " checked" : "") +
          " />" +
          "<span>" +
          escapeHtml(response.label) +
          "</span>" +
          "</label>";
      });

      html += "</fieldset>";
    });

    html += navButtons(true, "Continue", false) + "</section>";
    return html;
  }

  function renderStep4() {
    var html =
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Sharing permission</h2>' +
      '<fieldset class="mail-submit__permission">' +
      '<legend class="visually-hidden">Choose one sharing permission</legend>';

    PERMISSION_OPTIONS.forEach(function (option) {
      html +=
        '<label class="mail-submit__permission-option">' +
        '<input type="radio" name="mail-submit-permission" value="' +
        escapeHtml(option.value) +
        '" data-field="permissionTier"' +
        (state.permissionTier === option.value ? " checked" : "") +
        " />" +
        '<span class="mail-submit__permission-title">' +
        escapeHtml(option.title) +
        "</span>" +
        '<span class="mail-submit__permission-subline">' +
        escapeHtml(option.subline) +
        "</span>" +
        '<span class="mail-submit__permission-detail">' +
        escapeHtml(option.detail) +
        "</span>" +
        "</label>";
    });

    html += "</fieldset>";

    var showCredit = state.permissionTier === "named_credit";

    html +=
      '<div class="mail-submit__credit' +
      (showCredit ? "" : " mail-submit__credit--hidden") +
      '" id="mail-submit-credit-wrap">' +
      '<label class="mail-submit__label" for="mail-submit-public-credit">Public credit name</label>' +
      '<input type="text" id="mail-submit-public-credit" class="mail-submit__input" data-field="publicCreditName" autocomplete="off" value="' +
      escapeHtml(state.publicCreditName) +
      '" />' +
      '<p class="mail-submit__helper">First name, initials, pseudonym, or full name — your choice. Never derived from your private email.</p>' +
      "</div>" +
      navButtons(true, "Continue", false) +
      "</section>";

    return html;
  }

  function renderStep5() {
    return (
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Private contact</h2>' +
      '<p class="mail-submit__helper">This email is private administrative contact for this submission. It is not automatically public, not your public credit name, does not promise a reply, does not create ongoing private correspondence, and does not promise notification if your letter is selected or used.</p>' +
      '<label class="mail-submit__label" for="mail-submit-private-email">Private email address</label>' +
      '<input type="email" id="mail-submit-private-email" class="mail-submit__input" data-field="privateEmail" autocomplete="email" inputmode="email" spellcheck="false" required value="' +
      escapeHtml(state.privateEmail) +
      '" />' +
      navButtons(true, "Continue", false) +
      "</section>"
    );
  }

  function renderLetterPreview(body) {
    var trimmed = String(body || "").trim();

    if (trimmed.length <= 320) {
      return escapeHtml(trimmed).replace(/\n/g, "<br />");
    }

    return (
      escapeHtml(trimmed.slice(0, 320)).replace(/\n/g, "<br />") +
      '… <span class="mail-submit__review-truncated">(preview truncated)</span>'
    );
  }

  function renderStep6() {
    var optionalSummary =
      optionalAnsweredCount() === 0
        ? "Skipped"
        : "Answered " + optionalAnsweredCount() + " of " + questions.length;

    var html =
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Review &amp; send</h2>' +
      '<dl class="mail-submit__review">' +
      "<dt>Letter</dt><dd>" +
      renderLetterPreview(state.letterBody) +
      "</dd>" +
      "<dt>Sharing choice</dt><dd>" +
      escapeHtml(permissionLabel(state.permissionTier)) +
      "</dd>";

    if (state.permissionTier === "named_credit") {
      html +=
        "<dt>Public credit</dt><dd>" + escapeHtml(state.publicCreditName.trim()) + "</dd>";
    }

    html +=
      "<dt>Private email</dt><dd>" +
      escapeHtml(maskEmail(state.privateEmail)) +
      "</dd>" +
      "<dt>18+ affirmation</dt><dd>Confirmed</dd>" +
      "<dt>Optional questions</dt><dd>" +
      escapeHtml(optionalSummary) +
      "</dd>" +
      "</dl>" +
      '<label class="mail-submit__check mail-submit__check--terms">' +
      '<input type="checkbox" class="mail-submit__check-input" data-field="termsAccepted"' +
      (state.termsAccepted ? " checked" : "") +
      " />" +
      "<span>I understand how my letter may be used based on the sharing choice I selected, and I agree to the Mail Room Submission Terms.</span>" +
      "</label>" +
      '<p class="mail-submit__crisis-note">A note: The Mail Room isn\'t monitored continuously and isn\'t an emergency or crisis service. If you or someone else is in immediate danger, please use an emergency or crisis service instead. In the U.S., call or text 988, or call 911 for an immediate emergency.</p>' +
      '<div class="mail-submit__nav mail-submit__nav--send">' +
      '<button type="button" class="mail-submit__back">Back</button>' +
      '<button type="button" class="mail-submit__send">SEND A LETTER →</button>' +
      "</div>";

    if (state.sendAttempted) {
      html +=
        '<p class="mail-submit__status" role="status">Submission is not yet connected.</p>';
    }

    html += "</section>";
    return html;
  }

  function renderCurrentStep() {
    switch (state.step) {
      case 1:
        return renderStep1();
      case 2:
        return renderStep2();
      case 3:
        return renderStep3();
      case 4:
        return renderStep4();
      case 5:
        return renderStep5();
      case 6:
        return renderStep6();
      default:
        return renderStep1();
    }
  }

  function syncFieldsFromDom() {
    var letter = submitBody.querySelector('[data-field="letterBody"]');

    if (letter) {
      state.letterBody = letter.value;
    }

    var age = submitBody.querySelector('[data-field="ageAttestation"]');

    if (age) {
      state.ageAttestation = age.checked;
    }

    var tier = submitBody.querySelector('[data-field="permissionTier"]:checked');

    if (tier) {
      state.permissionTier = tier.value;
    }

    var credit = submitBody.querySelector('[data-field="publicCreditName"]');

    if (credit) {
      state.publicCreditName = credit.value;
    }

    var email = submitBody.querySelector('[data-field="privateEmail"]');

    if (email) {
      state.privateEmail = email.value;
    }

    var terms = submitBody.querySelector('[data-field="termsAccepted"]');

    if (terms) {
      state.termsAccepted = terms.checked;
    }

    submitBody.querySelectorAll("[data-question-id]").forEach(function (input) {
      if (input.checked) {
        state.optionalResponses[input.getAttribute("data-question-id")] = input.value;
      }
    });
  }

  function render() {
    submitBody.innerHTML = renderCurrentStep();
    announceStep(state.step);

    var focusTarget = submitBody.querySelector("#mail-submit-step-heading");

    if (focusTarget) {
      focusTarget.setAttribute("tabindex", "-1");
      focusTarget.focus();
    }
  }

  function validateStep(step) {
    syncFieldsFromDom();

    switch (step) {
      case 1:
        if (!state.ageAttestation) {
          return "Please confirm you are 18 or older to continue.";
        }
        return "";
      case 2:
        if (!String(state.letterBody || "").trim()) {
          return "Please write your letter before continuing.";
        }
        if (state.letterBody.length > LETTER_MAX) {
          return "Your letter exceeds the maximum length.";
        }
        return "";
      case 3:
        return "";
      case 4:
        if (!state.permissionTier) {
          return "Please choose a sharing permission.";
        }
        if (state.permissionTier === "named_credit" && !String(state.publicCreditName || "").trim()) {
          return "Please provide a public credit name.";
        }
        return "";
      case 5:
        if (!isValidEmail(state.privateEmail)) {
          return "Please enter a valid private email address.";
        }
        return "";
      case 6:
        if (!state.termsAccepted) {
          return "Please acknowledge the Mail Room Submission Terms before sending.";
        }
        return "";
      default:
        return "";
    }
  }

  function goNext() {
    var error = validateStep(state.step);

    if (error) {
      submitBody.insertAdjacentHTML("afterbegin", stepError(error));
      return;
    }

    if (state.step < TOTAL_STEPS) {
      state.step += 1;
      render();
    }
  }

  function goBack() {
    syncFieldsFromDom();

    if (state.step > 1) {
      state.step -= 1;
      state.sendAttempted = false;
      render();
    }
  }

  function attemptSend() {
    syncFieldsFromDom();
    var error = validateStep(6);

    if (error) {
      state.sendAttempted = false;
      render();
      submitBody.insertAdjacentHTML("afterbegin", stepError(error));
      return;
    }

    state.sendAttempted = true;
    render();
  }

  function openDevArrivalPreview() {
    if (!isDevHost()) {
      return;
    }

    var preview = document.createElement("dialog");
    preview.className = "mail-submit-dev-preview";
    preview.setAttribute("aria-labelledby", "mail-submit-dev-preview-title");

    preview.innerHTML =
      '<div class="mail-submit-dev-preview__panel">' +
      '<p class="mail-submit-dev-preview__banner" role="status">Development preview only. No letter was sent.</p>' +
      '<h2 class="mail-submit-dev-preview__title" id="mail-submit-dev-preview-title">IT ARRIVED.</h2>' +
      '<p class="mail-submit-dev-preview__copy">Sending it does not mean it will be published, answered, or turned into art. It means it arrived.</p>' +
      '<p class="mail-submit-dev-preview__ref"><span class="mail-submit-dev-preview__ref-label">Reference ID</span> PREVIEW — NOT A SUBMISSION</p>' +
      '<button type="button" class="mail-submit-dev-preview__close">Close preview</button>' +
      "</div>";

    document.body.appendChild(preview);

    preview.addEventListener("click", function (event) {
      if (event.target === preview) {
        preview.close();
      }
    });

    preview.querySelector(".mail-submit-dev-preview__close").addEventListener("click", function () {
      preview.close();
    });

    preview.addEventListener("close", function () {
      preview.remove();
    });

    if (typeof preview.showModal === "function") {
      preview.showModal();
    }
  }

  function resetAndOpen(trigger) {
    state = createInitialState();
    lastTrigger = trigger || document.activeElement;
    scrollRestore = window.scrollY;
    state.sendAttempted = false;

    if (submitDevPreview) {
      submitDevPreview.hidden = !isDevHost();
    }

    render();

    if (typeof submitDialog.showModal === "function") {
      submitDialog.showModal();
    }

    if (submitReturn) {
      submitReturn.focus();
    }
  }

  function closeSubmitDialog() {
    if (!submitDialog.open) {
      return;
    }

    submitDialog.close();
    window.scrollTo(0, scrollRestore);

    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
    }
  }

  function bindEvents() {
    writeCta.addEventListener("click", function () {
      resetAndOpen(writeCta);
    });

    if (submitReturn) {
      submitReturn.addEventListener("click", closeSubmitDialog);
    }

    if (submitDevPreview) {
      submitDevPreview.addEventListener("click", openDevArrivalPreview);
    }

    submitDialog.addEventListener("cancel", function (event) {
      event.preventDefault();
      closeSubmitDialog();
    });

    submitDialog.addEventListener("click", function (event) {
      if (event.target === submitDialog) {
        closeSubmitDialog();
      }
    });

    submitBody.addEventListener("click", function (event) {
      if (event.target.closest(".mail-submit__continue")) {
        event.preventDefault();
        goNext();
        return;
      }

      if (event.target.closest(".mail-submit__back")) {
        event.preventDefault();
        goBack();
        return;
      }

      if (event.target.closest(".mail-submit__send")) {
        event.preventDefault();
        attemptSend();
      }
    });

    submitBody.addEventListener("change", function (event) {
      var target = event.target;

      if (target.matches('[data-field="ageAttestation"]')) {
        state.ageAttestation = target.checked;
        render();
        return;
      }

      if (target.matches('[data-field="permissionTier"]')) {
        syncFieldsFromDom();
        render();
        return;
      }

      if (target.matches("[data-question-id]")) {
        syncFieldsFromDom();
      }
    });

    submitBody.addEventListener("input", function (event) {
      if (event.target.matches('[data-field="letterBody"], [data-field="publicCreditName"], [data-field="privateEmail"]')) {
        syncFieldsFromDom();
      }
    });
  }

  bindEvents();
})();
