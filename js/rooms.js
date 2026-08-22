/**
 * Homepage Rooms — structural pass.
 * Room content is authored in index.html per ROOM_PRODUCT_ARCHITECTURE.md.
 * Interactions begin after the Portal signature anchors to the first room.
 */
(function () {
  const homeRooms = document.querySelector(".home-rooms");

  if (!homeRooms) {
    return;
  }

  function enableHomeRooms() {
    document.body.dataset.homeRooms = "active";
    homeRooms.classList.add("is-interactive");
  }

  if (document.body.classList.contains("portal-complete")) {
    enableHomeRooms();
    return;
  }

  document.body.dataset.homeRooms = "pending";

  document.addEventListener("portal:stamped", enableHomeRooms, { once: true });
})();
