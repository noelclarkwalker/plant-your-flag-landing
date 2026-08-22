/**
 * ARTIFACTS PROTOTYPE 003 — throwaway emotional eval
 *
 * Continues from Prototype 001 camera complete.
 * Dev preview: ?artifacts-003=1 (skips 001, runs sequence from monogram position)
 *
 * Remove: this file, artifacts-prototype-003.css, index.html lines.
 */
(function () {
  const DEV_PREVIEW = new URLSearchParams(window.location.search).has("artifacts-003");

  const ASSETS = {
    painting: "assets/images/watercolor-flowers-1.PNG",
    easel: "assets/images/art-easel-outdoor.JPG",
    path: "assets/images/cobblestone-path.PNG",
    butterflyIll: "assets/images/butterfly-illustration.PNG",
    butterflyReal: "assets/images/butterfly-real-01.MP4",
    butterflyRealAlt: "assets/images/butterfly-real-02.MP4",
    lightPass: "assets/images/light-pass.PNG",
    landscapeDay: "assets/images/landscape-lehigh-valley.JPG",
    landscapeNight: "assets/images/night-fireflies.PNG",
    flag: "assets/images/flag-monogram-full.png",
  };

  const monogram = document.querySelector("#scene-02 .portal-signature img");
  const experience = document.getElementById("experience");
  const scene01 = document.getElementById("scene-01");
  const scene02 = document.getElementById("scene-02");
  const scene03 = document.getElementById("scene-03");

  let started = false;
  /** @type {gsap.core.Timeline | null} */
  let sequenceTimeline = null;
  /** @type {HTMLElement | null} */
  let activeStage = null;

  function isLocalDay() {
    const hour = new Date().getHours();
    return hour >= 7 && hour < 19;
  }

  function hideExperienceContent() {
    if (!experience) {
      return;
    }

    experience.setAttribute("aria-hidden", "true");
    experience.hidden = true;
  }

  function revealScene01Only() {
    if (scene02) {
      scene02.hidden = true;
    }

    if (scene03) {
      scene03.hidden = true;
    }

    if (scene01) {
      scene01.hidden = false;
    }

    if (experience) {
      experience.hidden = false;
      experience.removeAttribute("aria-hidden");
    }
  }

  function unlockPageScroll() {
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.left = "";
    document.body.style.width = "";
    document.body.style.paddingRight = "";
  }

  function teardownCameraRig() {
    const plane = document.querySelector("#scene-02 .continuous-surface");
    if (!plane) {
      return;
    }

    const rig = plane.closest(".camera-prototype-plane");
    if (rig) {
      rig.style.transform = "";
      rig.style.removeProperty("--camera-z");
    }

    const viewport = plane.closest(".camera-prototype-viewport");
    if (viewport && viewport.parentNode) {
      viewport.parentNode.insertBefore(plane, viewport);
      viewport.remove();
    }
  }

  function killSequenceMotion(stage) {
    const gsap = window.gsap;
    if (!gsap) {
      return;
    }

    if (sequenceTimeline) {
      sequenceTimeline.kill();
      sequenceTimeline = null;
    }

    if (stage) {
      gsap.killTweensOf(stage);
      gsap.killTweensOf(stage.querySelectorAll("*"));
    }
  }

  function stopStageMedia(stage) {
    if (!stage) {
      return;
    }

    stage.querySelectorAll("video").forEach((video) => {
      video.pause();
      video.removeAttribute("src");
      video.load();
    });
  }

  function removeStage(stage) {
    if (stage && stage.parentNode) {
      stage.remove();
    }

    if (activeStage === stage) {
      activeStage = null;
    }
  }

  function fullTeardown() {
    const stage = activeStage || document.querySelector(".artifacts-prototype-003");

    killSequenceMotion(stage);
    stopStageMedia(stage);
    removeStage(stage);

    teardownCameraRig();
    unlockPageScroll();

    document.body.dataset.artifactsPrototype003 = "done";
    document.body.dataset.cameraPrototype = "complete";

    window.scrollTo(0, 0);
    revealScene01Only();
  }

  function mountStage() {
    hideExperienceContent();

    const rect = monogram?.getBoundingClientRect();
    const monogramSrc =
      monogram?.currentSrc || monogram?.src || "assets/images/monogram-watercolor.png";
    const stage = document.createElement("div");
    stage.className = "artifacts-prototype-003";
    stage.setAttribute("aria-hidden", "true");

    const landscapeSrc = isLocalDay() ? ASSETS.landscapeDay : ASSETS.landscapeNight;

    stage.innerHTML = `
      <div class="artifacts-prototype-003__rig">
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--monogram">
          <img src="${monogramSrc}" alt="" />
        </div>
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--easel">
          <img class="artifacts-prototype-003__easel-bg" src="${ASSETS.easel}" alt="" />
          <img class="artifacts-prototype-003__canvas-painting" src="${ASSETS.painting}" alt="" />
          <img class="artifacts-prototype-003__canvas-butterfly" src="${ASSETS.butterflyIll}" alt="" />
        </div>
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--path">
          <img class="artifacts-prototype-003__path-img" src="${ASSETS.path}" alt="" />
        </div>
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--landscape">
          <img class="artifacts-prototype-003__landscape-img" src="${landscapeSrc}" alt="" />
        </div>
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--light">
          <img class="artifacts-prototype-003__light-pass" src="${ASSETS.lightPass}" alt="" />
        </div>
        <video class="artifacts-prototype-003__butterfly-video" src="${ASSETS.butterflyReal}" muted playsinline loop autoplay></video>
        <div class="artifacts-prototype-003__layer artifacts-prototype-003__layer--flag">
          <img class="artifacts-prototype-003__flag-img" src="${ASSETS.flag}" alt="" />
        </div>
        <div class="artifacts-prototype-003__grain"></div>
      </div>
    `;

    document.body.appendChild(stage);
    document.body.dataset.artifactsPrototype003 = "active";
    activeStage = stage;

    const monoImg = stage.querySelector(".artifacts-prototype-003__layer--monogram img");
    if (monoImg && rect) {
      monoImg.style.left = `${rect.left}px`;
      monoImg.style.top = `${rect.top}px`;
      monoImg.style.width = `${rect.width}px`;
      const ox = ((rect.left + rect.width / 2) / window.innerWidth) * 100;
      const oy = ((rect.top + rect.height / 2) / window.innerHeight) * 100;
      monoImg.style.setProperty("--ap3-ox", `${ox}%`);
      monoImg.style.setProperty("--ap3-oy", `${oy}%`);
    }

    return stage;
  }

  function runSequence(stage) {
    const gsap = window.gsap;
    if (!gsap) {
      return;
    }

    const monoLayer = stage.querySelector(".artifacts-prototype-003__layer--monogram");
    const monoImg = monoLayer?.querySelector("img");
    if (!monoLayer || !monoImg) {
      return;
    }

    const easelLayer = stage.querySelector(".artifacts-prototype-003__layer--easel");
    const butterflyIll = stage.querySelector(".artifacts-prototype-003__canvas-butterfly");
    const pathLayer = stage.querySelector(".artifacts-prototype-003__layer--path");
    const pathImg = stage.querySelector(".artifacts-prototype-003__path-img");
    const landscapeLayer = stage.querySelector(".artifacts-prototype-003__layer--landscape");
    const lightLayer = stage.querySelector(".artifacts-prototype-003__layer--light");
    const butterflyVideo = stage.querySelector(".artifacts-prototype-003__butterfly-video");
    const flagLayer = stage.querySelector(".artifacts-prototype-003__layer--flag");
    const flagImg = stage.querySelector(".artifacts-prototype-003__flag-img");

    gsap.set(monoLayer, { opacity: 1 });

    const rect = monogram?.getBoundingClientRect();
    const fillScale =
      rect && rect.width > 0 && rect.height > 0
        ? Math.max(window.innerWidth / rect.width, window.innerHeight / rect.height) * 1.15
        : 4.2;

    gsap.set(monoImg, { filter: "blur(14px)", scale: fillScale });
    gsap.set(easelLayer, { opacity: 0 });
    gsap.set(pathLayer, { opacity: 0 });
    gsap.set(landscapeLayer, { opacity: 0 });
    gsap.set(lightLayer, { opacity: 0 });
    gsap.set(flagLayer, { opacity: 0 });
    gsap.set(butterflyIll, { opacity: 0 });
    gsap.set(butterflyVideo, { opacity: 0 });
    butterflyVideo.play().catch(function () {});

    sequenceTimeline = gsap.timeline({
      defaults: { ease: "power2.inOut" },
      onInterrupt: fullTeardown,
    });

    sequenceTimeline
      .to(monoImg, { filter: "blur(0px)", duration: 2.4, ease: "power1.out" }, 0)
      .to(
        monoImg,
        { scale: fillScale * 1.08, x: "-1%", y: "-0.5%", duration: 2.8, ease: "power1.inOut" },
        0,
      )
      .to(monoLayer, { opacity: 0, duration: 1.6 }, 2.4)
      .to(easelLayer, { opacity: 1, duration: 2.2 }, 2.6)
      .fromTo(
        easelLayer,
        { scale: 1.35, y: "-4%" },
        { scale: 1, y: "0%", duration: 3.6, ease: "power2.out" },
        2.4,
      )
      .to({}, { duration: 1.4 })
      .to(butterflyIll, { opacity: 1, duration: 1.2 }, 6.8)
      .to(
        butterflyIll,
        { y: -18, x: 8, rotation: -6, duration: 3.2, ease: "sine.inOut" },
        7.4,
      )
      .to(
        easelLayer,
        { y: "6%", scale: 1.08, duration: 4.2, ease: "power1.inOut" },
        8.2,
      )
      .to(pathLayer, { opacity: 1, duration: 2.4 }, 9.6)
      .to(pathImg, { y: "-8%", scale: 1.06, duration: 4, ease: "power1.out" }, 9.8)
      .to(butterflyIll, { opacity: 0, y: -42, x: 24, duration: 1.6 }, 10.8)
      .to(butterflyVideo, { opacity: 1, duration: 1.4 }, 11.2)
      .to(
        butterflyVideo,
        { y: -60, x: 40, scale: 1.15, duration: 3.6, ease: "sine.out" },
        11.4,
      )
      .to(easelLayer, { opacity: 0, duration: 2 }, 12.8)
      .to(pathLayer, { opacity: 0, duration: 2.2 }, 13.2)
      .to(landscapeLayer, { opacity: 1, duration: 2.8 }, 13)
      .to(lightLayer, { opacity: 1, duration: 2.4 }, 13.4)
      .to(butterflyVideo, { opacity: 0, duration: 2 }, 15.6)
      .to(flagLayer, { opacity: 1, duration: 2.4 }, 16.8)
      .fromTo(
        flagImg,
        { y: 24, rotation: -2 },
        { y: 0, rotation: 0, duration: 3.2, ease: "sine.out" },
        16.8,
      )
      .to(
        flagImg,
        {
          rotation: 1.5,
          duration: 2.4,
          repeat: 2,
          yoyo: true,
          ease: "sine.inOut",
        },
        18.4,
      )
      .to({}, { duration: 2.2 })
      .call(fullTeardown);
  }

  function begin() {
    if (started || !monogram) {
      return;
    }

    if (document.body.dataset.cameraPrototype !== "complete") {
      return;
    }

    if (!window.gsap) {
      return;
    }

    started = true;
    const stage = mountStage();

    window.requestAnimationFrame(function () {
      runSequence(stage);
    });
  }

  function watchCameraComplete() {
    if (document.body.dataset.cameraPrototype !== "complete") {
      const observer = new MutationObserver(function () {
        if (document.body.dataset.cameraPrototype === "complete") {
          observer.disconnect();
          begin();
        }
      });

      observer.observe(document.body, {
        attributes: true,
        attributeFilter: ["data-camera-prototype"],
      });

      return;
    }

    begin();
  }

  if (DEV_PREVIEW) {
    document.body.dataset.cameraPrototype = "complete";
    begin();
    return;
  }

  if (!monogram) {
    return;
  }

  watchCameraComplete();
})();
