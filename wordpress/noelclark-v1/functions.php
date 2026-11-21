<?php
/**
 * NoelClark V1 theme bootstrap.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

require get_template_directory() . '/inc/enqueue.php';
require get_template_directory() . '/inc/ps-journey.php';
require get_template_directory() . '/inc/contact/validation.php';
require get_template_directory() . '/inc/contact/mail.php';
require get_template_directory() . '/inc/contact/handler.php';
require get_template_directory() . '/inc/mail-room/schema.php';
require get_template_directory() . '/inc/mail-room/capabilities.php';
require get_template_directory() . '/inc/mail-room/install.php';
require get_template_directory() . '/inc/mail-room/ids.php';
require get_template_directory() . '/inc/mail-room/publish.php';
require get_template_directory() . '/inc/mail-room/validation.php';
require get_template_directory() . '/inc/mail-room/idempotency.php';
require get_template_directory() . '/inc/mail-room/association.php';
require get_template_directory() . '/inc/mail-room/submit.php';
require get_template_directory() . '/inc/mail-room/notify.php';
require get_template_directory() . '/inc/mail-room/questionnaire.php';
require get_template_directory() . '/inc/mail-room/rest-submit.php';
require get_template_directory() . '/inc/mail-room/rest-questionnaire.php';
require get_template_directory() . '/inc/mail-room/terms-display.php';
require get_template_directory() . '/inc/mail-room/window-scenes.php';
require get_template_directory() . '/inc/social/reactions.php';

if (is_admin()) {
    require get_template_directory() . '/inc/mail-room/admin-read.php';
    require get_template_directory() . '/inc/mail-room/admin-inbox.php';
    require get_template_directory() . '/inc/mail-room/admin-publish.php';
}

/**
 * Bebas Neue, DM Sans, Newsreader, Geist, and Geist Mono for the private Mail Room admin only.
 *
 * @param string $hook_suffix Current admin screen hook.
 * @return void
 */
function noelclark_v1_mail_room_admin_enqueue_fonts($hook_suffix) {
    if (!defined('NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG') || $hook_suffix !== 'toplevel_page_' . NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG) {
        return;
    }

    if (!function_exists('noelclark_v1_mail_room_admin_can_read') || !noelclark_v1_mail_room_admin_can_read()) {
        return;
    }

    wp_enqueue_style(
        'noelclark-v1-mail-room-admin-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500;700&family=Newsreader:ital,opsz,wght@0,6..72,400;1,6..72,400&display=swap',
        array(),
        null
    );
}
add_action('admin_enqueue_scripts', 'noelclark_v1_mail_room_admin_enqueue_fonts', 5);

/**
 * Theme setup.
 */
function noelclark_v1_setup() {
    add_theme_support('title-tag');
}
add_action('after_setup_theme', 'noelclark_v1_setup');

/**
 * Preserve approved Contact body class on the Contact template.
 *
 * @param string[] $classes Body classes.
 * @return string[] Body classes.
 */
function noelclark_v1_body_classes($classes) {
    /* First paint: homepage/arrival nav chrome is available (no delayed reveal). */
    if (function_exists('noelclark_v1_is_front_page_experience') && noelclark_v1_is_front_page_experience()) {
        $classes[] = 'nav-visible';
    }

    if (noelclark_v1_is_contact_page()) {
        $classes[] = 'contact-page';
    }

    if (noelclark_v1_is_john_clark_page()) {
        $classes[] = 'jc-page';
    }

    if (noelclark_v1_is_about_page()) {
        $classes[] = 'about-page';
    }

    if (noelclark_v1_is_return_to_nature_page()) {
        $classes[] = 'rtn-page';
    }

    if (noelclark_v1_is_ps_page() || noelclark_v1_is_ps_work_page()) {
        $classes[] = 'ps-page';
    }

    if (noelclark_v1_is_ps_work_page()) {
        $classes[] = 'ps-work-page';
    }

    return $classes;
}
add_filter('body_class', 'noelclark_v1_body_classes');

/**
 * Current nav slug for interior header chrome.
 *
 * @return string
 */
function noelclark_v1_current_nav_slug() {
    $current = get_query_var('noelclark_nav_current', '');

    if ($current !== '') {
        return (string) $current;
    }

    if (is_page('contact')) {
        return 'contact';
    }

    return '';
}

/**
 * Render one interior nav link or current-page span.
 *
 * @param string $slug  Nav item slug.
 * @param string $label Visible label.
 * @param string $path  Site path (e.g. /mail-room/).
 */
function noelclark_v1_render_nav_item($slug, $label, $path) {
    if (noelclark_v1_current_nav_slug() === $slug) {
        printf(
            '<span class="site-header__link" aria-current="page">%s</span>',
            esc_html($label)
        );
        return;
    }

    printf(
        '<a class="site-header__link" href="%s">%s</a>',
        esc_url(home_url($path)),
        esc_html($label)
    );
}
