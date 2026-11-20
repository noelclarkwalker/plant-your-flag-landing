/**
 * P.S. camera journey — Phase 1
 * Frame-locked handoff from living hero video + scene moments.
 * Does not replace ps-data.js / ps-browse.js.
 */
(function () {
  "use strict";

  var body = document.body;
  if (!body.classList.contains("ps-page") || body.classList.contains("ps-work-page")) {
    return;
  }

  var cfg = window.noelclarkV1PsJourney || {};
  var IW = 1280;
  var IH = 720;
  var HERO_POS = { x: 0.76, y: 0.2 };
  /* Recalibrated for 1280×720 video plate; phone zooms moderated for clarity */
  var STOPS = {
    writing: { fx: 0.7, fy: 0.52, z: [1.85, 2.05], seal: "#FF2D78", kind: "Essay" },
    music: { fx: 0.48, fy: 0.3, focus: [0.31, 0.26], z: [1.55, 1.8], seal: "#00BFBF", kind: "Song" },
    art: { fx: 0.84, fy: 0.78, z: [1.6, 1.85], seal: "#a21eff", kind: "Art" },
  };
  var T = { enter: 2200, stop: 1200, back: 1400 };
  var FOCUS = 0.3;
  var NOTES = [659.25, 739.99, 880, 987.77, 1108.73, 1318.51, 1479.98];
  var NAMES = [
    [15, ["Pretty as a Peach", "Light Coral", "Coral Crush", "Rose Garden", "Rosewood"]],
    [38, ["Peach Blossom", "Apricot Glow", "Marigold Hour", "Tangerine Dream", "Ember"]],
    [62, ["Barely Butter", "Buttercup", "Summer Sunset", "Golden Hour", "Egg Yolk"]],
    [170, ["Morning Dew", "Subtle Sage", "Bay Leaf", "Awesome Avocado", "Fern Shade"]],
    [250, ["Morning Mist", "Summer Surf", "Tropical Aqua", "Dusk Blue", "Midnight Lake"]],
    [300, ["Lilac Hush", "Wisteria", "Twilight Orchid", "Violet Hour", "Plum Velvet"]],
    [345, ["Peony Blush", "Petal Pink", "Fuchsia Bloom", "Berry Bramble", "Mulberry"]],
    [361, ["Pretty as a Peach", "Light Coral", "Coral Crush", "Rose Garden", "Rosewood"]],
  ];
  var GREYS = ["Feather White", "Morning Fog", "Stone Path", "Branch Shadow", "Ink Well"];

  var reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  var hero = document.querySelector(".ps-hero");
  var sticky = document.querySelector(".ps-hero__sticky");
  var video = document.querySelector(".ps-hero__video");
  var land = document.getElementById("ps-land");
  var plate = document.getElementById("ps-plate");
  var scene = document.getElementById("ps-scene");
  var trail = document.getElementById("ps-trail");
  var shelf = document.getElementById("ps-journey-shelf");
  var themes = document.getElementById("ps-journey-themes");
  var swing = document.getElementById("ps-swing");
  var chand = document.getElementById("ps-chand");
  var sw = document.getElementById("ps-swatches");
  var chips = document.getElementById("ps-chips");
  var hint = document.getElementById("ps-hint-music");
  var hint2 = document.getElementById("ps-hint-art");
  var searchForm = document.getElementById("ps-journey-search");
  var qInput = document.getElementById("ps-journey-q");
  var sclose = document.getElementById("ps-journey-sclose");

  if (!hero || !sticky || !video || !land || !plate || !trail || !shelf) {
    return;
  }

  var medium = null;
  var tag = "All";
  var q = "";
  var busy = 0;
  var cur = null;
  var REST = 0;
  var capturedTime = 0;
  var captureOk = false;
  var pickCanvas = document.createElement("canvas");
  var pickCtx = pickCanvas.getContext("2d", { willReadFrequently: true });
  var pickReady = false;
  var AC = null;
  var OUT = null;
  var stripTimer = null;
  var strumming = false;
  var lastXtal = null;
  var lastStrikeAt = 0;

  function phone() {
    return innerWidth < 701;
  }

  function $(id) {
    return document.getElementById(id);
  }

  function setStory(m) {
    ["writing", "music", "art"].forEach(function (k) {
      body.classList.toggle("m-" + k, k === m);
    });
    try {
      window.dispatchEvent(
        new CustomEvent("ps-archive-medium", {
          detail: { medium: m || "all", source: "trail" },
        })
      );
    } catch (e) {
      /* CustomEvent unavailable */
    }
  }

  /* Matches .ps-feather { top } in ps-journey.css (1280×720 land space). */
  var FEATHER_LAND_TOP = 360;

  function writingLandPose(vw, vh) {
    var s = STOPS.writing;
    var z = s.z[phone() ? 1 : 0];
    var sc = Math.max(vw / IW, vh / IH) * z;
    var fo = s.focus ? s.focus[phone() ? 1 : 0] : FOCUS;
    var x = vw / 2 - s.fx * IW * sc;
    var y = vh * fo - s.fy * IH * sc;
    x = Math.min(0, Math.max(vw - IW * sc, x));
    y = Math.min(0, Math.max(vh - IH * sc, y));
    return { sc: sc, x: x, y: y };
  }

  /*
   * Tall tablet stages leave the fixed land-space feather floating mid-frame,
   * far above the theme labels / card shelf. Nudge land `top` so the resting
   * feather sits near the label band (phone relationship) without diving into
   * the work cards. Phone / short desktop stages already match — leave CSS.
   */
  function alignWritingFeather(vw, vh) {
    var feather = land && land.querySelector(".ps-feather");
    if (!feather || !themes || !shelf || !scene) return;

    feather.style.top = FEATHER_LAND_TOP + "px";

    var pose = writingLandPose(vw, vh);
    var sc = pose.sc;
    if (!sc) return;

    var sceneBox = scene.getBoundingClientRect();
    var featherH = feather.offsetHeight * sc;
    var fTop = sceneBox.top + pose.y + FEATHER_LAND_TOP * sc;
    var fBottom = fTop + featherH;
    var fMid = fTop + featherH / 2;

    var themesBox = themes.getBoundingClientRect();
    var shelfBox = shelf.getBoundingClientRect();
    var gapAboveThemes = themesBox.top - fBottom;

    /* Phone reference mid is ~65px above themes; skip when already near/into band. */
    if (gapAboveThemes < 36) {
      feather.style.removeProperty("top");
      return;
    }

    var targetMid = themesBox.top - 65;
    var maxMid = shelfBox.top + 8 - featherH / 2;
    var desiredMid = Math.min(targetMid, maxMid);
    var newTop = FEATHER_LAND_TOP + (desiredMid - fMid) / sc;
    newTop = Math.max(280, Math.min(520, newTop));
    feather.style.top = newTop + "px";
  }

  function resetFeatherLanding() {
    var feather = land && land.querySelector(".ps-feather");
    if (feather) feather.style.removeProperty("top");
  }

  function restH() {
    sticky.style.height = "";
    sticky.style.removeProperty("--ps-stage-h");
    return sticky.getBoundingClientRect().height;
  }

  function viewH() {
    return (window.visualViewport && visualViewport.height) || innerHeight;
  }

  function openH() {
    var chrome = document.querySelector(".ps-bar");
    var chromeH = chrome ? chrome.offsetHeight : 64;
    var vh = viewH();
    var head = vh < 760 ? 0 : document.querySelector(".site-header")
      ? document.querySelector(".site-header").offsetHeight
      : 0;
    /* Leave room for the trail; never let the stage claim the full visual viewport. */
    return Math.max(REST, vh - head - chromeH);
  }

  function setStageH(h, ms) {
    sticky.style.setProperty("--ps-stage-grow", (reduce ? 0 : ms) + "ms");
    sticky.style.setProperty("--ps-stage-h", h + "px");
    sticky.style.height = h + "px";
  }

  function coverFrame(posX, posY, zoom, vw, vh) {
    var sc = Math.max(vw / IW, vh / IH) * (zoom || 1);
    var x = (vw - IW * sc) * posX;
    var y = (vh - IH * sc) * posY;
    return "translate(" + x + "px," + y + "px) scale(" + sc + ")";
  }

  function heroFrame(vw, vh) {
    return coverFrame(HERO_POS.x, HERO_POS.y, 1, vw, vh);
  }

  function frameFor(s, vw, vh, zMul) {
    zMul = zMul || 1;
    var z = s.z[phone() ? 1 : 0] * zMul;
    var sc = Math.max(vw / IW, vh / IH) * z;
    var fo = s.focus ? s.focus[phone() ? 1 : 0] : FOCUS;
    var x = vw / 2 - s.fx * IW * sc;
    var y = vh * fo - s.fy * IH * sc;
    x = Math.min(0, Math.max(vw - IW * sc, x));
    y = Math.min(0, Math.max(vh - IH * sc, y));
    sticky.style.setProperty("--focusX", ((x + s.fx * IW * sc) / vw) * 100 + "%");
    sticky.style.setProperty("--focusY", ((y + s.fy * IH * sc) / vh) * 100 + "%");
    return "translate(" + x + "px," + y + "px) scale(" + sc + ")";
  }

  function midFrame(a, b, vw, vh) {
    return frameFor({ fx: (a.fx + b.fx) / 2, fy: (a.fy + b.fy) / 2, z: [1.15, 1.22] }, vw, vh);
  }

  function now() {
    return getComputedStyle(land).transform;
  }

  function animateTo(frames, dur) {
    if (cur) {
      if (cur.commitStyles) cur.commitStyles();
      cur.cancel();
      cur = null;
    }
    var end = frames[frames.length - 1].transform;
    if (reduce || !land.animate) {
      land.style.transform = end;
      return Promise.resolve();
    }
    var a = (cur = land.animate(frames, {
      duration: dur,
      easing: "cubic-bezier(.65,0,.35,1)",
      fill: "forwards",
    }));
    return a.finished
      .then(function () {
        land.style.transform = end;
        a.cancel();
        if (cur === a) cur = null;
      })
      .catch(function () {});
  }

  function sampleBlank(ctx) {
    try {
      var c = ctx.getImageData(IW / 2, IH / 2, 1, 1).data;
      return c[0] + c[1] + c[2] < 8;
    } catch (e) {
      return true;
    }
  }

  function drawFallback() {
    return new Promise(function (resolve) {
      var img = new Image();
      img.onload = function () {
        var ctx = plate.getContext("2d", { alpha: false });
        plate.width = IW;
        plate.height = IH;
        ctx.drawImage(img, 0, 0, IW, IH);
        captureOk = false;
        resolve(false);
      };
      img.onerror = function () {
        resolve(false);
      };
      img.src = cfg.fallbackUrl || plate.getAttribute("data-fallback") || "";
    });
  }

  function captureFrame() {
    var ctx = plate.getContext("2d", { alpha: false, willReadFrequently: true });
    plate.width = IW;
    plate.height = IH;
    try {
      ctx.drawImage(video, 0, 0, IW, IH);
      if (sampleBlank(ctx)) {
        return drawFallback();
      }
      captureOk = true;
      capturedTime = video.currentTime || 0;
      pickReady = false;
      return Promise.resolve(true);
    } catch (e) {
      return drawFallback();
    }
  }

  function moveDot(ms) {
    var d = $("ps-dot");
    var b = document.querySelector('.ps-stop[aria-pressed="true"]');
    if (!d || !b || !trail) return;
    var jump = !ms || reduce;
    d.classList.toggle("jump", jump);
    if (!jump) d.style.transitionDuration = ms + "ms";
    /* Geometric center of the stop relative to the trail — avoids subpixel drift vs getBoundingClientRect. */
    var trailBox = trail.getBoundingClientRect();
    var stopBox = b.getBoundingClientRect();
    d.style.left = stopBox.left - trailBox.left + stopBox.width / 2 + "px";
    if (jump) {
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          d.classList.remove("jump");
        });
      });
    }
  }

  function press(m) {
    document.querySelectorAll(".ps-stop").forEach(function (b) {
      b.setAttribute("aria-pressed", b.dataset.m === m ? "true" : "false");
    });
  }

  function setAway(on) {
    shelf.classList.toggle("ps-journey-away", on);
    if (themes) themes.classList.toggle("ps-journey-away", on || !!q);
  }

  function mediumOfFormat(format) {
    if (format === "essay") return "writing";
    if (format === "music") return "music";
    return "art";
  }

  /*
   * Owner-approved P.S. browsing categories (journey UI only).
   * Separate from historical thematic tags in ps-data.js / room-tags.js.
   * Piece→category assignments are not guessed here — only explicit
   * WordPress / #ps-journey-data mappings apply. Uncategorized works
   * remain visible under All.
   */
  var JOURNEY_CATEGORIES = {
    writing: ["Discoveries", "Experiences", "Creations", "Reflections"],
    music: ["Holiday", "Inspired by TMR"],
    art: ["Paintings", "Digital Art", "Photography"],
  };

  function pathKey(href) {
    try {
      var u = new URL(href, location.origin);
      var p = u.pathname || "/";
      if (p.length > 1 && p.charAt(p.length - 1) === "/") p = p.slice(0, -1);
      return p.toLowerCase();
    } catch (e) {
      return String(href || "")
        .replace(/[?#].*$/, "")
        .replace(/\/$/, "")
        .toLowerCase();
    }
  }

  function journeyNodes() {
    var list = [];
    document.querySelectorAll("#ps-journey-data .ps-journey-work").forEach(function (n) {
      list.push({
        id: n.getAttribute("data-id") || "",
        m: n.getAttribute("data-medium"),
        t: n.getAttribute("data-title"),
        href: n.getAttribute("data-href") || "#",
        summary: n.getAttribute("data-summary") || "",
        sub: n.getAttribute("data-sub") || "",
        categories: (n.getAttribute("data-categories") || "")
          .split(",")
          .map(function (t) {
            return t.trim();
          })
          .filter(Boolean),
      });
    });
    return list;
  }

  function works() {
    /*
     * Phase 2: prefer WordPress-rendered #ps-journey-data (published Pages).
     * Fall back to public PS_WORKS entries merged with journey category assignments.
     */
    var fromWp = journeyNodes().filter(function (w) {
      return w.m && w.t && w.href && w.href !== "#";
    });
    if (fromWp.length) {
      return fromWp;
    }

    var byPath = {};
    journeyNodes().forEach(function (w) {
      if (w.href) byPath[pathKey(w.href)] = w;
      if (w.t) byPath["title:" + w.t] = w;
      if (w.id) byPath["id:" + w.id] = w;
    });

    if (window.PS_WORKS && window.PS_WORKS.length) {
      return window.PS_WORKS.filter(function (w) {
        return w && w.access === "public" && w.href;
      }).map(function (w) {
        var extra =
          byPath[pathKey(w.href)] ||
          byPath["title:" + w.title] ||
          byPath["id:" + w.id] ||
          {};
        return {
          m: mediumOfFormat(w.format),
          t: w.title,
          href: w.href,
          summary: extra.summary || "",
          sub: extra.sub || "",
          categories: (extra.categories || []).slice(),
          id: w.id,
        };
      });
    }

    return [];
  }

  function categoriesFor(m) {
    return ["All"].concat(JOURNEY_CATEGORIES[m] || []);
  }

  function drawTags() {
    if (!themes) return;
    themes.innerHTML = medium
      ? categoriesFor(medium)
          .map(function (t) {
            return (
              '<button type="button" class="ps-journey-theme" aria-pressed="' +
              (t === tag) +
              '" data-t="' +
              t +
              '">' +
              t +
              "</button>"
            );
          })
          .join("")
      : "";
  }

  function cardHtml(w, i) {
    var st = STOPS[w.m];
    var tilt = [-1.4, 1, -0.6, 1.3, -1.1, 0.7][i % 6];
    var label = q ? '<span class="ps-journey-kind">' + st.kind + "</span>" : "";
    var go =
      w.m === "writing" ? "Read" : w.m === "music" ? "Listen" : "View";
    var sum = w.summary
      ? '<p class="sum">' + w.summary + "</p>"
      : w.sub
        ? "<p>" + w.sub + "</p>"
        : "";
    return (
      '<article class="ps-journey-card" style="--tilt:' +
      tilt +
      "deg;--seal:" +
      st.seal +
      '">' +
      label +
      "<h3>" +
      w.t +
      "</h3>" +
      sum +
      '<a class="ps-journey-go" href="' +
      w.href +
      '">' +
      go +
      "</a></article>"
    );
  }

  function fill() {
    var list = works().filter(function (w) {
      if (q) {
        return (w.t + " " + w.summary + " " + w.sub + " " + (w.categories || []).join(" "))
          .toLowerCase()
          .includes(q);
      }
      return (
        w.m === medium &&
        (tag === "All" || (w.categories || []).indexOf(tag) >= 0)
      );
    });
    shelf.innerHTML = list.length
      ? list.map(cardHtml).join("")
      : '<p class="ps-journey-empty">Nothing here yet.</p>';
    shelf.scrollLeft = 0;
    requestAnimationFrame(function () {
      body.style.setProperty("--shelfH", shelf.offsetHeight + "px");
    });
  }

  function setHash(v) {
    try {
      history.replaceState(null, "", v ? "#" + v : location.pathname + location.search);
    } catch (e) {}
  }

  function slug(t) {
    return String(t)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "");
  }

  function putAway() {
    if (sw) {
      sw.classList.remove("show", "leaving");
    }
    clearTimeout(stripTimer);
  }

  async function goTo(m, opts) {
    opts = opts || {};
    if (m === "home") {
      backToStart();
      return;
    }
    if (m === medium && body.classList.contains("at-stop")) return;

    var token = ++busy;
    var fromHero = !body.classList.contains("at-stop");
    var from = medium ? STOPS[medium] : null;
    var to = STOPS[m];

    /* Unlock audio BEFORE any await — must remain inside the Music trail gesture. */
    if (m === "music") warmAudio();

    if (fromHero) {
      await captureFrame();
      if (token !== busy) return;
      try {
        video.pause();
      } catch (e) {}
      land.style.transform = heroFrame(sticky.clientWidth, sticky.clientHeight || REST);
      scene.hidden = false;
      hero.classList.add("is-journey-stage");
      body.classList.add("is-ps-journey-open");
    } else {
      hero.classList.add("is-journey-stage");
      body.classList.add("is-ps-journey-open");
    }

    medium = m;
    tag = opts.tag && categoriesFor(m).indexOf(opts.tag) >= 0 ? opts.tag : "All";
    press(m);
    body.style.setProperty("--seal", to.seal);
    body.classList.add("at-stop");
    body.classList.remove("arrived");
    putAway();
    setStory(m);
    setAway(true);

    var dur = opts.instant ? 0 : fromHero ? T.enter : T.stop;
    moveDot(dur);
    var vw = sticky.clientWidth;
    var vh = openH();
    setStageH(vh, dur * 0.8);

    /* Track is collapsed under .is-journey-stage; bar sits directly under the stage. */
    var bar = document.querySelector(".ps-bar");
    function settleTrail(behavior) {
      if (!bar) return;
      var br = bar.getBoundingClientRect();
      var vhNow = viewH();
      if (br.bottom > vhNow + 1) {
        scrollBy({ top: br.bottom - vhNow + 2, behavior: behavior });
      } else if (br.top < 0) {
        scrollBy({ top: br.top - 2, behavior: behavior });
      }
    }
    var behavior = reduce || opts.instant ? "auto" : "smooth";
    var top = sticky.getBoundingClientRect().top + scrollY;
    var barH = bar ? bar.offsetHeight : 0;
    var need = top + vh + barH - viewH();
    if (need > scrollY + 2) {
      scrollTo({ top: need, behavior: behavior });
    } else if (scrollY > top + 2) {
      scrollTo({ top: Math.max(0, top), behavior: behavior });
    }
    requestAnimationFrame(function () {
      settleTrail(behavior);
      requestAnimationFrame(function () {
        settleTrail("auto");
        moveDot(0);
      });
    });

    var end = frameFor(to, vw, vh);
    var trip;
    if (opts.instant) {
      land.style.transform = end;
      trip = Promise.resolve();
    } else if (fromHero) {
      trip = animateTo([{ transform: now() }, { transform: end }], T.enter);
    } else {
      trip = animateTo(
        [{ transform: now() }, { transform: midFrame(from, to, vw, vh) }, { transform: end }],
        T.stop
      );
    }
    frameFor(to, vw, vh);
    drawTags();
    fill();
    if (m === "writing") {
      alignWritingFeather(vw, vh);
    } else {
      resetFeatherLanding();
    }
    setHash(m + (tag !== "All" ? "/" + slug(tag) : ""));
    setTimeout(function () {
      if (token === busy) setAway(false);
    }, opts.instant ? 0 : dur * 0.62);
    await trip;
    if (token === busy) {
      body.classList.add("arrived");
      if (m === "art") unveil();
      /* Land pose is final — re-align once so shelf/theme boxes are settled. */
      if (m === "writing") alignWritingFeather(vw, sticky.clientHeight || vh);
    }
  }

  function restoreVideo() {
    return new Promise(function (resolve) {
      var done = false;
      function finish() {
        if (done) return;
        done = true;
        scene.hidden = true;
        body.classList.remove("is-ps-journey-open");
        hero.classList.remove("is-journey-stage");
        if (!reduce) {
          video.play().catch(function () {});
        }
        resolve();
      }
      function onSeeked() {
        video.removeEventListener("seeked", onSeeked);
        finish();
      }
      try {
        var target = Math.min(
          Math.max(capturedTime, 0.001),
          Math.max((video.duration || 2.7) - 0.05, 0.05)
        );
        if (Math.abs(video.currentTime - target) > 0.03) {
          video.addEventListener("seeked", onSeeked);
          video.currentTime = target;
          setTimeout(finish, 450);
        } else {
          finish();
        }
      } catch (e) {
        finish();
      }
    });
  }

  async function backToStart() {
    if (!body.classList.contains("at-stop")) return;
    var token = ++busy;
    putAway();
    medium = null;
    press("home");
    body.style.removeProperty("--seal");
    resetFeatherLanding();
    moveDot(T.back);
    body.classList.remove("at-stop", "arrived");
    setStory(null);
    setHash("");
    setAway(true);
    setStageH(REST, T.back);
    var vw = sticky.clientWidth;
    await animateTo([{ transform: now() }, { transform: heroFrame(vw, REST) }], T.back);
    if (token !== busy) return;
    sticky.style.height = "";
    sticky.style.removeProperty("--ps-stage-h");
    sticky.style.removeProperty("--ps-stage-grow");
    await restoreVideo();
    scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
  }

  /*
   * Chandelier audio
   * Prior failure: warmAudio() ran AFTER await captureFrame(), so iPhone Safari
   * had already left the user-gesture window (iPad is more lenient). Unlock and
   * HTMLAudio .play() must run synchronously inside the Music / crystal gesture.
   */
  var htmlNotes = null;
  var audioDbg =
    /(?:\?|&)psDebug=1(?:&|$)/.test(location.search) ||
    sessionStorage.getItem("PS_JOURNEY_DEBUG") === "1";

  function dbg() {
    if (!audioDbg || !window.console) return;
    var args = ["[ps-journey-audio]"].concat([].slice.call(arguments));
    console.log.apply(console, args);
  }

  function encodeWavDataUri(freq, seconds) {
    var rate = 22050;
    var n = Math.max(1, Math.floor(rate * seconds));
    var data = new Int16Array(n);
    for (var i = 0; i < n; i++) {
      var t = i / rate;
      var env = Math.exp(-2.8 * t);
      var sample =
        Math.sin(2 * Math.PI * freq * t) * 0.55 * env +
        Math.sin(2 * Math.PI * freq * 2.32 * t) * 0.18 * env +
        Math.sin(2 * Math.PI * freq * 4.25 * t) * 0.08 * env;
      var v = Math.max(-1, Math.min(1, sample));
      data[i] = (v * 0.85 * 0x7fff) | 0;
    }
    var bytes = data.length * 2;
    var buf = new ArrayBuffer(44 + bytes);
    var view = new DataView(buf);
    function str(at, s) {
      for (var i = 0; i < s.length; i++) view.setUint8(at + i, s.charCodeAt(i));
    }
    str(0, "RIFF");
    view.setUint32(4, 36 + bytes, true);
    str(8, "WAVE");
    str(12, "fmt ");
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, 1, true);
    view.setUint32(24, rate, true);
    view.setUint32(28, rate * 2, true);
    view.setUint16(32, 2, true);
    view.setUint16(34, 16, true);
    str(36, "data");
    view.setUint32(40, bytes, true);
    for (var j = 0; j < data.length; j++) view.setInt16(44 + j * 2, data[j], true);
    var bin = "";
    var u8 = new Uint8Array(buf);
    for (var k = 0; k < u8.length; k++) bin += String.fromCharCode(u8[k]);
    return "data:audio/wav;base64," + btoa(bin);
  }

  function ensureHtmlNotes() {
    if (htmlNotes) return htmlNotes;
    htmlNotes = NOTES.map(function (f) {
      var a = new Audio(encodeWavDataUri(f, 1.4));
      a.preload = "auto";
      a.setAttribute("playsinline", "");
      a.playsInline = true;
      return a;
    });
    return htmlNotes;
  }

  function audio() {
    if (AC) return AC;
    var C = window.AudioContext || window.webkitAudioContext;
    if (!C) return null;
    AC = new C();
    var comp = AC.createDynamicsCompressor();
    comp.connect(AC.destination);
    OUT = AC.createGain();
    OUT.gain.value = 0.55;
    OUT.connect(comp);
    var len = AC.sampleRate * 2.4;
    var ir = AC.createBuffer(2, len, AC.sampleRate);
    for (var c = 0; c < 2; c++) {
      var d = ir.getChannelData(c);
      for (var i = 0; i < len; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 3.2);
    }
    var rv = AC.createConvolver();
    rv.buffer = ir;
    var wet = AC.createGain();
    wet.gain.value = 0.32;
    OUT.connect(rv);
    rv.connect(wet);
    wet.connect(comp);
    dbg("AudioContext created", AC.state, "sampleRate", AC.sampleRate);
    return AC;
  }

  function warmAudio() {
    /*
     * Silent unlock only — must stay synchronous inside the Music/crystal gesture.
     * No audible HTML chime on Music entry. Crystal taps call playHtmlNote separately.
     */
    try {
      ensureHtmlNotes();
    } catch (e) {
      dbg("ensureHtmlNotes error", String(e && e.message || e));
    }

    var ac = audio();
    if (!ac) {
      dbg("no AudioContext");
      return;
    }
    try {
      if (ac.state === "suspended") {
        var rp = ac.resume();
        if (rp && typeof rp.then === "function") {
          rp.then(function () {
            dbg("AudioContext resumed", ac.state);
          }).catch(function (err) {
            dbg("AudioContext resume failed", String(err && err.message || err));
          });
        }
      }
      /* Zero-sample buffer: unlocks output without an audible tone. */
      var buf = ac.createBuffer(1, 1, ac.sampleRate);
      var src = ac.createBufferSource();
      src.buffer = buf;
      src.connect(ac.destination);
      src.start(0);
      dbg("silent unlock buffer", ac.state);
    } catch (e) {
      dbg("warmAudio web audio error", String(e && e.message || e));
    }
  }

  function playHtmlNote(index) {
    try {
      var notes = ensureHtmlNotes();
      var a = notes[index];
      if (!a) return;
      a.pause();
      a.currentTime = 0;
      var p = a.play();
      if (p && typeof p.then === "function") {
        p.then(function () {
          dbg("html note", index, "playing");
        }).catch(function (err) {
          dbg("html note", index, "failed", String(err && err.message || err));
        });
      }
    } catch (e) {
      dbg("html note error", String(e && e.message || e));
    }
  }

  function playChime(ac, f) {
    if (!ac || !OUT) return;
    var t = ac.currentTime;
    [
      [1, 0.5, 2.6],
      [2.32, 0.17, 1.3],
      [4.25, 0.08, 0.7],
      [6.63, 0.035, 0.4],
    ].forEach(function (row) {
      var o = ac.createOscillator();
      var v = ac.createGain();
      o.type = "sine";
      o.frequency.value = f * row[0];
      v.gain.setValueAtTime(0, t);
      v.gain.linearRampToValueAtTime(row[1], t + 0.004);
      v.gain.exponentialRampToValueAtTime(0.0001, t + row[2]);
      o.connect(v);
      v.connect(OUT);
      o.start(t);
      o.stop(t + row[2] + 0.05);
    });
    dbg("oscillators started", f, "ctx", ac.state);
  }

  function chime(f, index) {
    /* HTMLAudio first — still inside the tap gesture even if Web Audio is suspended. */
    if (typeof index === "number") playHtmlNote(index);
    var ac = audio();
    if (!ac) return;
    if (ac.state === "suspended") {
      try {
        ac.resume();
      } catch (e) {}
    }
    try {
      playChime(ac, f);
    } catch (e) {
      dbg("playChime error", String(e && e.message || e));
    }
  }

  function strike(b) {
    if (!b || !body.classList.contains("m-music")) return;
    lastStrikeAt = Date.now();
    var n = +b.dataset.n;
    dbg("strike", n, "ac", AC && AC.state);
    warmAudio();
    chime(NOTES[n], n);
    b.classList.remove("hit");
    void b.offsetWidth;
    b.classList.add("hit");
    var r = document.createElement("i");
    r.className = "ps-ring";
    r.style.left = b.style.left;
    r.style.top = b.style.top;
    swing.appendChild(r);
    setTimeout(function () {
      r.remove();
    }, 1600);
    if (!reduce && chand && chand.animate) {
      chand.animate(
        [
          { transform: "rotate(0)" },
          { transform: "rotate(" + (n < 3 ? -0.8 : 0.8) + "deg)" },
          { transform: "rotate(" + (n < 3 ? 0.4 : -0.4) + "deg)" },
          { transform: "rotate(0)" },
        ],
        { duration: 900, easing: "ease-out" }
      );
    }
    if (hint) hint.classList.add("done");
  }

  /* Paint */
  function readyPick() {
    if (pickReady) return;
    pickCanvas.width = IW;
    pickCanvas.height = IH;
    pickCtx.drawImage(plate, 0, 0, IW, IH);
    pickReady = true;
  }

  function sample(x, y) {
    readyPick();
    var d = pickCtx.getImageData(Math.max(0, x - 3), Math.max(0, y - 3), 7, 7).data;
    var r = 0,
      g = 0,
      b = 0,
      n = 0;
    for (var i = 0; i < d.length; i += 4) {
      r += d[i];
      g += d[i + 1];
      b += d[i + 2];
      n++;
    }
    return [Math.round(r / n), Math.round(g / n), Math.round(b / n)];
  }

  function hsl(rgb) {
    var r = rgb[0] / 255,
      g = rgb[1] / 255,
      b = rgb[2] / 255;
    var M = Math.max(r, g, b),
      m = Math.min(r, g, b),
      l = (M + m) / 2,
      d = M - m;
    var h = 0,
      s = 0;
    if (d) {
      s = d / (1 - Math.abs(2 * l - 1));
      h = M === r ? ((g - b) / d) % 6 : M === g ? (b - r) / d + 2 : (r - g) / d + 4;
      h = (h * 60 + 360) % 360;
    }
    return [h, s, l];
  }

  function source(h) {
    var hue = h[0],
      s = h[1],
      l = h[2];
    if (s < 0.14) return l < 0.3 ? "Shadow" : l > 0.75 ? "Mist" : "Branch";
    if (l < 0.3) return "Shadow";
    if (hue < 45 && s < 0.6 && l < 0.4) return "Branch";
    if (hue >= 62 && hue < 170) return "Leaf";
    if (hue >= 170 && hue < 250) return "Sky";
    if (hue >= 40 && hue < 62) return "Peony heart";
    return "Petal";
  }

  function rgbFromHsl(h, s, l) {
    function k(n) {
      return (n + h / 30) % 12;
    }
    var a = s * Math.min(l, 1 - l);
    function f(n) {
      return l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
    }
    return [f(0), f(8), f(4)].map(function (v) {
      return Math.round(v * 255);
    });
  }

  function hex(c) {
    return (
      "#" +
      c
        .map(function (v) {
          return v.toString(16).padStart(2, "0");
        })
        .join("")
        .toUpperCase()
    );
  }

  function placeSwatches() {
    if (!sw || !themes) return;
    var s = sticky.getBoundingClientRect();
    var avail = themes.getBoundingClientRect().top - s.top - 14;
    var W = phone() ? 128 : 168;
    var chipH = Math.max(36, Math.min(60, (avail - 50) / 4 - 3));
    sw.style.setProperty("--sw", W + "px");
    sw.style.setProperty("--chipH", chipH + "px");
    sw.style.left = s.width - W - (phone() ? 14 : Math.max(28, s.width * 0.05)) + "px";
    sw.style.top = "12px";
  }

  function strip(c) {
    var h = hsl(c);
    var top = Math.min(0.88, Math.max(h[2] + 0.18, 0.58));
    var ls = [top, top - 0.14, top - 0.28, top - 0.42].map(function (v) {
      return Math.max(0.12, v);
    });
    var k = 0,
      best = 9;
    ls.forEach(function (v, i) {
      if (Math.abs(v - h[2]) < best) {
        best = Math.abs(v - h[2]);
        k = i;
      }
    });
    var fam = h[1] < 0.14 ? GREYS : NAMES.find(function (row) {
      return h[0] < row[0];
    })[1];
    var off = top > 0.8 ? 0 : 1;
    return ls.map(function (v, i) {
      var col = i === k ? c : rgbFromHsl(h[0], Math.max(h[1], 0.3) * (h[1] < 0.14 ? 0.2 : 1), v);
      return { col: col, name: fam[Math.min(4, i + off)], yours: i === k, light: hsl(col)[2] > 0.62 };
    });
  }

  function showStrip(c, from) {
    if (!sw || !chips) return;
    placeSwatches();
    clearTimeout(stripTimer);
    var s = sticky.getBoundingClientRect();
    var wasShown = sw.classList.contains("show");
    var tx = parseFloat(sw.style.left) + sw.offsetWidth / 2;
    var ty = 40;
    var fx = from.x - s.left;
    var fy = from.y - s.top;
    var drop = document.createElement("i");
    drop.className = "ps-lift";
    drop.style.background = "rgb(" + c.join(",") + ")";
    drop.style.left = fx + "px";
    drop.style.top = fy + "px";
    sticky.appendChild(drop);
    var dur = reduce ? 0 : 900;
    if (drop.animate) {
      drop.animate(
        [
          { transform: "scale(.3)", opacity: 0 },
          { transform: "scale(1.1)", opacity: 1, offset: 0.15 },
          {
            transform: "translate(" + (tx - fx) * 0.45 + "px," + ((ty - fy) * 0.45 - 40) + "px) scale(1)",
            opacity: 1,
            offset: 0.55,
          },
          { transform: "translate(" + (tx - fx) + "px," + (ty - fy) + "px) scale(.6)", opacity: 0 },
        ],
        { duration: dur, easing: "cubic-bezier(.45,0,.25,1)", fill: "forwards" }
      );
    }
    setTimeout(function () {
      drop.remove();
    }, dur + 50);
    if (wasShown) {
      sw.classList.add("leaving");
      sw.classList.remove("show");
    }
    stripTimer = setTimeout(function () {
      chips.innerHTML = strip(c)
        .map(function (x, i) {
          return (
            '<div class="ps-chip' +
            (x.yours ? " yours" : "") +
            '" style="--i:' +
            i +
            ";--c:rgb(" +
            x.col.join(",") +
            ");--t:" +
            (x.light ? "#2a211b" : "#fff") +
            '">' +
            x.name +
            "<small>" +
            hex(x.col) +
            "</small></div>"
          );
        })
        .join("");
      var srcEl = $("ps-paint-source");
      if (srcEl) srcEl.textContent = source(hsl(c));
      sw.classList.remove("leaving");
      void sw.offsetWidth;
      sw.classList.add("show");
    }, reduce ? 0 : dur * 0.6);
  }

  function unveil() {
    readyPick();
    if (sw) sw.classList.remove("show", "leaving");
    var wr = land.getBoundingClientRect();
    var k = wr.width / IW;
    var P = [Math.round(0.615 * IW), Math.round(0.662 * IH)];
    showStrip(sample(P[0], P[1]), { x: wr.left + P[0] * k, y: wr.top + P[1] * k });
  }

  function setQ(v) {
    q = v.trim().toLowerCase();
    if (searchForm) searchForm.classList.toggle("has", !!v);
    body.classList.toggle("searching-journey", !!q);
    if (q) {
      if (!body.classList.contains("at-stop")) {
        /* search-from-hero grows stage without medium */
        captureFrame().then(function () {
          try {
            video.pause();
          } catch (e) {}
          scene.hidden = false;
          hero.classList.add("is-journey-stage");
          body.classList.add("is-ps-journey-open", "at-stop");
          setStageH(openH(), 500);
          land.style.transform = heroFrame(sticky.clientWidth, sticky.clientHeight);
          fill();
          setAway(false);
        });
      } else {
        fill();
        setAway(false);
      }
    } else if (body.classList.contains("at-stop") && medium) {
      fill();
      setAway(false);
    } else if (!medium) {
      setAway(true);
      backToStart();
    }
  }

  function reframe() {
    if (cur) return;
    REST = restH();
    if (body.classList.contains("at-stop") || q) {
      setStageH(openH(), 0);
    }
    var vw = sticky.clientWidth;
    var vh = sticky.clientHeight;
    land.style.transform =
      medium && body.classList.contains("at-stop")
        ? frameFor(STOPS[medium], vw, vh)
        : heroFrame(vw, vh);
    moveDot(0);
  }

  /* Events */
  trail.addEventListener("click", function (e) {
    var b = e.target.closest(".ps-stop");
    if (!b) return;
    /* Gesture-sync unlock before goTo's async captureFrame yields. */
    if (b.dataset.m === "music") warmAudio();
    goTo(b.dataset.m);
  });

  addEventListener("keydown", function (e) {
    if (e.key === "Escape" && body.classList.contains("at-stop") && !q) backToStart();
  });

  if (themes) {
    themes.addEventListener("click", function (e) {
      var b = e.target.closest(".ps-journey-theme");
      if (!b) return;
      tag = b.dataset.t;
      drawTags();
      fill();
      setHash(medium + (tag !== "All" ? "/" + slug(tag) : ""));
    });
  }

  if (qInput) {
    qInput.addEventListener("input", function (e) {
      setQ(e.target.value);
    });
  }
  if (sclose) {
    sclose.addEventListener("click", function () {
      if (qInput) qInput.value = "";
      setQ("");
      if (qInput) qInput.focus();
    });
  }

  if (swing) {
    function xtalFromEvent(e) {
      var t = e.target;
      if (t && t.closest) {
        var direct = t.closest(".ps-xtal");
        if (direct) return direct;
      }
      var x = e.clientX;
      var y = e.clientY;
      if ((x == null || y == null) && e.changedTouches && e.changedTouches[0]) {
        x = e.changedTouches[0].clientX;
        y = e.changedTouches[0].clientY;
      }
      if (x == null || y == null) return null;
      var el = document.elementFromPoint(x, y);
      return el && el.closest ? el.closest(".ps-xtal") : null;
    }

    /* Do not preventDefault on touch — that voids iPhone Safari audio unlock. */
    swing.addEventListener("pointerdown", function (e) {
      var b = xtalFromEvent(e);
      if (!b) return;
      if (e.pointerType === "mouse") e.preventDefault();
      strumming = true;
      lastXtal = b;
      strike(b);
    });
    /* touchend is a qualifying activation event on iPhone Safari. */
    swing.addEventListener(
      "touchend",
      function (e) {
        var b = xtalFromEvent(e);
        if (!b) return;
        if (Date.now() - lastStrikeAt < 450) return;
        strike(b);
      },
      { passive: true }
    );
    swing.addEventListener("click", function (e) {
      var b = xtalFromEvent(e);
      if (!b) return;
      if (Date.now() - lastStrikeAt < 450) return;
      strike(b);
    });
    addEventListener("pointermove", function (e) {
      if (!strumming) return;
      var b = xtalFromEvent(e);
      if (b && b !== lastXtal) {
        lastXtal = b;
        strike(b);
      }
    });
    addEventListener("pointerup", function () {
      strumming = false;
      lastXtal = null;
    });
    addEventListener("pointercancel", function () {
      strumming = false;
      lastXtal = null;
    });
    swing.addEventListener("keydown", function (e) {
      var b = e.target.closest(".ps-xtal");
      if (b && (e.key === "Enter" || e.key === " ")) {
        e.preventDefault();
        strike(b);
      }
    });
  }

  sticky.addEventListener("click", function (e) {
    if (!body.classList.contains("m-art") || !body.classList.contains("arrived")) return;
    if (e.target.closest(".ps-journey-shelf, .ps-journey-themes, .ps-swatches, .ps-journey-card, .ps-bar")) {
      return;
    }
    var wr = land.getBoundingClientRect();
    var k = wr.width / IW;
    var x = Math.round((e.clientX - wr.left) / k);
    var y = Math.round((e.clientY - wr.top) / k);
    if (x < 0 || y < 0 || x >= IW || y >= IH) return;
    if (hint2) hint2.classList.add("done");
    showStrip(sample(x, y), { x: e.clientX, y: e.clientY });
  });

  addEventListener("resize", reframe);

  /* Start */
  REST = restH();
  land.style.transform = heroFrame(sticky.clientWidth, REST);
  moveDot(0);
  scene.hidden = true;

  var hash = decodeURIComponent(location.hash.slice(1)).split("/");
  if (STOPS[hash[0]]) {
    var t = hash[1] ? categoriesFor(hash[0]).find(function (x) {
      return slug(x) === hash[1];
    }) : null;
    goTo(hash[0], { instant: true, tag: t || "All" });
  }
})();
