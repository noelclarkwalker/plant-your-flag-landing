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
        </div>
        <div class="ps-hero__track" aria-hidden="true"></div>
    </section>

    <section class="ps-field" aria-label="Noèl's authored work">
        <div class="ps-field__layout">
            <div class="ps-field__canvas">
                <p class="ps-browse__empty" id="ps-browse-empty" hidden>
                    Nothing in P.S. matches that.
                </p>

                <div class="ps-collection">
                    <article class="ps-preview ps-preview--journal" id="ps-work-returning-open-journal">
                        <div class="ps-preview__body">
                            <div class="ps-preview__primary">
                                <h2 class="ps-preview__title">RETURNING TO THE OPEN JOURNAL</h2>
                                <p class="ps-preview__lead">I stopped blogging a few years ago.</p>
                                <a class="ps-preview__read" href="<?php echo esc_url(home_url('/ps/returning-to-the-open-journal/')); ?>">
                                    READ →
                                </a>
                            </div>

                            <div class="ps-preview__visual ps-preview__visual--pair">
                                <figure class="ps-preview__thumb ps-preview__thumb--keyboard">
                                    <img
                                        src="<?php echo esc_url($theme_uri . '/assets/images/lavender-on-keyboard.PNG'); ?>"
                                        alt=""
                                        width="2000"
                                        height="2000"
                                        loading="lazy"
                                    />
                                </figure>
                                <figure class="ps-preview__thumb ps-preview__thumb--office">
                                    <img
                                        src="<?php echo esc_url($theme_uri . '/assets/images/daytime-office.JPG'); ?>"
                                        alt=""
                                        width="5712"
                                        height="4284"
                                        loading="lazy"
                                    />
                                </figure>
                            </div>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--photo" id="ps-preview-invisible-thread">
                        <div class="ps-preview__body ps-preview__body--reverse">
                            <div class="ps-preview__primary">
                                <h2 class="ps-preview__title">THE INVISIBLE THREAD</h2>
                                <a class="ps-preview__read" href="<?php echo esc_url(home_url('/ps/the-invisible-thread/')); ?>">READ →</a>
                            </div>

                            <figure class="ps-preview__visual ps-preview__visual--single">
                                <img
                                    src="<?php echo esc_url($theme_uri . '/assets/images/invisible-thread-ego-fitted.png'); ?>"
                                    alt=""
                                    width="1491"
                                    height="1055"
                                    loading="lazy"
                                />
                            </figure>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--photo" id="ps-work-magenta-bloom">
                        <div class="ps-preview__body ps-preview__body--reverse">
                            <div class="ps-preview__primary">
                                <h2 class="ps-preview__title">MAGENTA BLOOM</h2>
                                <a class="ps-preview__view" href="<?php echo esc_url(home_url('/ps/magenta-bloom/')); ?>">VIEW →</a>
                            </div>

                            <figure class="ps-preview__visual ps-preview__visual--single">
                                <img
                                    src="<?php echo esc_url($theme_uri . '/assets/images/flower-compound.JPG'); ?>"
                                    alt=""
                                    width="5712"
                                    height="4284"
                                    loading="lazy"
                                />
                            </figure>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--photo" id="ps-work-lavender-nostalgia">
                        <div class="ps-preview__body ps-preview__body--stack">
                            <figure class="ps-preview__visual ps-preview__visual--portrait">
                                <img
                                    src="<?php echo esc_url($theme_uri . '/assets/images/lavender-nostalgia.JPG'); ?>"
                                    alt=""
                                    width="5712"
                                    height="4284"
                                    loading="lazy"
                                />
                            </figure>

                            <div class="ps-preview__primary ps-preview__primary--compact">
                                <h2 class="ps-preview__title">LAVENDER NOSTALGIA</h2>
                                <button
                                    type="button"
                                    class="ps-preview__view"
                                    disabled
                                    aria-disabled="true"
                                >
                                    VIEW →
                                </button>
                            </div>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--photo" id="ps-work-out-the-windows">
                        <div class="ps-preview__body">
                            <div class="ps-preview__primary">
                                <h2 class="ps-preview__title">OUT THE WINDOWS</h2>
                                <button
                                    type="button"
                                    class="ps-preview__view"
                                    disabled
                                    aria-disabled="true"
                                >
                                    VIEW →
                                </button>
                            </div>

                            <figure class="ps-preview__visual ps-preview__visual--wide">
                                <img
                                    src="<?php echo esc_url($theme_uri . '/assets/images/out-the-windows.JPG'); ?>"
                                    alt=""
                                    width="5712"
                                    height="4284"
                                    loading="lazy"
                                />
                            </figure>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--motion" id="ps-work-observing-a-butterfly">
                        <div class="ps-preview__body ps-preview__body--reverse">
                            <div class="ps-preview__primary">
                                <h2 class="ps-preview__title">OBSERVING A BUTTERFLY</h2>
                                <a class="ps-preview__watch" href="<?php echo esc_url(home_url('/ps/observing-a-butterfly/')); ?>">WATCH →</a>
                            </div>

                            <figure class="ps-preview__visual ps-preview__visual--motion">
                                <video
                                    class="ps-preview__video"
                                    width="1920"
                                    height="1080"
                                    muted
                                    playsinline
                                    loop
                                    autoplay
                                    preload="auto"
                                >
                                    <source
                                        src="<?php echo esc_url($theme_uri . '/assets/video/observing-a-butterfly.MP4'); ?>"
                                        type="video/mp4"
                                    />
                                </video>
                            </figure>
                        </div>
                    </article>

                    <article class="ps-preview ps-preview--listen" id="ps-work-hell-its-forever-lit">
                        <div class="ps-preview__body ps-preview__body--stack">
                            <figure class="ps-preview__visual ps-preview__visual--listen">
                                <video
                                    class="ps-preview__video"
                                    muted
                                    playsinline
                                    loop
                                    autoplay
                                    preload="metadata"
                                >
                                    <source
                                        src="<?php echo esc_url($theme_uri . '/assets/video/forever-lit-walkers-wraith.mp4'); ?>"
                                        type="video/mp4"
                                    />
                                </video>
                            </figure>

                            <div class="ps-preview__primary ps-preview__primary--compact">
                                <h2 class="ps-preview__title">HELL (IT'S FOREVER LIT)</h2>
                                <a class="ps-preview__listen" href="<?php echo esc_url(home_url('/ps/hell-its-forever-lit/')); ?>">PLAY →</a>
                            </div>
                        </div>
                    </article>
                </div>

                <button type="button" class="ps-browse__more" id="ps-browse-more" hidden>
                    Load more
                </button>
            </div>

            <aside class="ps-browse" id="ps-browse" aria-label="Search and explore P.S.">
                <div class="ps-browse__search">
                    <label class="ps-browse__search-label" for="ps-browse-query">Search P.S.</label>
                    <input
                        type="search"
                        id="ps-browse-query"
                        class="ps-browse__search-input"
                        autocomplete="off"
                        enterkeyhint="search"
                    />
                </div>
                <p class="ps-browse__active" id="ps-browse-active" hidden>
                    <span id="ps-browse-active-text"></span>
                    <button type="button" class="ps-browse__clear" id="ps-browse-clear">Clear</button>
                </p>
                <div id="ps-browse-discovery"></div>
            </aside>
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
