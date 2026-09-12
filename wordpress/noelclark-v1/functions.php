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
require get_template_directory() . '/inc/contact/validation.php';
require get_template_directory() . '/inc/contact/mail.php';
require get_template_directory() . '/inc/contact/handler.php';

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
    if (noelclark_v1_is_contact_page()) {
        $classes[] = 'contact-page';
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
