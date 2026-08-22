/**
 * Homepage Rooms — sequential cinematic chapters.
 * Posters only; blurbs and interaction props arrive with each room's motion design.
 */
(function () {
  const homeJourney = document.querySelector(".home-journey");

  if (!homeJourney) {
    return;
  }

  document.body.dataset.homeRooms = "journey";
})();
