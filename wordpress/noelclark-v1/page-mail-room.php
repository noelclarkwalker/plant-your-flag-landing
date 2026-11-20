<?php
/**
 * Template Name: Mail Room
 * The Mail Room — integrated architectural window + correspondence.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

set_query_var('noelclark_nav_current', 'mail-room');

get_header();
?>

<main id="mail-room">
    <?php noelclark_v1_mail_room_window_render_stage(); ?>
</main>

<dialog class="mail-reading" id="mail-reading" aria-labelledby="mail-reading-title">
    <div class="mail-reading__panel">
        <div class="mail-reading__toolbar">
            <button type="button" class="mail-reading__return">← BACK TO THE MAIL ROOM</button>
            <span class="visually-hidden" id="mail-reading-title">Reading correspondence</span>
        </div>
        <div class="mail-reading__body"></div>
    </div>
</dialog>

<dialog class="mail-submit" id="mail-submit" aria-labelledby="mail-submit-dialog-title">
    <div class="mail-submit__panel">
        <div class="mail-submit__toolbar">
            <button type="button" class="mail-submit__return">← BACK TO THE MAIL ROOM</button>
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
