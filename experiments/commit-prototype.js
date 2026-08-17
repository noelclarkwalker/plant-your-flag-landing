/**
 * COMMIT PROTOTYPE 003 — throwaway
 *
 * Question: Does clicking the signature feel like commitment, not a button?
 * Concept: Silent assent — no affordance; consequence only (fade to black).
 *
 * Remove: this file, commit-prototype.css, index.html lines.
 */
(function () {
  const signature = document.querySelector("#scene-02 .portal-signature");
  const socialPost = document.querySelector("#scene-02 .social-post");

  if (!signature || !socialPost) {
    return;
  }

  let committed = false;
  let armed = false;
  let veil = null;

  function isReady() {
    return (
      socialPost.classList.contains("platform-surrendered") &&
      signature.classList.contains("is-revealed")
    );
  }

  function ensureVeil() {
    if (veil) {
      return veil;
    }

    veil = document.createElement("div");
    veil.className = "commit-prototype-veil";
    veil.setAttribute("aria-hidden", "true");
    document.body.appendChild(veil);
    return veil;
  }

  function armIfReady() {
    if (committed || armed) {
      return;
    }

    if (!isReady()) {
      return;
    }

    armed = true;
    document.body.classList.add("commit-prototype");
  }

  function onSignatureClick(event) {
    if (!armed || committed) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    committed = true;

    const overlay = ensureVeil();

    requestAnimationFrame(() => {
      overlay.classList.add("is-held");
    });
  }

  signature.addEventListener("click", onSignatureClick);

  const observer = new MutationObserver(armIfReady);
  observer.observe(signature, { attributes: true, attributeFilter: ["class"] });
  observer.observe(socialPost, { attributes: true, attributeFilter: ["class"] });

  armIfReady();
})();
