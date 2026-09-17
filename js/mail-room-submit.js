(function () {
  "use strict";

  var TOTAL_STEPS = 5;
  var LETTER_MAX = 50000;

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
      permissionTier: "",
      publicCreditName: "",
      displayName: "",
      privateEmail: "",
      termsAccepted: false,
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
      '<p class="mail-submit__helper">Your name and email are just for my private records. They won\'t be shown publicly unless you chose to be credited, and submitting a letter doesn\'t sign you up for emails or mean you\'ll receive a reply.</p>' +
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

  function renderStep5() {
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
      "<span>I understand how my letter may be used based on the sharing choice I selected, and I agree to the Mail Room Submission Terms.</span>" +
      "</label>" +
      '<p class="mail-submit__crisis-note">A note: The Mail Room isn\'t monitored continuously and isn\'t an emergency or crisis service. If you or someone else is in immediate danger, please use an emergency or crisis service. In the U.S., call or text 988, or call 911 for an immediate emergency.</p>' +
      '<div class="mail-submit__nav mail-submit__nav--send">' +
      '<button type="button" class="mail-submit__back"' +
      (state.sendInFlight ? " disabled" : "") +
      ">Back</button>" +
      '<button type="button" class="mail-submit__send"' +
      (state.sendInFlight ? " disabled" : "") +
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
        return renderStep3();
      case 4:
        return renderStep4();
      case 5:
        return renderStep5();
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
        if (!state.permissionTier) {
          return "Please choose a sharing permission.";
        }
        if (state.permissionTier === "named_credit" && !String(state.publicCreditName || "").trim()) {
          return "Please provide a public credit name.";
        }
        return "";
      case 4:
        if (!String(state.displayName || "").trim()) {
          return "Please enter your name.";
        }
        if (!isValidEmail(state.privateEmail)) {
          return "Please enter a valid email address.";
        }
        return "";
      case 5:
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
      state.sendInFlight = false;
      state.sendError = "";
      render();
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
          "</p>";

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

      fetch(config.submitUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-NoelClark-Mail-Room-Questionnaire-Nonce": config.nonce,
        },
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
      wipeHandoff();
      preview.remove();
      closeSubmitDialog();
    });

    if (typeof preview.showModal === "function") {
      preview.showModal();
    }
  }

  function attemptSend() {
    syncFieldsFromDom();
    var error = validateStep(5);
    var config = getSubmitConfig();

    if (error) {
      state.sendAttempted = false;
      state.sendInFlight = false;
      state.sendError = "";
      render();
      submitBody.insertAdjacentHTML("afterbegin", stepError(error));
      return;
    }

    if (!config || !config.submitUrl || !config.nonce) {
      state.sendAttempted = true;
      state.sendInFlight = false;
      state.sendError = "";
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

    fetch(config.submitUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-NoelClark-Mail-Room-Nonce": config.nonce,
      },
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
      })
      .catch(function () {
        state.sendInFlight = false;
        clearAssociationToken();
        state.sendError =
          "Network error. Your letter may not have been sent. Please try again.";
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

    clearAssociationToken();
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
    });

    submitBody.addEventListener("input", function (event) {
      if (
        event.target.matches(
          '[data-field="letterBody"], [data-field="publicCreditName"], [data-field="displayName"], [data-field="privateEmail"]'
        )
      ) {
        syncFieldsFromDom();
      }
    });
  }

  bindEvents();
})();
