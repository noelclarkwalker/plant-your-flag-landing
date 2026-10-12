/**
 * Contact form — enable SEND MESSAGE only when required fields are complete.
 * Server-side validation remains authoritative.
 */
(function () {
  "use strict";

  var success = document.getElementById("contact-success");

  if (success) {
    success.focus();
    return;
  }

  var form = document.getElementById("contact-form");

  if (!form) {
    return;
  }

  var submit = form.querySelector(".contact-form__submit");
  var status = document.getElementById("contact-form-status");
  var fields = form.querySelectorAll("input[required], select[required], textarea[required]");
  var submitting = false;

  if (!submit) {
    return;
  }

  function isComplete(field) {
    if (!field.validity.valid) {
      return false;
    }

    // Whitespace-only text satisfies native `required`, but the server trims it.
    return String(field.value).trim() !== "";
  }

  function setDisabled(disabled) {
    submit.disabled = disabled;
    submit.setAttribute("aria-disabled", disabled ? "true" : "false");
  }

  function sync() {
    if (submitting) {
      return;
    }

    for (var i = 0; i < fields.length; i += 1) {
      if (!isComplete(fields[i])) {
        setDisabled(true);
        return;
      }
    }

    setDisabled(false);
  }

  form.addEventListener("input", sync);
  form.addEventListener("change", sync);

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }

    sync();

    if (submit.disabled) {
      event.preventDefault();
      return;
    }

    submitting = true;
    setDisabled(true);
  });

  // Back/forward cache restores can keep field values and a stale submitting state.
  window.addEventListener("pageshow", function () {
    submitting = false;
    sync();
  });

  if (status && !status.classList.contains("visually-hidden") && status.textContent.trim() !== "") {
    status.setAttribute("tabindex", "-1");
    status.focus();
  }

  sync();
})();
