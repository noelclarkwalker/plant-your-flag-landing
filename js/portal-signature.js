/**
 * Portal signature — continuous scroll handoff (PORTAL_TREATMENT.md).
 *
 * One monogram, no independent motion. It scrolls beneath the Manifesto until
 * the first Homepage Room reaches it, then becomes part of that room in place.
 */
(function () {
  const signature = document.querySelector("#scene-02 .portal-signature");
  const monogram = signature?.querySelector("img");
  const homeRooms = document.querySelector(".home-rooms");

  if (!signature || !monogram || !homeRooms) {
    return;
  }

  const firstRoom = homeRooms.querySelector(".room-card");

  if (!firstRoom) {
    return;
  }

  let anchored = false;
  let scrollPending = false;

  function signatureReady() {
    return signature.classList.contains("is-revealed");
  }

  function roomReachedSignature() {
    const monogramRect = monogram.getBoundingClientRect();
    const roomRect = firstRoom.getBoundingClientRect();

    if (monogramRect.width <= 0 || monogramRect.height <= 0) {
      return false;
    }

    if (roomRect.width <= 0 || roomRect.height <= 0) {
      return false;
    }

    const verticalOverlap =
      roomRect.top <= monogramRect.bottom && roomRect.bottom >= monogramRect.top;

    const horizontalOverlap =
      roomRect.left <= monogramRect.right && roomRect.right >= monogramRect.left;

    return verticalOverlap && horizontalOverlap;
  }

  function createLayoutPlaceholder() {
    const styles = getComputedStyle(signature);
    const placeholder = document.createElement("div");

    placeholder.className = "portal-signature-placeholder";
    placeholder.setAttribute("aria-hidden", "true");
    placeholder.style.marginTop = styles.marginTop;
    placeholder.style.height = `${signature.offsetHeight}px`;

    return placeholder;
  }

  function anchorSignatureToRoom() {
    if (anchored) {
      return;
    }

    const signatureStyles = getComputedStyle(signature);
    const signatureRect = signature.getBoundingClientRect();
    const roomRect = firstRoom.getBoundingClientRect();

    anchored = true;

    const placeholder = createLayoutPlaceholder();
    signature.parentNode.insertBefore(placeholder, signature);

    firstRoom.classList.add("is-portal-signature-host");
    firstRoom.insertBefore(signature, firstRoom.firstChild);

    signature.classList.add("portal-signature--room-anchored");
    signature.style.top = `${signatureRect.top - roomRect.top}px`;
    signature.style.left = `${signatureRect.left - roomRect.left}px`;
    signature.style.width = `${signatureRect.width}px`;
    signature.style.paddingLeft = signatureStyles.paddingLeft;
    signature.style.marginTop = "0";
    signature.style.transform = "none";

    document.body.classList.add("portal-complete");

    document.dispatchEvent(
      new CustomEvent("portal:stamped", {
        detail: { room: firstRoom },
      }),
    );
  }

  function checkHandoff() {
    scrollPending = false;

    if (anchored || !signatureReady()) {
      return;
    }

    if (roomReachedSignature()) {
      anchorSignatureToRoom();
    }
  }

  function scheduleHandoffCheck() {
    if (anchored || scrollPending) {
      return;
    }

    scrollPending = true;
    requestAnimationFrame(checkHandoff);
  }

  window.addEventListener("scroll", scheduleHandoffCheck, { passive: true });
  window.addEventListener("resize", scheduleHandoffCheck, { passive: true });

  const revealObserver = new MutationObserver(scheduleHandoffCheck);
  revealObserver.observe(signature, {
    attributes: true,
    attributeFilter: ["class"],
  });

  scheduleHandoffCheck();
})();
