(function () {
  "use strict";

  var LETTER_MAX = 50000;
  var RECIPIENT_TEXT_MAX = 200;
  var RECIPIENT_TERMS_VERSION = "mail-room-submission-terms-2026-10-16-prod2";

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

  var RECIPIENT_OPTIONS = [
    {
      value: "as_written",
      label: "Yes, show it as I wrote it",
    },
    {
      value: "alternate",
      label: "Show something different instead",
    },
    {
      value: "private",
      label: "No, keep it private",
    },
  ];

  var submitDialog = document.getElementById("mail-submit");
  var submitBody = document.getElementById("mail-submit-body");
  var submitReturn = document.querySelector(".mail-submit__return");
  var submitDevPreview = document.querySelector(".mail-submit__dev-preview");
  var writeCta = document.getElementById("mail-room-send-letter");
  var stepLive = document.getElementById("mail-submit-step-live");
  var termsPanel = document.getElementById("mail-submit-terms");
  var termsContent = document.getElementById("mail-submit-terms-content");

  var TERMS_UNAVAILABLE_MESSAGE =
    "The Mail Room Submission Terms couldn't be loaded, so your letter can't be sent right now. Please try again later.";

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
      recipientOriginal: "",
      recipientDisplayChoice: "",
      recipientPublicWording: "",
      permissionTier: "",
      publicCreditName: "",
      displayName: "",
      privateEmail: "",
      termsAccepted: false,
      reviewLetterExpanded: false,
      sendAttempted: false,
      sendInFlight: false,
      sendError: "",
      arrivedMrRef: "",
      // Short-lived post-success questionnaire handoff only. Never display,
      // URL-encode, log, or write to localStorage/sessionStorage.
      associationToken: "",
      idempotencyKey: "",
    };
  }

  function getSubmitConfig() {
    return window.noelclarkV1MailRoomSubmit || null;
  }

  function getRestNonce() {
    var submitConfig = getSubmitConfig();

    if (!submitConfig || typeof submitConfig.restNonce !== "string") {
      return "";
    }

    return submitConfig.restNonce;
  }

  function getQuestionnaireConfig() {
    return window.noelclarkV1MailRoomQuestionnaire || null;
  }

  function clearAssociationToken() {
    state.associationToken = "";
  }

  function createIdempotencyKey() {
    var bytes;
    var i;
    var out = "";

    if (window.crypto && typeof window.crypto.getRandomValues === "function") {
      bytes = new Uint8Array(24);
      window.crypto.getRandomValues(bytes);

      for (i = 0; i < bytes.length; i += 1) {
        out += (bytes[i] % 36).toString(36);
      }

      return ("mr" + out).slice(0, 48);
    }

    return ("mr" + String(Date.now()) + Math.random().toString(36).slice(2)).slice(0, 48);
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

  function recipientStepEnabled() {
    var config = getSubmitConfig();

    return !!(config && config.legalDocumentVersion === RECIPIENT_TERMS_VERSION);
  }

  function totalSteps() {
    return recipientStepEnabled() ? 6 : 5;
  }

  function recipientStepNumber() {
    return recipientStepEnabled() ? 3 : 0;
  }

  function sharingStep() {
    return recipientStepEnabled() ? 4 : 3;
  }

  function contactStep() {
    return recipientStepEnabled() ? 5 : 4;
  }

  function reviewStep() {
    return recipientStepEnabled() ? 6 : 5;
  }

  function allowedRecipientChoice(value) {
    var i;

    for (i = 0; i < RECIPIENT_OPTIONS.length; i += 1) {
      if (RECIPIENT_OPTIONS[i].value === value) {
        return value;
      }
    }

    return "";
  }

  function recipientChoiceLabel(value) {
    var match = RECIPIENT_OPTIONS.find(function (option) {
      return option.value === value;
    });

    return match ? match.label : "";
  }

  function recipientLineError(value, emptyMessage) {
    var text = String(value || "");

    if (!text.trim()) {
      return emptyMessage;
    }

    if (/[\r\n]/.test(text) || text.indexOf("\0") !== -1) {
      return "Keep this on one line.";
    }

    if (text.length > RECIPIENT_TEXT_MAX) {
      return "Keep this to 200 characters.";
    }

    return "";
  }

  function remapDraftStep(step, recipientDraft) {
    var enabled = recipientStepEnabled();
    var numeric = parseInt(step, 10);

    if (!numeric || numeric < 1) {
      return 1;
    }

    // A five-step draft has no recipient answer. Sharing, Contact, and Review
    // sit at or after the new step, so restore lands on Recipient.
    if (enabled && !recipientDraft && numeric >= 3) {
      return recipientStepNumber();
    }

    if (!enabled && recipientDraft && numeric > 3) {
      return numeric - 1;
    }

    return numeric;
  }

  function announceStep(step) {
    if (!stepLive) {
      return;
    }

    stepLive.textContent = "Step " + step + " of " + totalSteps();
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

  function renderRecipientStep() {
    var html =
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">WHO\'S YOUR LETTER TO?</h2>' +
      '<p class="mail-submit__helper">A letter can be addressed to anyone or anything. It doesn\'t have to be a real name.</p>' +
      '<p class="mail-submit__helper">For example: Simone · My Old Friend · The Woman on the Train · My Seventeen-Year-Old Self · Someone I Miss · The City I Left</p>' +
      '<label class="visually-hidden" for="mail-submit-recipient">WHO\'S YOUR LETTER TO?</label>' +
      '<input type="text" id="mail-submit-recipient" class="mail-submit__input" data-field="recipientOriginal" autocomplete="off" maxlength="' +
      RECIPIENT_TEXT_MAX +
      '" value="' +
      escapeHtml(state.recipientOriginal) +
      '" />' +
      '<h3 class="mail-submit__label" id="mail-submit-recipient-display-heading">CAN WE SHOW THIS IF YOUR LETTER IS SHARED?</h3>' +
      '<fieldset class="mail-submit__permission">' +
      '<legend class="visually-hidden">Choose whether the recipient can be shown</legend>';

    RECIPIENT_OPTIONS.forEach(function (option) {
      html +=
        '<label class="mail-submit__check">' +
        '<input type="radio" class="mail-submit__check-input" name="mail-submit-recipient-display" value="' +
        escapeHtml(option.value) +
        '" data-field="recipientDisplayChoice"' +
        (state.recipientDisplayChoice === option.value ? " checked" : "") +
        " />" +
        '<span class="mail-submit__choice-text">' +
        escapeHtml(option.label) +
        "</span>" +
        "</label>";
    });

    html +=
      "</fieldset>" +
      '<p class="mail-submit__helper">If you choose something different, we\'ll ask what you\'d like readers to see.</p>' +
      '<p class="mail-submit__helper">Your original recipient description stays with your private submission. This choice only controls how the recipient may appear publicly.</p>';

    var showAlternate = state.recipientDisplayChoice === "alternate";

    html +=
      '<div class="mail-submit__credit' +
      (showAlternate ? "" : " mail-submit__credit--hidden") +
      '" id="mail-submit-recipient-wording-wrap">' +
      '<label class="mail-submit__label mail-submit__choice-text" for="mail-submit-recipient-wording">What should readers see?</label>' +
      '<input type="text" id="mail-submit-recipient-wording" class="mail-submit__input" data-field="recipientPublicWording" autocomplete="off" maxlength="' +
      RECIPIENT_TEXT_MAX +
      '" value="' +
      escapeHtml(state.recipientPublicWording) +
      '" />' +
      '<p class="mail-submit__helper">Readers see this instead of what you wrote above.</p>' +
      "</div>" +
      navButtons(true, "Continue", false) +
      "</section>";

    return html;
  }

  function renderStep3() {
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

  function renderStep4() {
    return (
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Private contact</h2>' +
      '<p class="mail-submit__helper">Your name and email are just for my private records and are never shown publicly. If you choose to be credited, only the public name you provide may appear. Submitting a letter doesn\'t sign you up for emails or mean you\'ll receive a reply.</p>' +
      '<label class="mail-submit__label" for="mail-submit-display-name">Name</label>' +
      '<input type="text" id="mail-submit-display-name" class="mail-submit__input" data-field="displayName" autocomplete="name" spellcheck="false" required value="' +
      escapeHtml(state.displayName) +
      '" />' +
      '<label class="mail-submit__label" for="mail-submit-private-email">Email</label>' +
      '<input type="email" id="mail-submit-private-email" class="mail-submit__input" data-field="privateEmail" autocomplete="email" inputmode="email" spellcheck="false" required value="' +
      escapeHtml(state.privateEmail) +
      '" />' +
      navButtons(true, "Continue", false) +
      "</section>"
    );
  }

  function letterPreviewHtml(body) {
    return escapeHtml(String(body || "")).replace(/\n/g, "<br />");
  }

  function reviewToggleButton(expanded) {
    return (
      '<button type="button" class="mail-submit__review-toggle" aria-expanded="' +
      (expanded ? "true" : "false") +
      '">' +
      (expanded ? "Show less" : "Read full letter") +
      "</button>"
    );
  }

  function renderLetterPreview(body) {
    var trimmed = String(body || "").trim();

    if (trimmed.length <= 320) {
      return letterPreviewHtml(trimmed);
    }

    if (state.reviewLetterExpanded) {
      return letterPreviewHtml(trimmed) + " " + reviewToggleButton(true);
    }

    return (
      letterPreviewHtml(trimmed.slice(0, 320)) +
      "… " +
      reviewToggleButton(false)
    );
  }

  function renderStep5() {
    var html =
      '<section class="mail-submit__step" aria-labelledby="mail-submit-step-heading">' +
      '<h2 class="mail-submit__step-title" id="mail-submit-step-heading">Review &amp; send</h2>' +
      '<dl class="mail-submit__review">' +
      '<dt>Letter</dt><dd class="mail-submit__review-letter">' +
      renderLetterPreview(state.letterBody) +
      "</dd>";

    if (recipientStepEnabled()) {
      html +=
        "<dt>Recipient</dt><dd>" +
        escapeHtml(String(state.recipientOriginal || "").trim()) +
        "</dd>" +
        "<dt>If shared</dt><dd>" +
        escapeHtml(recipientChoiceLabel(state.recipientDisplayChoice)) +
        "</dd>";

      if (state.recipientDisplayChoice === "alternate") {
        html +=
          "<dt>Readers see</dt><dd>" +
          escapeHtml(String(state.recipientPublicWording || "").trim()) +
          "</dd>";
      }
    }

    html +=
      "<dt>Sharing choice</dt><dd>" +
      escapeHtml(permissionLabel(state.permissionTier)) +
      "</dd>";

    if (state.permissionTier === "named_credit") {
      html +=
        "<dt>Public credit</dt><dd>" + escapeHtml(state.publicCreditName.trim()) + "</dd>";
    }

    html +=
      "<dt>Name</dt><dd>" +
      escapeHtml(String(state.displayName || "").trim()) +
      "</dd>" +
      "<dt>Email</dt><dd>" +
      escapeHtml(maskEmail(state.privateEmail)) +
      "</dd>" +
      "<dt>18+ affirmation</dt><dd>Confirmed</dd>" +
      "</dl>" +
      '<label class="mail-submit__check mail-submit__check--terms">' +
      '<input type="checkbox" class="mail-submit__check-input" data-field="termsAccepted"' +
      (state.termsAccepted ? " checked" : "") +
      " />" +
      "<span>I understand how my letter may be used based on the sharing choice I selected, and I agree to the " +
      '<a class="mail-submit__terms-open" href="#mail-submit-terms" aria-controls="mail-submit-terms">Mail Room Submission Terms</a>.</span>' +
      "</label>" +
      (termsAvailable() ? "" : stepError(TERMS_UNAVAILABLE_MESSAGE)) +
      '<p class="mail-submit__crisis-note">A note: The Mail Room isn\'t monitored continuously and isn\'t an emergency or crisis service. If you or someone else is in immediate danger, please use an emergency or crisis service. In the U.S., call or text 988, or call 911 for an immediate emergency.</p>' +
      '<div class="mail-submit__nav mail-submit__nav--send">' +
      '<button type="button" class="mail-submit__back"' +
      (state.sendInFlight ? " disabled" : "") +
      ">Back</button>" +
      '<button type="button" class="mail-submit__send"' +
      (state.sendInFlight || !termsAvailable() ? " disabled" : "") +
      ">SEND A LETTER →</button>" +
      "</div>";

    if (state.sendInFlight) {
      html +=
        '<p class="mail-submit__status" role="status">Sending your letter…</p>';
    } else if (state.sendError) {
      html +=
        '<p class="mail-submit__error" role="alert">' +
        escapeHtml(state.sendError) +
        "</p>";
    } else if (state.sendAttempted && !getSubmitConfig()) {
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
        return recipientStepEnabled() ? renderRecipientStep() : renderStep3();
      case 4:
        return recipientStepEnabled() ? renderStep3() : renderStep4();
      case 5:
        return recipientStepEnabled() ? renderStep4() : renderStep5();
      case 6:
        return recipientStepEnabled() ? renderStep5() : renderStep1();
      default:
        return renderStep1();
    }
  }

  function syncFieldsFromDom() {
    var letter = submitBody.querySelector('[data-field="letterBody"]');

    if (letter) {
      state.letterBody = letter.value;
    }

    var recipient = submitBody.querySelector('[data-field="recipientOriginal"]');

    if (recipient) {
      state.recipientOriginal = recipient.value;
    }

    var recipientChoice = submitBody.querySelector('[data-field="recipientDisplayChoice"]:checked');

    if (recipientChoice) {
      state.recipientDisplayChoice = recipientChoice.value;
    }

    var recipientWording = submitBody.querySelector('[data-field="recipientPublicWording"]');

    if (recipientWording) {
      state.recipientPublicWording = recipientWording.value;
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

    var displayName = submitBody.querySelector('[data-field="displayName"]');

    if (displayName) {
      state.displayName = displayName.value;
    }

    var email = submitBody.querySelector('[data-field="privateEmail"]');

    if (email) {
      state.privateEmail = email.value;
    }

    var terms = submitBody.querySelector('[data-field="termsAccepted"]');

    if (terms) {
      state.termsAccepted = terms.checked;
    }
  }

  function termsAvailable() {
    return !!(
      termsPanel &&
      termsContent &&
      termsPanel.getAttribute("data-terms-status") === "ok"
    );
  }

  function isTermsOpen() {
    return !!(termsPanel && !termsPanel.hidden);
  }

  function showTerms() {
    if (!termsPanel || !termsContent) {
      return;
    }

    syncFieldsFromDom();

    if (!termsAvailable()) {
      termsContent.innerHTML = stepError(TERMS_UNAVAILABLE_MESSAGE);
    }

    submitBody.hidden = true;

    if (submitReturn) {
      submitReturn.hidden = true;
    }

    termsPanel.hidden = false;

    if (stepLive) {
      stepLive.textContent = "Mail Room Submission Terms";
    }

    termsContent.focus({ preventScroll: true });
    termsPanel.scrollTop = 0;
  }

  function openTerms() {
    var marker;

    showTerms();
    saveDraft();
    marker = currentMarker();

    if (!marker || marker.view !== "terms" || marker.step !== state.step) {
      pushMarker(state.step, "terms");
    }
  }

  function requestCloseTerms() {
    var marker = currentMarker();

    if (marker && marker.view === "terms") {
      history.back();
      return;
    }

    closeTerms(true);
    saveDraft();
  }

  function closeTerms(restoreFocus) {
    if (!isTermsOpen()) {
      return;
    }

    termsPanel.hidden = true;
    submitBody.hidden = false;

    if (submitReturn) {
      submitReturn.hidden = false;
    }

    if (!restoreFocus) {
      return;
    }

    announceStep(state.step);

    var link = submitBody.querySelector(".mail-submit__terms-open");

    if (link) {
      link.focus();
    }
  }

  function render(options) {
    closeTerms(false);
    submitBody.innerHTML = renderCurrentStep();
    announceStep(state.step);

    if (options && options.preserveFocus) {
      return;
    }

    var focusTarget = submitBody.querySelector("#mail-submit-step-heading");

    if (focusTarget) {
      focusTarget.setAttribute("tabindex", "-1");
      /* Keep dialog focus entry; preventScroll avoids jump. Focus ring handled via :focus-visible CSS. */
      focusTarget.focus({ preventScroll: true });
    }
  }

  function stepErrorForState(step) {
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
      default:
        break;
    }

    if (step === recipientStepNumber()) {
      var recipientError = recipientLineError(
        state.recipientOriginal,
        "Please say who your letter is to before continuing."
      );

      if (recipientError) {
        return recipientError;
      }

      if (!allowedRecipientChoice(state.recipientDisplayChoice)) {
        return "Please choose whether the recipient can be shown if your letter is shared.";
      }

      if (state.recipientDisplayChoice === "alternate") {
        return recipientLineError(
          state.recipientPublicWording,
          "Please say what readers should see."
        );
      }

      return "";
    }

    if (step === sharingStep()) {
      if (!state.permissionTier) {
        return "Please choose a sharing permission.";
      }
      if (state.permissionTier === "named_credit" && !String(state.publicCreditName || "").trim()) {
        return "Please provide a public credit name.";
      }
      return "";
    }

    if (step === contactStep()) {
      if (!String(state.displayName || "").trim()) {
        return "Please enter your name.";
      }
      if (!isValidEmail(state.privateEmail)) {
        return "Please enter a valid email address.";
      }
      return "";
    }

    if (step === reviewStep()) {
      if (!termsAvailable()) {
        return TERMS_UNAVAILABLE_MESSAGE;
      }
      if (!state.termsAccepted) {
        return "Please acknowledge the Mail Room Submission Terms before sending.";
      }
      return "";
    }

    return "";
  }

  function validateStep(step) {
    syncFieldsFromDom();
    return stepErrorForState(step);
  }

  function firstInvalidStep() {
    var step;
    var error;
    var limit = totalSteps();

    for (step = 1; step <= limit; step += 1) {
      error = stepErrorForState(step);
      if (error) {
        return {
          step: step,
          error: error,
        };
      }
    }

    return null;
  }

  function clampToValidStep(step) {
    var invalid = firstInvalidStep();

    if (invalid && step > invalid.step) {
      return invalid.step;
    }

    return step;
  }

  function goNext() {
    syncFieldsFromDom();
    var invalid = firstInvalidStep();

    if (invalid && invalid.step < state.step) {
      state.step = invalid.step;
      saveDraft();
      render();
      submitBody.insertAdjacentHTML("afterbegin", stepError(invalid.error));
      return;
    }

    if (invalid && invalid.step === state.step) {
      saveDraft();
      submitBody.insertAdjacentHTML("afterbegin", stepError(invalid.error));
      return;
    }

    if (state.step < totalSteps()) {
      state.step += 1;
      saveDraft();
      render();
      pushMarker(state.step, "form");
    }
  }

  function goBack() {
    syncFieldsFromDom();
    saveDraft();

    if (isTermsOpen()) {
      requestCloseTerms();
      return;
    }

    if (currentMarker()) {
      history.back();
      return;
    }

    if (state.step > 1) {
      state.step -= 1;
      state.sendInFlight = false;
      render();
      saveDraft();
    }
  }

  function mapSendErrorCode(code) {
    switch (code) {
      case "nonce_error":
        return "Security verification failed. Refresh the page and try again.";
      case "validation_error":
      case "invalid_json":
        return "Your letter could not be accepted. Please check your entries and try again.";
      case "legal_document_unavailable":
        return "Submission Terms are temporarily unavailable. Please try again later.";
      case "storage_error":
      case "service_unavailable":
        return "Something went wrong while saving your letter. Please try again.";
      default:
        return "Your letter could not be sent. Please try again.";
    }
  }

  function mapQuestionnaireErrorCode(code) {
    switch (code) {
      case "nonce_error":
        return "Security verification failed. Refresh the page and try again.";
      case "validation_error":
      case "invalid_json":
        return "Those answers could not be accepted. Please check your selections and try again.";
      case "association_invalid":
      case "association_expired":
      case "association_revoked":
      case "association_consumed":
        return "This questionnaire offer is no longer available. Your letter was already received.";
      case "questionnaire_unavailable":
        return "The questionnaire is temporarily unavailable. Your letter was already received.";
      case "storage_error":
      case "service_unavailable":
        return "Something went wrong while saving your answers. Your letter was already received.";
      default:
        return "Your answers could not be saved. Your letter was already received.";
    }
  }

  function showArrivalSuccess(mrRef) {
    var preview = document.createElement("dialog");
    preview.className = "mail-submit-dev-preview";
    preview.setAttribute("aria-labelledby", "mail-submit-step-heading-arrived");

    // Local handoff copy only — never written into DOM attributes/text.
    var handoffToken = typeof state.associationToken === "string" ? state.associationToken : "";
    var phase = handoffToken ? "invite" : "arrived";
    var answers = {};
    var qRef = "";
    var qError = "";
    var qInFlight = false;

    function wipeHandoff() {
      handoffToken = "";
      clearAssociationToken();
    }

    function optionalQuestions() {
      return Array.isArray(window.MAIL_ROOM_OPTIONAL_QUESTIONS)
        ? window.MAIL_ROOM_OPTIONAL_QUESTIONS
        : [];
    }

    function syncAnswersFromDom() {
      var questions = optionalQuestions();
      var next = {};
      var i;
      var question;
      var selected;

      for (i = 0; i < questions.length; i += 1) {
        question = questions[i];

        if (!question || !question.id) {
          continue;
        }

        selected = preview.querySelector(
          'input[name="mail-q-' + question.id + '"]:checked'
        );

        if (selected && selected.value) {
          next[question.id] = selected.value;
        }
      }

      answers = next;
    }

    function clearLocalAnswers() {
      answers = {};
      qError = "";
      renderArrivalPanel();
    }

    function renderQuestionsHtml() {
      var questions = optionalQuestions();
      var html = "";
      var i;
      var j;
      var question;
      var response;
      var selected;

      if (!questions.length) {
        return (
          '<p class="mail-submit-dev-preview__copy" role="alert">Questionnaire questions are unavailable right now. Your letter was already received.</p>' +
          '<button type="button" class="mail-submit-dev-preview__close" data-arrival-action="close">Close</button>'
        );
      }

      html +=
        '<div class="mail-submit-dev-preview__questions">';

      for (i = 0; i < questions.length; i += 1) {
        question = questions[i];

        if (!question || !question.id) {
          continue;
        }

        selected = answers[question.id] || "";

        html +=
          '<fieldset class="mail-submit__question">' +
          '<legend class="mail-submit__question-prompt">' +
          escapeHtml(question.prompt || "") +
          "</legend>";

        if (Array.isArray(question.responses)) {
          for (j = 0; j < question.responses.length; j += 1) {
            response = question.responses[j];

            if (!response || !response.id) {
              continue;
            }

            html +=
              '<label class="mail-submit__choice">' +
              '<input type="radio" name="mail-q-' +
              escapeHtml(question.id) +
              '" value="' +
              escapeHtml(response.id) +
              '"' +
              (selected === response.id ? " checked" : "") +
              " />" +
              "<span>" +
              escapeHtml(response.label || "") +
              "</span>" +
              "</label>";
          }
        }

        html += "</fieldset>";
      }

      html +=
        "</div>" +
        '<div class="mail-submit-dev-preview__actions">' +
        '<button type="button" class="mail-submit-dev-preview__action" data-arrival-action="clear-answers"' +
        (qInFlight ? " disabled" : "") +
        ">Clear answers</button>" +
        '<button type="button" class="mail-submit-dev-preview__action mail-submit-dev-preview__action--primary" data-arrival-action="submit-questionnaire"' +
        (qInFlight ? " disabled" : "") +
        ">Submit answers →</button>" +
        '<button type="button" class="mail-submit-dev-preview__close" data-arrival-action="no-thanks"' +
        (qInFlight ? " disabled" : "") +
        ">No thanks</button>" +
        "</div>";

      if (qInFlight) {
        html +=
          '<p class="mail-submit__status" role="status">Saving your answers…</p>';
      } else if (qError) {
        html +=
          '<p class="mail-submit__error" role="alert">' +
          escapeHtml(qError) +
          "</p>";
      }

      return html;
    }

    function renderArrivalPanel() {
      var html;
      var headingId = "mail-submit-step-heading-arrived";

      if (phase === "questions") {
        headingId = "mail-submit-step-heading-questions";
        preview.classList.add("mail-submit-dev-preview--questionnaire");
        preview.setAttribute("aria-labelledby", headingId);
        html =
          '<div class="mail-submit-dev-preview__panel mail-submit-dev-preview__panel--scroll">' +
          '<h2 class="mail-submit-dev-preview__title" id="' +
          headingId +
          '">A FEW QUESTIONS.</h2>' +
          '<p class="mail-submit-dev-preview__copy">These questions are optional and private. You can answer any, all, or none.</p>' +
          renderQuestionsHtml() +
          "</div>";
      } else if (phase === "complete") {
        headingId = "mail-submit-step-heading-questionnaire-thanks";
        preview.classList.remove("mail-submit-dev-preview--questionnaire");
        preview.setAttribute("aria-labelledby", headingId);
        html =
          '<div class="mail-submit-dev-preview__panel">' +
          '<h2 class="mail-submit-dev-preview__title" id="' +
          headingId +
          '">THANK YOU.</h2>' +
          '<p class="mail-submit-dev-preview__copy">Your optional answers were received.</p>' +
          (qRef
            ? '<p class="mail-submit-dev-preview__ref"><span class="mail-submit-dev-preview__ref-label">Questionnaire reference</span> ' +
              escapeHtml(qRef) +
              "</p>"
            : "") +
          '<button type="button" class="mail-submit-dev-preview__close" data-arrival-action="close">Close</button>' +
          "</div>";
      } else {
        preview.classList.remove("mail-submit-dev-preview--questionnaire");
        preview.setAttribute("aria-labelledby", headingId);
        html =
          '<div class="mail-submit-dev-preview__panel">' +
          '<h2 class="mail-submit-dev-preview__title" id="' +
          headingId +
          '">IT ARRIVED.</h2>' +
          '<p class="mail-submit-dev-preview__copy">Sending it does not mean it will be published, answered, or turned into art. It means it arrived.</p>' +
          '<p class="mail-submit-dev-preview__ref"><span class="mail-submit-dev-preview__ref-label">Reference ID</span> ' +
          escapeHtml(mrRef || "") +
          "</p>" +
          '<p class="mail-submit-dev-preview__copy">Keep this reference somewhere safe. If you ever want to change how your name appears, go anonymous, correct something, or request that your letter be taken off the public site, email hi@noelclark.com from the address you used here and include your reference. We may need to verify your request before making certain changes.</p>';

        if (phase === "invite") {
          html +=
            '<p class="mail-submit-dev-preview__invite">Want to go one step further?</p>' +
            '<div class="mail-submit-dev-preview__actions">' +
            '<button type="button" class="mail-submit-dev-preview__action mail-submit-dev-preview__action--primary" data-arrival-action="yes">Yes</button>' +
            '<button type="button" class="mail-submit-dev-preview__close" data-arrival-action="no-thanks">No thanks</button>' +
            "</div>";
        } else {
          html +=
            '<button type="button" class="mail-submit-dev-preview__close" data-arrival-action="close">Close</button>';
        }

        html += "</div>";
      }

      preview.innerHTML = html;
    }

    function finishAndClose() {
      wipeHandoff();
      preview.close();
    }

    function attemptQuestionnaireSubmit() {
      var config = getQuestionnaireConfig();

      syncAnswersFromDom();

      if (!handoffToken) {
        qError = mapQuestionnaireErrorCode("association_invalid");
        renderArrivalPanel();
        return;
      }

      if (!config || !config.submitUrl || !config.nonce) {
        qError = mapQuestionnaireErrorCode("service_unavailable");
        renderArrivalPanel();
        return;
      }

      if (qInFlight) {
        return;
      }

      qInFlight = true;
      qError = "";
      renderArrivalPanel();

      var headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-NoelClark-Mail-Room-Questionnaire-Nonce": config.nonce,
      };
      var restNonce = getRestNonce();

      if (restNonce) {
        headers["X-WP-Nonce"] = restNonce;
      }

      fetch(config.submitUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: headers,
        body: JSON.stringify({
          nonce: config.nonce,
          association_token: handoffToken,
          questionnaire_ref: config.questionnaireRef || "",
          version_label: config.versionLabel || "",
          answers: answers,
        }),
      })
        .then(function (response) {
          return response.text().then(function (text) {
            var data = {};

            if (text) {
              try {
                data = JSON.parse(text);
              } catch (parseError) {
                data = {};
              }
            }

            return { okHttp: response.ok, data: data };
          });
        })
        .then(function (result) {
          qInFlight = false;

          if (!result.data || result.data.ok !== true || !result.data.q_ref) {
            qError = mapQuestionnaireErrorCode(
              result.data && result.data.code ? result.data.code : "storage_error"
            );
            renderArrivalPanel();
            return;
          }

          qRef = String(result.data.q_ref);
          wipeHandoff();
          phase = "complete";
          renderArrivalPanel();
        })
        .catch(function () {
          qInFlight = false;
          qError =
            "Network error. Your answers may not have been saved. Your letter was already received.";
          renderArrivalPanel();
        });
    }

    renderArrivalPanel();
    document.body.appendChild(preview);

    preview.addEventListener("click", function (event) {
      if (event.target === preview && !qInFlight) {
        finishAndClose();
      }
    });

    preview.addEventListener("click", function (event) {
      var actionButton = event.target.closest("[data-arrival-action]");
      var action;

      if (!actionButton || !preview.contains(actionButton)) {
        return;
      }

      action = actionButton.getAttribute("data-arrival-action");
      event.preventDefault();

      if (action === "yes") {
        if (!handoffToken) {
          phase = "arrived";
          renderArrivalPanel();
          return;
        }

        phase = "questions";
        qError = "";
        renderArrivalPanel();
        return;
      }

      if (action === "no-thanks" || action === "close") {
        finishAndClose();
        return;
      }

      if (action === "clear-answers") {
        clearLocalAnswers();
        return;
      }

      if (action === "submit-questionnaire") {
        attemptQuestionnaireSubmit();
      }
    });

    preview.addEventListener("change", function (event) {
      if (event.target && event.target.matches('input[type="radio"][name^="mail-q-"]')) {
        syncAnswersFromDom();
      }
    });

    preview.addEventListener("close", function () {
      arrivalOpen = false;
      wipeHandoff();
      preview.remove();
      closeSubmitDialog();
    });

    arrivalOpen = true;

    if (typeof preview.showModal === "function") {
      preview.showModal();
    }
  }

  function attemptSend() {
    syncFieldsFromDom();
    var invalid = firstInvalidStep();
    var config = getSubmitConfig();

    if (invalid) {
      state.step = invalid.step;
      state.sendAttempted = false;
      state.sendInFlight = false;
      state.sendError = "";
      saveDraft();
      render();
      submitBody.insertAdjacentHTML("afterbegin", stepError(invalid.error));
      return;
    }

    if (!config || !config.submitUrl || !config.nonce) {
      state.sendAttempted = true;
      state.sendInFlight = false;
      state.sendError = "";
      saveDraft();
      render();
      return;
    }

    if (state.sendInFlight) {
      return;
    }

    if (!state.idempotencyKey) {
      state.idempotencyKey = createIdempotencyKey();
    }

    state.sendAttempted = true;
    state.sendInFlight = true;
    state.sendError = "";
    clearAssociationToken();
    saveDraft();
    render();

    var payload = {
      nonce: config.nonce,
      idempotency_key: state.idempotencyKey,
      letter_body: state.letterBody,
      sharing_choice: state.permissionTier,
      public_credit:
        state.permissionTier === "named_credit" ? String(state.publicCreditName || "") : "",
      display_name: String(state.displayName || "").trim(),
      email: String(state.privateEmail || "").trim(),
      legal_document_version: config.legalDocumentVersion || "",
      age_attested: !!state.ageAttestation,
      terms_accepted: !!state.termsAccepted,
    };

    if (recipientStepEnabled()) {
      payload.recipient_original = String(state.recipientOriginal || "");
      payload.recipient_display_choice = state.recipientDisplayChoice;
      payload.recipient_public_wording =
        state.recipientDisplayChoice === "alternate"
          ? String(state.recipientPublicWording || "")
          : "";
    }

    var headers = {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-NoelClark-Mail-Room-Nonce": config.nonce,
    };
    var restNonce = getRestNonce();

    if (restNonce) {
      headers["X-WP-Nonce"] = restNonce;
    }

    fetch(config.submitUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: headers,
      body: JSON.stringify(payload),
    })
      .then(function (response) {
        return response.text().then(function (text) {
          var data = {};

          if (text) {
            try {
              data = JSON.parse(text);
            } catch (parseError) {
              data = {};
            }
          }

          return { okHttp: response.ok, data: data };
        });
      })
      .then(function (result) {
        state.sendInFlight = false;

        if (!result.data || result.data.ok !== true || !result.data.mr_ref) {
          clearAssociationToken();
          state.sendError = mapSendErrorCode(
            result.data && result.data.code ? result.data.code : "storage_error"
          );
          saveDraft();
          render();
          return;
        }

        state.sendError = "";
        state.arrivedMrRef = String(result.data.mr_ref);
        state.associationToken =
          typeof result.data.association_token === "string" &&
          result.data.association_token
            ? result.data.association_token
            : "";
        state.idempotencyKey = "";
        render();
        showArrivalSuccess(state.arrivedMrRef);
        submissionSealed = true;
        markSubmissionSeal();
        clearDraft();
        state = createInitialState();
        discardFormHistory();
      })
      .catch(function () {
        state.sendInFlight = false;
        clearAssociationToken();
        state.sendError =
          "Network error. Your letter may not have been sent. Please try again.";
        saveDraft();
        render();
      });
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
      '<p class="mail-submit-dev-preview__copy">Keep this reference somewhere safe. If you ever want to change how your name appears, go anonymous, correct something, or request that your letter be taken off the public site, email hi@noelclark.com from the address you used here and include your reference. We may need to verify your request before making certain changes.</p>' +
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

  var DRAFT_KEY = "noelclark-v1-mail-room-draft";
  var SEAL_KEY = "noelclark-v1-mail-room-sealed";
  var restoredView = "form";
  var unwinding = false;
  var reopenAfterUnwind = false;
  var discardForwardHistory = false;
  var submissionSealed = false;
  var arrivalOpen = false;
  var draftTimer = 0;

  function clipText(value, max) {
    var text = typeof value === "string" ? value : "";

    if (text.length > max) {
      return text.slice(0, max);
    }

    return text;
  }

  function allowedPermission(value) {
    var i;

    for (i = 0; i < PERMISSION_OPTIONS.length; i += 1) {
      if (PERMISSION_OPTIONS[i].value === value) {
        return value;
      }
    }

    return "";
  }

  function markerFrom(raw) {
    var marker;
    var step;

    if (!raw || !raw.ncMrSubmit) {
      return null;
    }

    marker = raw.ncMrSubmit;
    step = parseInt(marker.step, 10);

    if (step < 1 || step > totalSteps()) {
      return null;
    }

    return {
      step: step,
      view: marker.view === "terms" ? "terms" : "form",
    };
  }

  function currentMarker() {
    try {
      return markerFrom(history.state);
    } catch (error) {
      return null;
    }
  }

  function pushMarker(step, view) {
    try {
      history.pushState(
        {
          ncMrSubmit: {
            step: step,
            view: view === "terms" ? "terms" : "form",
          },
        },
        ""
      );
      return true;
    } catch (error) {
      return false;
    }
  }

  function seedHistory() {
    var step = state.step;
    var i;

    for (i = 1; i <= step; i += 1) {
      if (!pushMarker(i, "form")) {
        return;
      }
    }

    if (isTermsOpen()) {
      pushMarker(step, "terms");
    }
  }

  function draftRecord() {
    var record = {
      step: state.step,
      view: isTermsOpen() ? "terms" : "form",
      ageAttestation: !!state.ageAttestation,
      letterBody: clipText(state.letterBody, LETTER_MAX),
      permissionTier: allowedPermission(state.permissionTier),
      publicCreditName: clipText(state.publicCreditName, 4000),
      displayName: clipText(state.displayName, 4000),
      privateEmail: clipText(state.privateEmail, 4000),
      termsAccepted: !!state.termsAccepted,
      reviewLetterExpanded: !!state.reviewLetterExpanded,
      idempotencyKey: clipText(state.idempotencyKey, 64),
    };

    if (recipientStepEnabled()) {
      record.recipientStep = true;
      record.recipientOriginal = clipText(state.recipientOriginal, RECIPIENT_TEXT_MAX);
      record.recipientDisplayChoice = allowedRecipientChoice(state.recipientDisplayChoice);
      record.recipientPublicWording = clipText(state.recipientPublicWording, RECIPIENT_TEXT_MAX);
    }

    return record;
  }

  function draftHasProgress(record) {
    if (!record) {
      return false;
    }

    return !!(
      record.letterBody ||
      record.displayName ||
      record.privateEmail ||
      record.permissionTier ||
      record.publicCreditName ||
      record.recipientOriginal ||
      record.recipientDisplayChoice ||
      record.recipientPublicWording ||
      record.ageAttestation ||
      record.termsAccepted ||
      (record.step && record.step > 1)
    );
  }

  function memoryHasProgress() {
    return draftHasProgress(draftRecord());
  }

  function markSubmissionSeal() {
    try {
      sessionStorage.setItem(SEAL_KEY, "1");
    } catch (error) {
      /* sessionStorage unavailable or blocked */
    }
  }

  function clearSubmissionSeal() {
    submissionSealed = false;

    try {
      sessionStorage.removeItem(SEAL_KEY);
    } catch (error) {
      /* sessionStorage unavailable or blocked */
    }
  }

  function readSubmissionSeal() {
    try {
      return sessionStorage.getItem(SEAL_KEY) === "1";
    } catch (error) {
      return false;
    }
  }

  function saveDraft() {
    if (submissionSealed) {
      return;
    }

    var record = draftRecord();

    try {
      if (!draftHasProgress(record)) {
        sessionStorage.removeItem(DRAFT_KEY);
        return;
      }

      sessionStorage.setItem(DRAFT_KEY, JSON.stringify(record));
    } catch (error) {
      /* sessionStorage unavailable or blocked */
    }
  }

  function clearDraft() {
    if (draftTimer) {
      window.clearTimeout(draftTimer);
      draftTimer = 0;
    }

    restoredView = "form";

    try {
      sessionStorage.removeItem(DRAFT_KEY);
    } catch (error) {
      /* sessionStorage unavailable or blocked */
    }
  }

  function scheduleDraftSave() {
    if (draftTimer) {
      window.clearTimeout(draftTimer);
    }

    draftTimer = window.setTimeout(function () {
      draftTimer = 0;
      saveDraft();
    }, 200);
  }

  function readDraft() {
    var raw;
    var data;

    try {
      raw = sessionStorage.getItem(DRAFT_KEY);
    } catch (error) {
      return null;
    }

    if (!raw) {
      return null;
    }

    try {
      data = JSON.parse(raw);
    } catch (error) {
      return null;
    }

    if (!data || typeof data !== "object") {
      return null;
    }

    return data;
  }

  function restoreDraft() {
    var data = readDraft();
    var step;

    restoredView = "form";

    if (!data) {
      return false;
    }

    step = remapDraftStep(parseInt(data.step, 10), data.recipientStep === true);
    state = createInitialState();
    state.ageAttestation = data.ageAttestation === true;
    state.letterBody = clipText(data.letterBody, LETTER_MAX);
    if (recipientStepEnabled() && data.recipientStep === true) {
      state.recipientOriginal = clipText(data.recipientOriginal, RECIPIENT_TEXT_MAX);
      state.recipientDisplayChoice = allowedRecipientChoice(data.recipientDisplayChoice);
      state.recipientPublicWording = clipText(data.recipientPublicWording, RECIPIENT_TEXT_MAX);
    }
    state.permissionTier = allowedPermission(data.permissionTier);
    state.publicCreditName = clipText(data.publicCreditName, 4000);
    state.displayName = clipText(data.displayName, 4000);
    state.privateEmail = clipText(data.privateEmail, 4000);
    state.termsAccepted = data.termsAccepted === true;
    state.reviewLetterExpanded = data.reviewLetterExpanded === true;
    state.idempotencyKey = clipText(data.idempotencyKey, 64);
    restoredView = data.view === "terms" ? "terms" : "form";
    step = clampToValidStep(step);
    state.step = step >= 1 && step <= totalSteps() ? step : 1;
    return true;
  }

  function showStepFromMarker(marker) {
    state.step = clampToValidStep(marker.step);
    state.sendInFlight = false;
    render();

    if (marker.view === "terms" && state.step === reviewStep()) {
      showTerms();
    }
  }

  function presentDialog() {
    if (submitDevPreview) {
      submitDevPreview.hidden = !isDevHost();
    }

    if (!submitDialog.open && typeof submitDialog.showModal === "function") {
      submitDialog.showModal();
    }
  }

  function closeDialogUiOnly() {
    if (!submitDialog.open) {
      return;
    }

    closeTerms(false);
    clearAssociationToken();
    submitDialog.close();
    window.scrollTo(0, scrollRestore);

    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
    }
  }

  function startUnwind() {
    if (!currentMarker()) {
      unwinding = false;
      return;
    }

    unwinding = true;

    window.setTimeout(function () {
      if (unwinding) {
        history.back();
      }
    }, 0);
  }

  function onPopState(event) {
    var marker;

    if (unwinding) {
      if (event.state && event.state.ncMrSubmit) {
        window.setTimeout(function () {
          if (unwinding) {
            history.back();
          }
        }, 0);
        return;
      }

      unwinding = false;

      if (discardForwardHistory) {
        discardForwardHistory = false;

        try {
          history.pushState(null, "");
        } catch (error) {
          /* history unavailable */
        }
      }

      if (reopenAfterUnwind && !submissionSealed) {
        reopenAfterUnwind = false;
        resetAndOpen(lastTrigger);
      } else {
        reopenAfterUnwind = false;
      }

      return;
    }

    marker = markerFrom(event.state);

    if (submissionSealed) {
      if (marker) {
        discardFormHistory();
      } else if (submitDialog.open && !arrivalOpen) {
        closeDialogUiOnly();
      }
      return;
    }

    if (!marker) {
      if (submitDialog.open) {
        syncFieldsFromDom();

        if (!submissionSealed) {
          saveDraft();
        }

        closeDialogUiOnly();
      }
      return;
    }

    syncFieldsFromDom();

    if (!submitDialog.open) {
      presentDialog();
    }

    showStepFromMarker(marker);
    saveDraft();
  }

  function resetAndOpen(trigger) {
    var marker;

    if (unwinding) {
      if (!submissionSealed) {
        reopenAfterUnwind = true;
      }
      return;
    }

    if (submitDialog.open) {
      return;
    }

    clearSubmissionSeal();
    lastTrigger = trigger || document.activeElement;
    scrollRestore = window.scrollY;

    if (!restoreDraft() && !memoryHasProgress()) {
      state = createInitialState();
    }

    presentDialog();
    marker = currentMarker();

    if (marker) {
      showStepFromMarker(marker);
      return;
    }

    render();

    if (restoredView === "terms" && state.step === reviewStep()) {
      showTerms();
    }

    seedHistory();
    saveDraft();
  }

  function discardFormHistory() {
    if (!currentMarker()) {
      return;
    }

    discardForwardHistory = true;
    startUnwind();
  }

  function closeSubmitDialog() {
    if (unwinding) {
      if (!submissionSealed) {
        saveDraft();
      }

      closeDialogUiOnly();
      return;
    }

    if (!submitDialog.open && !currentMarker()) {
      return;
    }

    if (!submissionSealed) {
      saveDraft();
    }

    closeDialogUiOnly();
    startUnwind();
  }

  function persistDraftIfActive() {
    if (submissionSealed) {
      return;
    }

    if (!submitDialog.open && !memoryHasProgress()) {
      return;
    }

    syncFieldsFromDom();
    saveDraft();
  }

  function bootFromHistory() {
    var marker;

    if (readSubmissionSeal()) {
      submissionSealed = true;
      clearDraft();

      if (currentMarker()) {
        discardFormHistory();
      }

      return;
    }

    marker = currentMarker();

    if (!marker) {
      return;
    }

    lastTrigger = writeCta;
    scrollRestore = window.scrollY;
    restoreDraft();
    presentDialog();
    showStepFromMarker(marker);
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

      if (isTermsOpen()) {
        requestCloseTerms();
        return;
      }

      closeSubmitDialog();
    });

    submitDialog.addEventListener("click", function (event) {
      if (event.target === submitDialog) {
        if (isTermsOpen()) {
          requestCloseTerms();
          return;
        }

        closeSubmitDialog();
      }
    });

    if (termsPanel) {
      termsPanel.addEventListener("click", function (event) {
        if (event.target.closest(".mail-submit__terms-back")) {
          event.preventDefault();
          requestCloseTerms();
        }
      });
    }

    submitBody.addEventListener("click", function (event) {
      var reviewToggle;

      if (event.target.closest(".mail-submit__terms-open")) {
        event.preventDefault();
        openTerms();
        return;
      }

      reviewToggle = event.target.closest(".mail-submit__review-toggle");

      if (reviewToggle) {
        event.preventDefault();
        state.reviewLetterExpanded = !state.reviewLetterExpanded;
        render({ preserveFocus: true });
        reviewToggle = submitBody.querySelector(".mail-submit__review-toggle");

        if (reviewToggle) {
          reviewToggle.focus({ preventScroll: true });
        }

        saveDraft();
        return;
      }

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
        saveDraft();
        return;
      }

      if (target.matches('[data-field="permissionTier"], [data-field="recipientDisplayChoice"]')) {
        syncFieldsFromDom();
        render();
        saveDraft();
        return;
      }

      if (target.matches('[data-field="termsAccepted"]')) {
        state.termsAccepted = target.checked;
        saveDraft();
      }
    });

    submitBody.addEventListener("input", function (event) {
      if (
        event.target.matches(
          '[data-field="letterBody"], [data-field="recipientOriginal"], [data-field="recipientPublicWording"], [data-field="publicCreditName"], [data-field="displayName"], [data-field="privateEmail"]'
        )
      ) {
        syncFieldsFromDom();
        scheduleDraftSave();
      }
    });

    window.addEventListener("popstate", onPopState);
    window.addEventListener("pagehide", persistDraftIfActive);
  }

  bindEvents();
  bootFromHistory();
})();
