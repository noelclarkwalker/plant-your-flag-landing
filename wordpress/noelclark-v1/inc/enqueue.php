<?php
/**
 * Theme asset enqueue — Contact page slice.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return bool
 */
function noelclark_v1_is_contact_page() {
    return is_page('contact') || is_page_template('page-contact.php');
}

/**
 * Register and enqueue Contact page styles.
 */
function noelclark_v1_enqueue_contact_assets() {
    if (!noelclark_v1_is_contact_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();
    $version   = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'noelclark-v1-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), $version);
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), $version);
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), $version);
    wp_enqueue_style('noelclark-v1-contact', $theme_uri . '/assets/css/contact.css', array('noelclark-v1-site'), $version);
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_contact_assets');

/**
 * Google Fonts preconnect hints for Contact page.
 */
function noelclark_v1_contact_resource_hints($urls, $relation_type) {
    if (!noelclark_v1_is_contact_page() || $relation_type !== 'preconnect') {
        return $urls;
    }

    $urls[] = array(
        'href' => 'https://fonts.googleapis.com',
    );
    $urls[] = array(
        'href'        => 'https://fonts.gstatic.com',
        'crossorigin' => 'anonymous',
    );

    return $urls;
}
add_filter('wp_resource_hints', 'noelclark_v1_contact_resource_hints', 10, 2);

/**
 * Contact page meta description.
 */
function noelclark_v1_contact_meta_description() {
    if (!noelclark_v1_is_contact_page()) {
        return;
    }

    echo '<meta name="description" content="General inquiries for Noèl Clark and NoelClark.com — press, collaborations, speaking, rights, and website questions.">' . "\n";
}
add_action('wp_head', 'noelclark_v1_contact_meta_description', 1);

/**
 * Contact page document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_contact_document_title($parts) {
    if (!noelclark_v1_is_contact_page()) {
        return $parts;
    }

    $parts['title'] = 'Contact';
    $parts['site']  = 'NoelClark.com';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_contact_document_title');
