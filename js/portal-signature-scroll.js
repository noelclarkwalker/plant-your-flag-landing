/**
 * Portal signature — scrubbed travel within the Portal bridge only.
 * Animates the visible monogram to .envelope-dock, then rests there permanently.
 * The page never scrolls programmatically; only the signature moves.
 */
(function () {
  /** @type {HTMLElement | null} */
  let signature = null;
  /** @type {HTMLElement | null} */
  let signatureVisual = null;
  /** @type {HTMLElement | null} */
  let mailRoom = null;
  /** @type {HTMLElement | null} */
  let mailPoster = null;
  /** @type {HTMLElement | null} */
  let envelopeDock = null;
  /** @type {HTMLElement | null} */
  let portalBridge = null;

  /** @type {ScrollTrigger | null} */
  let travelTrigger = null;
  /** @type {HTMLElement | null} */
  let flowPlaceholder = null;

  let initialized = false;
  let scrollLinked = false;
  let dockFinalized = false;

  /** @type {{ left: number; top: number; width: number; height: number } | null} */
  let travelStartDoc = null;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function gsapReady() {
    return typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined";
  }

  function signatureReady() {
    return signature?.classList.contains("is-revealed") ?? false;
  }

  function resolveSignatureVisual() {
    signatureVisual = signature?.querySelector("img") ?? signature;
    return signatureVisual;
  }

  function getMailPosterImage() {
    return mailPoster?.querySelector("img") ?? null;
  }

  function captureTravelStartDoc() {
    const visual = resolveSignatureVisual();

    if (!visual) {
      return null;
    }

    const rect = visual.getBoundingClientRect();

    return {
      left: rect.left + window.scrollX,
      top: rect.top + window.scrollY,
      width: rect.width,
      height: rect.height,
    };
  }

  /** Document-space top-left for the visible monogram anchor. */
  function getDockDocTarget() {
    if (!envelopeDock || !signatureVisual) {
      return null;
    }

    const dockRect = envelopeDock.getBoundingClientRect();
    const width = signatureVisual.offsetWidth;
    const height = signatureVisual.offsetHeight;

    if (width <= 0 || height <= 0) {
      return null;
    }

    const dockCenterX = dockRect.left + window.scrollX + dockRect.width / 2;
    const dockCenterY = dockRect.top + window.scrollY + dockRect.height / 2;

    return {
      left: dockCenterX - width / 2,
      top: dockCenterY - height / 2,
    };
  }

  function docToViewport(docLeft, docTop) {
    return {
      left: docLeft - window.scrollX,
      top: docTop - window.scrollY,
    };
  }

  function ensureFlowPlaceholder() {
    if (!signature || flowPlaceholder) {
      return;
    }

    const styles = getComputedStyle(signature);

    flowPlaceholder = document.createElement("div");
    flowPlaceholder.className = "portal-signature-flow-placeholder";
    flowPlaceholder.setAttribute("aria-hidden", "true");
    flowPlaceholder.style.marginTop = styles.marginTop;
    flowPlaceholder.style.height = `${signature.offsetHeight}px`;
    flowPlaceholder.style.paddingLeft = styles.paddingLeft;

    signature.parentNode?.insertBefore(flowPlaceholder, signature);
  }

  function removeFlowPlaceholder() {
    flowPlaceholder?.remove();
    flowPlaceholder = null;
  }

  function promoteToScrollLinked() {
    if (!signature || scrollLinked) {
      return;
    }

    const start = travelStartDoc ?? captureTravelStartDoc();

    if (!start) {
      return;
    }

    travelStartDoc = start;
    ensureFlowPlaceholder();

    signature.classList.add("is-scroll-linked");
    scrollLinked = true;

    const viewport = docToViewport(start.left, start.top);

    gsap.set(signature, {
      position: "fixed",
      left: viewport.left,
      top: viewport.top,
      width: "auto",
      paddingLeft: 0,
      marginTop: 0,
      transform: "none",
    });
  }

  function demoteFromScrollLinked() {
    if (!signature || !scrollLinked || dockFinalized) {
      return;
    }

    signature.classList.remove("is-scroll-linked");
    gsap.set(signature, { clearProps: "position,left,top,width,transform,paddingLeft" });

    scrollLinked = false;
    removeFlowPlaceholder();
  }

  function finalizeDock() {
    if (!signature || !envelopeDock || dockFinalized) {
      return;
    }

    dockFinalized = true;
    travelTrigger?.kill();
    travelTrigger = null;

    envelopeDock.appendChild(signature);

    signature.classList.remove("is-scroll-linked");
    signature.classList.add("is-docked");

    gsap.set(signature, { clearProps: "all" });

    removeFlowPlaceholder();
    scrollLinked = false;

    document.dispatchEvent(new CustomEvent("portal:signature-docked"));

    if (typeof ScrollTrigger !== "undefined") {
      ScrollTrigger.refresh();
    }
  }

  function applyTravelProgress(progress) {
    if (!signature || dockFinalized) {
      return;
    }

    const clampedProgress = Math.min(Math.max(progress, 0), 1);

    if (clampedProgress <= 0) {
      travelStartDoc = captureTravelStartDoc();
      demoteFromScrollLinked();
      return;
    }

    if (!travelStartDoc) {
      travelStartDoc = captureTravelStartDoc();
    }

    if (!scrollLinked) {
      promoteToScrollLinked();
    }

    const start = travelStartDoc;
    const end = getDockDocTarget();

    if (!start || !end) {
      return;
    }

    const docLeft = gsap.utils.interpolate(start.left, end.left, clampedProgress);
    const docTop = gsap.utils.interpolate(start.top, end.top, clampedProgress);
    const viewport = docToViewport(docLeft, docTop);

    gsap.set(signature, {
      left: viewport.left,
      top: viewport.top,
      transform: "none",
    });

    if (clampedProgress >= 1) {
      finalizeDock();
    }
  }

  function buildTravelTrigger() {
    if (!gsapReady() || !signature || !portalBridge || !envelopeDock) {
      return;
    }

    resolveSignatureVisual();

    travelTrigger?.kill();
    gsap.registerPlugin(ScrollTrigger);

    travelTrigger = ScrollTrigger.create({
      trigger: portalBridge,
      start: "top bottom",
      endTrigger: envelopeDock,
      end: "center center",
      scrub: 0,
      invalidateOnRefresh: true,
      onUpdate: (self) => {
        applyTravelProgress(self.progress);
      },
    });

    const posterImage = getMailPosterImage();

    if (posterImage && !posterImage.complete) {
      posterImage.addEventListener("load", () => ScrollTrigger.refresh(), { once: true });
    }
  }

  function initScrollTravel() {
    if (initialized || !gsapReady() || prefersReducedMotion()) {
      return;
    }

    signature = document.querySelector("#scene-02 .portal-signature");
    mailRoom = document.getElementById("room-mail-room");
    mailPoster = mailRoom?.querySelector(".room-card__poster") ?? null;
    envelopeDock = mailPoster?.querySelector(".envelope-dock") ?? null;
    portalBridge = document.querySelector(".home-portal-bridge");

    if (!signature || !mailRoom || !mailPoster || !envelopeDock || !portalBridge) {
      return;
    }

    resolveSignatureVisual();

    const activate = () => {
      if (initialized) {
        return;
      }

      initialized = true;
      buildTravelTrigger();
      ScrollTrigger.refresh();
    };

    if (!signatureReady()) {
      const revealObserver = new MutationObserver(() => {
        if (signatureReady()) {
          revealObserver.disconnect();
          activate();
        }
      });

      revealObserver.observe(signature, {
        attributes: true,
        attributeFilter: ["class"],
      });

      return;
    }

    activate();
  }

  function teardownScrollTravel() {
    travelTrigger?.kill();
    travelTrigger = null;

    if (!dockFinalized) {
      demoteFromScrollLinked();
    }

    initialized = false;
    dockFinalized = false;
  }

  window.initPortalSignatureScroll = initScrollTravel;
  window.teardownPortalSignatureScroll = teardownScrollTravel;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initScrollTravel, { once: true });
  } else {
    initScrollTravel();
  }
})();
