<?php
/**
 * Theme asset enqueue — front page and Contact page slices.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return bool
 */
function noelclark_v1_is_front_page_experience() {
    return is_front_page();
}

/**
 * @return bool
 */
function noelclark_v1_is_contact_page() {
    return is_page('contact') || is_page_template('page-contact.php');
}

/**
 * Register and enqueue front page landing + Homepage assets.
 */
function noelclark_v1_enqueue_front_page_assets() {
    if (!noelclark_v1_is_front_page_experience()) {
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
    wp_enqueue_style('noelclark-v1-base', $theme_uri . '/assets/css/base.css', array('noelclark-v1-variables'), $version);
    wp_enqueue_style('noelclark-v1-world-environment', $theme_uri . '/assets/css/world-environment.css', array('noelclark-v1-base'), $version);
    wp_enqueue_style('noelclark-v1-layout', $theme_uri . '/assets/css/layout.css', array('noelclark-v1-world-environment'), $version);
    wp_enqueue_style('noelclark-v1-typography', $theme_uri . '/assets/css/typography.css', array('noelclark-v1-layout'), $version);
    wp_enqueue_style('noelclark-v1-animations', $theme_uri . '/assets/css/animations.css', array('noelclark-v1-typography'), $version);
    wp_enqueue_style('noelclark-v1-hero', $theme_uri . '/assets/css/hero.css', array('noelclark-v1-animations'), $version);
    wp_enqueue_style('noelclark-v1-social', $theme_uri . '/assets/css/social.css', array('noelclark-v1-hero'), $version);
    wp_enqueue_style('noelclark-v1-arrival', $theme_uri . '/assets/css/arrival.css', array('noelclark-v1-social'), $version);
    wp_enqueue_style('noelclark-v1-cinema', $theme_uri . '/assets/css/cinema.css', array('noelclark-v1-arrival'), $version);
    wp_enqueue_style('noelclark-v1-manifesto-stack', $theme_uri . '/assets/css/manifesto-stack.css', array('noelclark-v1-cinema'), $version);
    wp_enqueue_style('noelclark-v1-navigation', $theme_uri . '/assets/css/navigation.css', array('noelclark-v1-manifesto-stack'), $version);
    wp_enqueue_style('noelclark-v1-portal', $theme_uri . '/assets/css/portal.css', array('noelclark-v1-navigation'), $version);
    wp_enqueue_style('noelclark-v1-home', $theme_uri . '/assets/css/home.css', array('noelclark-v1-portal'), $version);

    wp_enqueue_script(
        'noelclark-v1-gsap',
        'https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js',
        array(),
        '3.13.0',
        true
    );
    wp_enqueue_script(
        'noelclark-v1-scrolltrigger',
        'https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js',
        array('noelclark-v1-gsap'),
        '3.13.0',
        true
    );
    wp_enqueue_script('noelclark-v1-world', $theme_uri . '/assets/js/world.js', array('noelclark-v1-gsap', 'noelclark-v1-scrolltrigger'), $version, true);
    wp_enqueue_script('noelclark-v1-navigation', $theme_uri . '/assets/js/navigation.js', array(), $version, true);
    wp_enqueue_script('noelclark-v1-social-interactions', $theme_uri . '/assets/js/social-interactions.js', array(), $version, true);
    wp_enqueue_script('noelclark-v1-feature-01-state', $theme_uri . '/assets/js/feature-01-state.js', array(), $version, true);
    wp_enqueue_script('noelclark-v1-arrival', $theme_uri . '/assets/js/arrival.js', array(), $version, true);
    wp_enqueue_script('noelclark-v1-manifesto-stack', $theme_uri . '/assets/js/manifesto-stack.js', array(), $version, true);
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_front_page_assets');

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
function noelclark_v1_theme_resource_hints($urls, $relation_type) {
    if ($relation_type !== 'preconnect') {
        return $urls;
    }

    if (!noelclark_v1_is_front_page_experience() && !noelclark_v1_is_contact_page()) {
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
add_filter('wp_resource_hints', 'noelclark_v1_theme_resource_hints', 10, 2);

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

/**
 * Front page document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_front_page_document_title($parts) {
    if (!noelclark_v1_is_front_page_experience()) {
        return $parts;
    }

    $parts['title'] = 'Plant Your Flag';
    $parts['site']  = 'Noel Clark';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_front_page_document_title');
