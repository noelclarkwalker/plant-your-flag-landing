<?php
/**
 * Template Name: Mail Room
 * The Mail Room — approved frontend fidelity.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

set_query_var('noelclark_nav_current', 'mail-room');

$theme_uri = get_template_directory_uri();

get_header();
?>

<main id="mail-room">
    <section class="mail-room-write" aria-labelledby="mail-room-write-heading">
        <div class="mail-room-write__inner">
            <p class="mail-room-write__room">THE MAIL ROOM</p>
            <h1 class="mail-room-write__title" id="mail-room-write-heading">
                WRITE TO THE MAIL ROOM
            </h1>
            <p class="mail-room-write__copy">
                Write what's on your heart. Share a story, a question, a memory, a
                disagreement, a theory, a fragment.
            </p>
            <p class="mail-room-write__copy">
                Sending it does not mean it will be published, answered, or turned
                into art. It means it arrived.
            </p>
            <button type="button" class="mail-room-write__cta" id="mail-room-send-letter">
                SEND A LETTER →
            </button>
        </div>
    </section>

    <section class="mail-room-hero" aria-label="What arrived">
        <div class="mail-room-hero__sticky">
            <div class="mail-room-hero__clip">
                <div class="mail-room-hero__media" aria-hidden="true">
                    <img
                        class="mail-room-hero__photo"
                        src="<?php echo esc_url($theme_uri . '/assets/images/mail-room-header.png'); ?>"
                        alt=""
                        width="1920"
                        height="819"
                        fetchpriority="high"
                    />
                </div>
            </div>
            <div class="mail-room-hero__titles">
                <p class="mail-room-hero__room">THE MAIL ROOM</p>
                <h2 class="mail-room-hero__kicker">WHAT ARRIVED</h2>
            </div>
        </div>
        <div class="mail-room-hero__track" aria-hidden="true"></div>
    </section>

    <section class="mail-room-field" aria-labelledby="mail-room-field-heading">
        <h2 class="visually-hidden" id="mail-room-field-heading">WHAT ARRIVED</h2>

        <p class="mail-room-field__intro">
            Selected correspondence gathered across time — each piece waiting to be
            encountered on its own terms.
        </p>

        <div class="mail-room-field__layout">
            <div class="mail-room-field__archive">
                <p class="mail-browse__empty" id="mail-browse-empty" hidden>
                    No correspondence matches.
                </p>

                <div class="mail-room-field__grid" id="mail-room-artifacts"></div>

                <button type="button" class="mail-browse__more" id="mail-browse-more" hidden>
                    Load more
                </button>
            </div>

            <aside class="mail-room-rail" id="mail-browse" aria-label="Search and explore correspondence">
                <div class="mail-browse__search">
                    <label class="mail-browse__search-label" for="mail-browse-query">
                        Search correspondence
                    </label>
                    <input
                        type="search"
                        id="mail-browse-query"
                        class="mail-browse__search-input"
                        autocomplete="off"
                        enterkeyhint="search"
                    />
                </div>
                <p class="mail-browse__active" id="mail-browse-active" hidden>
                    <span id="mail-browse-active-text"></span>
                    <button type="button" class="mail-browse__clear" id="mail-browse-clear">
                        Clear
                    </button>
                </p>
                <div id="mail-browse-discovery"></div>
            </aside>
        </div>
    </section>
</main>

<dialog class="mail-reading" id="mail-reading" aria-labelledby="mail-reading-title">
    <div class="mail-reading__panel">
        <div class="mail-reading__toolbar">
            <button type="button" class="mail-reading__return">← BACK TO WHAT ARRIVED</button>
            <span class="visually-hidden" id="mail-reading-title">Reading correspondence</span>
        </div>
        <div class="mail-reading__body"></div>
    </div>
</dialog>

<dialog class="mail-submit" id="mail-submit" aria-labelledby="mail-submit-dialog-title">
    <div class="mail-submit__panel">
        <div class="mail-submit__toolbar">
            <button type="button" class="mail-submit__return">← BACK TO WHAT ARRIVED</button>
            <button type="button" class="mail-submit__dev-preview" hidden>
                Preview arrival layout (development only)
            </button>
            <span class="visually-hidden" id="mail-submit-dialog-title">Write to the Mail Room</span>
            <span class="visually-hidden" id="mail-submit-step-live" aria-live="polite"></span>
        </div>
        <div class="mail-submit__body" id="mail-submit-body"></div>
    </div>
</dialog>

<?php
get_footer();
