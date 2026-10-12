/**
 * Remembered social post — Like and Save.
 *
 * Totals are global and cumulative. Each reaction can succeed once per page
 * visit; later clicks during that visit do nothing, and a failed attempt can be
 * retried. There is no Unlike or Unsave and no stored visitor state. Without the
 * WordPress REST configuration (static prototype), counts are in-memory only.
 */
document.addEventListener("DOMContentLoaded", () => {
  const OBSOLETE_STORAGE_KEYS = [
    "pyf-social-metrics",
    "pyf-guestbook-entries",
    "pyf-remembered-social",
  ];

  try {
    OBSOLETE_STORAGE_KEYS.forEach((key) => localStorage.removeItem(key));
  } catch {
    /* localStorage unavailable */
  }

  const scene = document.querySelector("#scene-02");
  if (!scene) {
    return;
  }

  const likeButton = scene.querySelector(".social-chrome--like");
  const saveButton = scene.querySelector(".social-chrome--save");
  const likesCount = scene.querySelector(".engagement-count--likes");
  const savesCount = scene.querySelector(".engagement-count--saves");

  if (!likeButton || !saveButton || !likesCount || !savesCount) {
    return;
  }

  const config = window.NoelClarkSocial;
  const totalsUrl =
    config && typeof config.totalsUrl === "string" ? config.totalsUrl : "";
  const reactionUrl =
    config && typeof config.reactionUrl === "string" ? config.reactionUrl : "";
  const persistent = totalsUrl !== "" && reactionUrl !== "";

  function parseCount(element) {
    const value = Number.parseInt(element.textContent.replace(/[^0-9]/g, ""), 10);
    return Number.isSafeInteger(value) ? value : 0;
  }

  const counters = {
    like: {
      button: likeButton,
      display: likesCount,
      confirmed: parseCount(likesCount),
      inFlight: 0,
      succeeded: false,
    },
    save: {
      button: saveButton,
      display: savesCount,
      confirmed: parseCount(savesCount),
      inFlight: 0,
      succeeded: false,
    },
  };

  function isChromeActive(element) {
    return !element.classList.contains("social-chrome--hidden");
  }

  function formatCount(value) {
    return value.toLocaleString("en-US");
  }

  function render(counter) {
    counter.display.textContent = formatCount(counter.confirmed + counter.inFlight);
  }

  function isTotal(value) {
    return Number.isSafeInteger(value) && value >= 0;
  }

  function acceptLiveTotal(counter, total) {
    if (!isTotal(total)) {
      return;
    }

    // In-flight clicks may already be counted on the server; never step backwards then.
    counter.confirmed =
      counter.inFlight === 0 ? total : Math.max(counter.confirmed, total);
  }

  function refreshTotals() {
    if (!persistent) {
      return;
    }

    const url = new URL(totalsUrl, window.location.href);
    url.searchParams.set("_", String(Date.now()));

    fetch(url.toString(), {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: { Accept: "application/json" },
    })
      .then((response) =>
        response.ok ? response.json() : Promise.reject(new Error("totals unavailable")),
      )
      .then((data) => {
        if (!data) {
          return;
        }

        acceptLiveTotal(counters.like, data.likes);
        acceptLiveTotal(counters.save, data.saves);
        render(counters.like);
        render(counters.save);
      })
      .catch(() => {
        /* Keep the server-rendered totals. */
      });
  }

  function sendReaction(reaction) {
    return fetch(reactionUrl, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",
      keepalive: true,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ reaction }),
    })
      .then((response) =>
        response.ok ? response.json() : Promise.reject(new Error("reaction failed")),
      )
      .then((data) => {
        if (!data || data.reaction !== reaction || !isTotal(data.total)) {
          throw new Error("reaction failed");
        }

        return data.total;
      });
  }

  function react(reaction) {
    const counter = counters[reaction];

    if (counter.succeeded || counter.inFlight > 0 || !isChromeActive(counter.button)) {
      return;
    }

    counter.button.classList.add("social-action--active");

    if (!persistent) {
      counter.confirmed += 1;
      counter.succeeded = true;
      render(counter);
      return;
    }

    counter.inFlight += 1;
    render(counter);

    sendReaction(reaction)
      .then((total) => {
        counter.succeeded = true;
        counter.confirmed = Math.max(counter.confirmed, total);
      })
      .catch(() => {
        /* Unsuccessful clicks are not counted. */
      })
      .finally(() => {
        counter.inFlight -= 1;

        if (!counter.succeeded && counter.inFlight === 0) {
          counter.button.classList.remove("social-action--active");
        }

        render(counter);
      });
  }

  likeButton.addEventListener("click", () => {
    react("like");
  });

  saveButton.addEventListener("click", () => {
    react("save");
  });

  window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
      refreshTotals();
    }
  });

  refreshTotals();
});
