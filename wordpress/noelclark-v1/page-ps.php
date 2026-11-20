<?php
/**
 * Template Name: P.S.
 * P.S. index — approved frontend fidelity.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

set_query_var('noelclark_nav_current', 'ps');

$theme_uri = get_template_directory_uri();

get_header();
?>

<main id="ps">
    <section class="ps-hero ps-hero--video ps-hero--awaiting-autoplay" aria-label="P.S. living header">
        <div class="ps-hero__sticky">
            <button
                type="button"
                class="ps-hero__activate"
                aria-label="Play hawk motion"
            ></button>
            <div class="ps-hero__media">
                <div class="ps-hero__scene">
                    <video
                        class="ps-hero__video"
                        src="<?php echo esc_url($theme_uri . '/assets/video/ps-heading-hawk-silent.mp4'); ?>"
                        width="1280"
                        height="720"
                        muted
                        autoplay
                        playsinline
                        preload="auto"
                    ></video>

                    <img
                        class="ps-hero__art ps-hero__art--fallback"
                        src="<?php echo esc_url($theme_uri . '/assets/images/ps-header-hawk.png'); ?>"
                        alt=""
                        width="1918"
                        height="820"
                        fetchpriority="high"
                    />

                    <div class="ps-hero__breeze" aria-hidden="true">
                        <div class="ps-hero__strand ps-hero__strand--left-a">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-flowers-left.png'); ?>"
                                alt=""
                                width="2400"
                                height="1350"
                            />
                        </div>
                        <div class="ps-hero__strand ps-hero__strand--left-b">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-flowers-left.png'); ?>"
                                alt=""
                                width="2400"
                                height="1350"
                            />
                        </div>
                        <div class="ps-hero__strand ps-hero__strand--left-c">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-flowers-left.png'); ?>"
                                alt=""
                                width="2400"
                                height="1350"
                            />
                        </div>
                        <div class="ps-hero__strand ps-hero__strand--left-d">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-flowers-left.png'); ?>"
                                alt=""
                                width="2400"
                                height="1350"
                            />
                        </div>
                        <div class="ps-hero__strand ps-hero__strand--left-tendril">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-flowers-left.png'); ?>"
                                alt=""
                                width="2400"
                                height="1350"
                            />
                        </div>
                    </div>

                    <div class="ps-hero__glints" aria-hidden="true">
                        <span class="ps-hero__glint ps-hero__glint--1"></span>
                        <span class="ps-hero__glint ps-hero__glint--2"></span>
                        <span class="ps-hero__glint ps-hero__glint--3"></span>
                        <span class="ps-hero__glint ps-hero__glint--4"></span>
                        <span class="ps-hero__glint ps-hero__glint--5"></span>
                        <span class="ps-hero__glint ps-hero__glint--6"></span>
                        <span class="ps-hero__glint ps-hero__glint--7"></span>
                        <span class="ps-hero__glint ps-hero__glint--8"></span>
                        <span class="ps-hero__glint ps-hero__glint--9"></span>
                    </div>
                </div>
            </div>

            <div class="ps-hero__identity">
                <h1 class="ps-hero__title">PERSONAL SEAL</h1>
                <p class="ps-hero__tagline">Sealed with a P.S.</p>
            </div>

            <!-- Phase 1 journey scene (camera plate + moments). Hidden at rest. -->
            <div class="ps-scene" id="ps-scene" hidden aria-hidden="true">
                <div class="ps-scene__land" id="ps-land">
                    <canvas
                        class="ps-scene__plate"
                        id="ps-plate"
                        width="1280"
                        height="720"
                        data-fallback="<?php echo esc_url($theme_uri . '/assets/images/ps-camera-fallback.jpg'); ?>"
                        aria-hidden="true"
                    ></canvas>

                    <div class="ps-swing" id="ps-swing" aria-hidden="true">
                        <img
                            class="ps-chand"
                            id="ps-chand"
                            src="<?php echo esc_url($theme_uri . '/assets/images/ps-chandelier-cutout.png'); ?>"
                            alt=""
                            width="621"
                            height="567"
                            draggable="false"
                        />
                        <button type="button" class="ps-xtal" data-n="0" style="left:48px;top:340px;--d:0s" aria-label="Crystal chime 1"></button>
                        <button type="button" class="ps-xtal" data-n="1" style="left:92px;top:286px;--d:0.53s" aria-label="Crystal chime 2"></button>
                        <button type="button" class="ps-xtal" data-n="2" style="left:150px;top:318px;--d:1.06s" aria-label="Crystal chime 3"></button>
                        <button type="button" class="ps-xtal" data-n="3" style="left:198px;top:274px;--d:1.59s" aria-label="Crystal chime 4"></button>
                        <button type="button" class="ps-xtal" data-n="4" style="left:250px;top:248px;--d:2.12s" aria-label="Crystal chime 5"></button>
                        <button type="button" class="ps-xtal" data-n="5" style="left:302px;top:210px;--d:2.65s" aria-label="Crystal chime 6"></button>
                        <button type="button" class="ps-xtal" data-n="6" style="left:360px;top:178px;--d:3.18s" aria-label="Crystal chime 7"></button>
                    </div>

                    <div class="ps-feather" aria-hidden="true">
                        <div class="ps-feather__sway">
                            <img
                                src="<?php echo esc_url($theme_uri . '/assets/images/ps-feather.png'); ?>"
                                alt=""
                                width="580"
                                height="523"
                                draggable="false"
                            />
                        </div>
                    </div>
                </div>

                <div class="ps-scene__lens" aria-hidden="true"></div>
                <div class="ps-scene__veil" aria-hidden="true"></div>

                <div class="ps-hint music" id="ps-hint-music">♪ Tap the crystals</div>
                <div class="ps-hint art" id="ps-hint-art">◐ Tap any color on the flower</div>

                <figure class="ps-swatches" id="ps-swatches" aria-live="polite">
                    <div id="ps-chips"></div>
                    <figcaption>
                        <span>From nature</span>
                        <span id="ps-paint-source">Petal</span>
                    </figcaption>
                </figure>
            </div>

            <!--
              Themes + shelf sit on the sticky stage but OUTSIDE .ps-scene.
              Nested under .ps-scene/.ps-hero__sticky overflow:hidden, iOS Safari
              required two-finger pans for this overflow-x shelf and blocked
              one-finger access to off-screen cards.
            -->
            <nav class="ps-journey-themes ps-journey-away" id="ps-journey-themes" aria-label="Tags"></nav>
            <div class="ps-journey-shelf ps-journey-away" id="ps-journey-shelf" aria-live="polite" aria-label="Pieces"></div>
        </div>
        <div class="ps-hero__track" aria-hidden="true"></div>
    </section>

    <div class="ps-bar" id="ps-bar">
        <nav class="ps-trail" id="ps-trail" aria-label="Choose writing, music or art">
            <span class="ps-dot" id="ps-dot" aria-hidden="true"></span>
            <button type="button" class="ps-stop home" data-m="home" aria-pressed="true">P.S.</button>
            <button type="button" class="ps-stop" data-m="writing" aria-pressed="false">Writing</button>
            <button type="button" class="ps-stop" data-m="music" aria-pressed="false">Music</button>
            <button type="button" class="ps-stop" data-m="art" aria-pressed="false">Art</button>
        </nav>
    </div>

    <?php
    /* Phase 2: journey shelf fed by published Pages / template-backed public works. */
    noelclark_v1_ps_journey_render_data();
    ?>

    <section class="ps-field" aria-label="Personal Seal archive">
        <div class="ps-field__layout">
            <div class="ps-field__canvas">
                <div class="ps-browse" id="ps-browse" aria-label="Search P.S.">
                    <form class="ps-journey-search ps-browse__search" id="ps-browse-search" role="search" onsubmit="return false">
                        <label class="visually-hidden" for="ps-browse-query">Search P.S.</label>
                        <input
                            type="search"
                            id="ps-browse-query"
                            placeholder="Search P.S."
                            autocomplete="off"
                            enterkeyhint="search"
                        />
                        <button class="clear" type="button" id="ps-browse-clear" aria-label="Clear search">×</button>
                    </form>
                    <div class="ps-explore">
                        <button
                            type="button"
                            class="ps-explore__toggle"
                            id="ps-explore-toggle"
                            aria-expanded="false"
                            aria-controls="ps-browse-discovery"
                        >
                            Explore
                        </button>
                        <div class="ps-explore__panel" id="ps-browse-discovery" hidden></div>
                    </div>
                    <p class="ps-browse__active" id="ps-browse-active" hidden>
                        <span id="ps-browse-active-text"></span>
                        <button type="button" class="ps-browse__clear" id="ps-browse-clear-filters">Clear</button>
                    </p>
                </div>

                <?php noelclark_v1_ps_archive_render(); ?>

                <button type="button" class="ps-browse__more" id="ps-browse-more" hidden>
                    Load more
                </button>
            </div>
        </div>
    </section>
</main>

<dialog class="ps-members-note" id="ps-members-note" aria-labelledby="ps-members-note-title">
    <div class="ps-members-note__panel">
        <button type="button" class="ps-members-note__close">Close</button>
        <h2 class="ps-members-note__title" id="ps-members-note-title">Members only</h2>
        <p class="ps-members-note__work"></p>
        <p class="ps-members-note__copy">
            This work is reserved for members. Membership is not wired yet — this is a
            presentation prototype for how member-only P.S. pieces may appear.
        </p>
        <button type="button" class="ps-members-note__cta" disabled aria-disabled="true">
            Membership
        </button>
    </div>
</dialog>

<?php
get_footer();
