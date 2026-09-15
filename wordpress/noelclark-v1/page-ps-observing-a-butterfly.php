<?php
/**
 * Template Name: P.S. — Observing a Butterfly
 * Observing a Butterfly — approved P.S. work-page fidelity.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();

get_header();
?>

<main class="ps-work" id="ps-work-observing-a-butterfly">
    <nav class="ps-work__return" aria-label="Back to P.S.">
        <a class="ps-work__return-link" href="<?php echo esc_url(home_url('/ps/')); ?>">← BACK TO P.S.</a>
    </nav>

    <div class="ps-work__seal" aria-hidden="true">
        <img
            class="ps-work__seal-mark"
            src="<?php echo esc_url($theme_uri . '/assets/images/black-wax-seal.png'); ?>"
            alt=""
            width="1024"
            height="1024"
        />
    </div>

    <figure class="ps-work__motion">
        <h1 class="visually-hidden">OBSERVING A BUTTERFLY</h1>
        <div class="ps-work__motion-stage">
            <video
                class="ps-work__motion-video ps-work__motion-video--ambient"
                src="<?php echo esc_url($theme_uri . '/assets/video/observing-a-butterfly.MP4'); ?>"
                width="1920"
                height="1080"
                muted
                playsinline
                preload="metadata"
                tabindex="-1"
                aria-hidden="true"
            ></video>
            <div class="ps-work__motion-scrim" aria-hidden="true"></div>
            <video
                class="ps-work__motion-video ps-work__motion-video--primary"
                src="<?php echo esc_url($theme_uri . '/assets/video/observing-a-butterfly.MP4'); ?>"
                width="1920"
                height="1080"
                controls
                playsinline
                preload="metadata"
            ></video>
        </div>
    </figure>

    <div
        class="ps-work__notation ps-work__notation--motion"
        data-ps-tags
        data-ps-work-id="ps-work-observing-a-butterfly"
    ></div>
</main>

<script>
      (function () {
        "use strict";

        var stage = document.querySelector(".ps-work__motion-stage");
        var primary = document.querySelector(".ps-work__motion-video--primary");
        var ambient = document.querySelector(".ps-work__motion-video--ambient");

        if (!stage || !primary || !ambient) {
          return;
        }

        function syncAmbientTime() {
          if (Math.abs(ambient.currentTime - primary.currentTime) > 0.05) {
            try {
              ambient.currentTime = primary.currentTime;
            } catch (error) {
              /* seek not ready */
            }
          }
        }

        function mirrorPlayback() {
          if (primary.paused) {
            ambient.pause();
            return;
          }

          syncAmbientTime();
          ambient.play().catch(function () {
            /* ambient follows primary gesture */
          });
        }

        primary.addEventListener("play", mirrorPlayback);
        primary.addEventListener("pause", function () {
          ambient.pause();
        });
        primary.addEventListener("seeked", syncAmbientTime);
        primary.addEventListener("timeupdate", syncAmbientTime);

        primary.addEventListener("loadedmetadata", syncAmbientTime, { once: true });

        stage.addEventListener("click", function (event) {
          if (event.target !== stage) {
            return;
          }

          if (primary.paused) {
            primary.play().catch(function () {
              /* playback blocked */
            });
          } else {
            primary.pause();
          }
        });
      })();
</script>

<?php
get_footer();
