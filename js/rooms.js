/**
 * Homepage Rooms — structural pass.
 * Room content is authored in index.html per ROOM_PRODUCT_ARCHITECTURE.md.
 * Interactions and motion deferred to a later pass.
 */
(function () {
  const homeRooms = document.querySelector(".home-rooms");

  if (!homeRooms) {
    return;
  }

  document.body.dataset.homeRooms = "structural";
})();
