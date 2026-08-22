/**
 * Portal signature — scroll-linked shared element travel.
 * Single .portal-signature; scrubbed fixed-position path to Mail Room envelope.
 */
(function () {
  const ENVELOPE_X = 0.5;
  const ENVELOPE_Y = 0.36;

  /** @type {HTMLElement | null} */
  let signature = null;
  /** @type {HTMLImageElement | null} */
  let monogram = null;
  /** @type {HTMLElement | null} */
  let mailRoom = null;
  /** @type {HTMLElement | null} */
  let mailPoster = null;

  /** @type {ScrollTrigger | null} */
  let travelTrigger = null;
  /** @type {HTMLElement | null} */
  let flowPlaceholder = null;

  let initialized = false;
  let scrollLinked = false;
  let docked = false;

  /** @type {{ left: number; top: number; width: number } | null} */
  let travelStart = null;

  function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  }

  function gsapReady() {
    return typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined";
  }

  function signatureReady() {
    return signature?.classList.contains("is-revealed") ?? false;
  }

  function getMailPosterImage() {
    return mailPoster?.querySelector("img") ?? null;
  }

  function getEnvelopeDock() {
    const poster = getMailPosterImage();

    if (!poster || !signature) {
      return null;
    }

    const rect = poster.getBoundingClientRect();
    const width = signature.offsetWidth;
    const height = signature.offsetHeight;

    if (rect.width <= 0 || rect.height <= 0 || width <= 0 || height <= 0) {
      return null;
    }

    return {
      left: rect.left + rect.width * ENVELOPE_X - width / 2,
      top: rect.top + rect.height * ENVELOPE_Y - height / 2,
    };
  }

  function captureTravelStart() {
    if (!signature) {
      return null;
    }

    const rect = signature.getBoundingClientRect();

    return {
      left: rect.left,
      top: rect.top,
      width: rect.width,
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

    const start = travelStart ?? captureTravelStart();

    if (!start) {
      return;
    }

    travelStart = start;
    ensureFlowPlaceholder();

    signature.classList.add("is-scroll-linked");
    scrollLinked = true;

    gsap.set(signature, {
      position: "fixed",
      left: start.left,
      top: start.top,
      width: start.width,
      marginTop: 0,
      transform: "none",
    });
  }

  function demoteFromScrollLinked() {
    if (!signature || !scrollLinked) {
      return;
    }

    signature.classList.remove("is-scroll-linked", "is-docked");
    mailRoom?.classList.remove("is-signature-dock");
    gsap.set(signature, { clearProps: "position,left,top,width,transform" });

    scrollLinked = false;
    docked = false;
    removeFlowPlaceholder();
  }

  function applyTravelProgress(progress) {
    if (!signature) {
      return;
    }

    if (progress <= 0) {
      travelStart = captureTravelStart();
      demoteFromScrollLinked();
      return;
    }

    if (!travelStart) {
      travelStart = captureTravelStart();
    }

    if (!scrollLinked) {
      promoteToScrollLinked();
    }

    const start = travelStart;
    const end = getEnvelopeDock();

    if (!start || !end) {
      return;
    }

    const left = gsap.utils.interpolate(start.left, end.left, progress);
    const top = gsap.utils.interpolate(start.top, end.top, progress);

    gsap.set(signature, { left, top });

    if (progress >= 1) {
      if (!docked) {
        docked = true;
        signature.classList.add("is-docked");
        mailRoom?.classList.add("is-signature-dock");
      }

      const dock = getEnvelopeDock();

      if (dock) {
        gsap.set(signature, { left: dock.left, top: dock.top });
      }
    } else if (docked) {
      docked = false;
      signature.classList.remove("is-docked");
      mailRoom?.classList.remove("is-signature-dock");
    }
  }

  function buildTravelTrigger() {
    if (!gsapReady() || !signature || !mailPoster) {
      return;
    }

    travelTrigger?.kill();
    gsap.registerPlugin(ScrollTrigger);

    travelTrigger = ScrollTrigger.create({
      trigger: signature,
      start: "top 82%",
      endTrigger: mailPoster,
      end: "center center",
      scrub: 0,
      invalidateOnRefresh: true,
      onUpdate: (self) => {
        applyTravelProgress(self.progress);
      },
      onRefresh: () => {
        if (scrollLinked && travelTrigger) {
          applyTravelProgress(travelTrigger.progress);
        }
      },
    });
  }

  function initScrollTravel() {
    if (initialized || prefersReducedMotion() || !gsapReady()) {
      return;
    }

    signature = document.querySelector("#scene-02 .portal-signature");
    monogram = signature?.querySelector("img") ?? null;
    mailRoom = document.getElementById("room-mail-room");
    mailPoster = mailRoom?.querySelector(".room-card__poster") ?? null;

    if (!signature || !monogram || !mailRoom || !mailPoster) {
      return;
    }

    if (!signatureReady()) {
      const revealObserver = new MutationObserver(() => {
        if (signatureReady()) {
          revealObserver.disconnect();

          if (!initialized) {
            initialized = true;
            buildTravelTrigger();

            const posterImage = getMailPosterImage();

            if (posterImage && !posterImage.complete) {
              posterImage.addEventListener("load", () => ScrollTrigger.refresh(), {
                once: true,
              });
            }

            ScrollTrigger.refresh();
          }
        }
      });

      revealObserver.observe(signature, {
        attributes: true,
        attributeFilter: ["class"],
      });

      return;
    }

    initialized = true;
    buildTravelTrigger();

    const posterImage = getMailPosterImage();

    if (posterImage && !posterImage.complete) {
      posterImage.addEventListener("load", () => ScrollTrigger.refresh(), { once: true });
    }

    ScrollTrigger.refresh();
  }

  function teardownScrollTravel() {
    travelTrigger?.kill();
    travelTrigger = null;
    demoteFromScrollLinked();
    initialized = false;
  }

  window.initPortalSignatureScroll = initScrollTravel;
  window.teardownPortalSignatureScroll = teardownScrollTravel;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initScrollTravel, { once: true });
  } else {
    initScrollTravel();
  }
})();
