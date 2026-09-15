<?php
/**
 * Theme asset enqueue — front page, Contact page, Mail Room page, Return to Nature page, and P.S. index slices.
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
 * @return bool
 */
function noelclark_v1_is_mail_room_page() {
    return is_page('mail-room') || is_page_template('page-mail-room.php');
}

/**
 * @return bool
 */
function noelclark_v1_is_return_to_nature_page() {
    return is_page('return-to-nature') || is_page_template('page-return-to-nature.php');
}

/**
 * @return bool
 */
function noelclark_v1_is_ps_page() {
    return is_page('ps') || is_page_template('page-ps.php');
}

/**
 * Any P.S. work template (page-ps-*.php), excluding the P.S. index.
 *
 * @return bool
 */
function noelclark_v1_is_ps_work_page() {
    $template = get_page_template_slug();

    return is_string($template) && strpos($template, 'page-ps-') === 0;
}

/**
 * The Invisible Thread work page only.
 *
 * @return bool
 */
function noelclark_v1_is_ps_invisible_thread_page() {
    return is_page_template('page-ps-the-invisible-thread.php');
}

/**
 * Per-file cache key for theme-owned CSS/JS. Uses filemtime so a changed
 * asset gets a new ?ver= without bumping the WordPress theme Version.
 *
 * @param string $relative_path Path from the theme root (e.g. assets/css/site.css).
 * @return string
 */
function noelclark_v1_asset_version($relative_path) {
    $path = get_template_directory() . '/' . ltrim($relative_path, '/');

    if (is_readable($path)) {
        $mtime = filemtime($path);
        if ($mtime !== false) {
            return (string) $mtime;
        }
    }

    return (string) wp_get_theme()->get('Version');
}

/**
 * Register and enqueue front page landing + Homepage assets.
 */
function noelclark_v1_enqueue_front_page_assets() {
    if (!noelclark_v1_is_front_page_experience()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-base', $theme_uri . '/assets/css/base.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/base.css'));
    wp_enqueue_style('noelclark-v1-world-environment', $theme_uri . '/assets/css/world-environment.css', array('noelclark-v1-base'), noelclark_v1_asset_version('assets/css/world-environment.css'));
    wp_enqueue_style('noelclark-v1-layout', $theme_uri . '/assets/css/layout.css', array('noelclark-v1-world-environment'), noelclark_v1_asset_version('assets/css/layout.css'));
    wp_enqueue_style('noelclark-v1-typography', $theme_uri . '/assets/css/typography.css', array('noelclark-v1-layout'), noelclark_v1_asset_version('assets/css/typography.css'));
    wp_enqueue_style('noelclark-v1-animations', $theme_uri . '/assets/css/animations.css', array('noelclark-v1-typography'), noelclark_v1_asset_version('assets/css/animations.css'));
    wp_enqueue_style('noelclark-v1-hero', $theme_uri . '/assets/css/hero.css', array('noelclark-v1-animations'), noelclark_v1_asset_version('assets/css/hero.css'));
    wp_enqueue_style('noelclark-v1-social', $theme_uri . '/assets/css/social.css', array('noelclark-v1-hero'), noelclark_v1_asset_version('assets/css/social.css'));
    wp_enqueue_style('noelclark-v1-arrival', $theme_uri . '/assets/css/arrival.css', array('noelclark-v1-social'), noelclark_v1_asset_version('assets/css/arrival.css'));
    wp_enqueue_style('noelclark-v1-cinema', $theme_uri . '/assets/css/cinema.css', array('noelclark-v1-arrival'), noelclark_v1_asset_version('assets/css/cinema.css'));
    wp_enqueue_style('noelclark-v1-manifesto-stack', $theme_uri . '/assets/css/manifesto-stack.css', array('noelclark-v1-cinema'), noelclark_v1_asset_version('assets/css/manifesto-stack.css'));
    wp_enqueue_style('noelclark-v1-navigation', $theme_uri . '/assets/css/navigation.css', array('noelclark-v1-manifesto-stack'), noelclark_v1_asset_version('assets/css/navigation.css'));
    wp_enqueue_style('noelclark-v1-portal', $theme_uri . '/assets/css/portal.css', array('noelclark-v1-navigation'), noelclark_v1_asset_version('assets/css/portal.css'));
    wp_enqueue_style('noelclark-v1-home', $theme_uri . '/assets/css/home.css', array('noelclark-v1-portal'), noelclark_v1_asset_version('assets/css/home.css'));

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
    wp_enqueue_script('noelclark-v1-world', $theme_uri . '/assets/js/world.js', array('noelclark-v1-gsap', 'noelclark-v1-scrolltrigger'), noelclark_v1_asset_version('assets/js/world.js'), true);
    wp_enqueue_script('noelclark-v1-navigation', $theme_uri . '/assets/js/navigation.js', array(), noelclark_v1_asset_version('assets/js/navigation.js'), true);
    wp_enqueue_script('noelclark-v1-social-interactions', $theme_uri . '/assets/js/social-interactions.js', array(), noelclark_v1_asset_version('assets/js/social-interactions.js'), true);
    wp_enqueue_script('noelclark-v1-feature-01-state', $theme_uri . '/assets/js/feature-01-state.js', array(), noelclark_v1_asset_version('assets/js/feature-01-state.js'), true);
    wp_enqueue_script('noelclark-v1-arrival', $theme_uri . '/assets/js/arrival.js', array(), noelclark_v1_asset_version('assets/js/arrival.js'), true);
    wp_enqueue_script('noelclark-v1-manifesto-stack', $theme_uri . '/assets/js/manifesto-stack.js', array(), noelclark_v1_asset_version('assets/js/manifesto-stack.js'), true);
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

    wp_enqueue_style(
        'noelclark-v1-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/site.css'));
    wp_enqueue_style('noelclark-v1-contact', $theme_uri . '/assets/css/contact.css', array('noelclark-v1-site'), noelclark_v1_asset_version('assets/css/contact.css'));
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_contact_assets');

/**
 * Register and enqueue Mail Room page assets.
 */
function noelclark_v1_enqueue_mail_room_assets() {
    if (!noelclark_v1_is_mail_room_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-mail-room-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IM+Fell+Double+Pica&family=Instrument+Sans:wght@400;500;600&family=Roboto+Mono:wght@400&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/site.css'));
    wp_enqueue_style('noelclark-v1-mail-room', $theme_uri . '/assets/css/mail-room.css', array('noelclark-v1-site'), noelclark_v1_asset_version('assets/css/mail-room.css'));

    wp_enqueue_script('noelclark-v1-browse-core', $theme_uri . '/assets/js/browse-core.js', array(), noelclark_v1_asset_version('assets/js/browse-core.js'), true);
    wp_enqueue_script('noelclark-v1-mail-room-data', $theme_uri . '/assets/js/mail-room-data.js', array(), noelclark_v1_asset_version('assets/js/mail-room-data.js'), true);
    wp_enqueue_script('noelclark-v1-mail-room', $theme_uri . '/assets/js/mail-room.js', array('noelclark-v1-browse-core'), noelclark_v1_asset_version('assets/js/mail-room.js'), true);
    wp_enqueue_script('noelclark-v1-mail-room-questions', $theme_uri . '/assets/js/mail-room-questions.js', array(), noelclark_v1_asset_version('assets/js/mail-room-questions.js'), true);
    wp_enqueue_script('noelclark-v1-mail-room-submit', $theme_uri . '/assets/js/mail-room-submit.js', array(), noelclark_v1_asset_version('assets/js/mail-room-submit.js'), true);

    wp_localize_script(
        'noelclark-v1-mail-room',
        'noelclarkV1MailRoom',
        array(
            'roomPath' => wp_parse_url(home_url('/mail-room/'), PHP_URL_PATH) ?: '/mail-room/',
        )
    );
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_mail_room_assets');

/**
 * Register and enqueue Return to Nature page assets.
 */
function noelclark_v1_enqueue_return_to_nature_assets() {
    if (!noelclark_v1_is_return_to_nature_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-return-to-nature-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/site.css'));
    wp_enqueue_style('noelclark-v1-return-to-nature', $theme_uri . '/assets/css/return-to-nature.css', array('noelclark-v1-site'), noelclark_v1_asset_version('assets/css/return-to-nature.css'));

    wp_enqueue_script('noelclark-v1-return-to-nature', $theme_uri . '/assets/js/return-to-nature.js', array(), noelclark_v1_asset_version('assets/js/return-to-nature.js'), true);
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_return_to_nature_assets');

/**
 * Register and enqueue P.S. index assets.
 */
function noelclark_v1_enqueue_ps_assets() {
    if (!noelclark_v1_is_ps_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/site.css'));
    wp_enqueue_style('noelclark-v1-ps', $theme_uri . '/assets/css/ps.css', array('noelclark-v1-site'), noelclark_v1_asset_version('assets/css/ps.css'));

    wp_enqueue_script('noelclark-v1-browse-core', $theme_uri . '/assets/js/browse-core.js', array(), noelclark_v1_asset_version('assets/js/browse-core.js'), true);
    wp_enqueue_script('noelclark-v1-ps-data', $theme_uri . '/assets/js/ps-data.js', array(), noelclark_v1_asset_version('assets/js/ps-data.js'), true);
    wp_enqueue_script('noelclark-v1-ps', $theme_uri . '/assets/js/ps.js', array(), noelclark_v1_asset_version('assets/js/ps.js'), true);
    wp_enqueue_script('noelclark-v1-ps-browse', $theme_uri . '/assets/js/ps-browse.js', array('noelclark-v1-browse-core'), noelclark_v1_asset_version('assets/js/ps-browse.js'), true);

    wp_localize_script(
        'noelclark-v1-ps-browse',
        'noelclarkV1Ps',
        array(
            'roomPath' => wp_parse_url(home_url('/ps/'), PHP_URL_PATH) ?: '/ps/',
        )
    );
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_ps_assets');

/**
 * Register and enqueue P.S. work-page assets.
 */
function noelclark_v1_enqueue_ps_work_assets() {
    if (!noelclark_v1_is_ps_work_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style('noelclark-v1-reset', $theme_uri . '/assets/css/reset.css', array(), noelclark_v1_asset_version('assets/css/reset.css'));
    wp_enqueue_style('noelclark-v1-variables', $theme_uri . '/assets/css/variables.css', array('noelclark-v1-reset'), noelclark_v1_asset_version('assets/css/variables.css'));
    wp_enqueue_style('noelclark-v1-site', $theme_uri . '/assets/css/site.css', array('noelclark-v1-variables'), noelclark_v1_asset_version('assets/css/site.css'));
    wp_enqueue_style('noelclark-v1-ps', $theme_uri . '/assets/css/ps.css', array('noelclark-v1-site'), noelclark_v1_asset_version('assets/css/ps.css'));

    wp_enqueue_script('noelclark-v1-browse-core', $theme_uri . '/assets/js/browse-core.js', array(), noelclark_v1_asset_version('assets/js/browse-core.js'), true);
    wp_enqueue_script('noelclark-v1-ps-data', $theme_uri . '/assets/js/ps-data.js', array(), noelclark_v1_asset_version('assets/js/ps-data.js'), true);
    wp_enqueue_script('noelclark-v1-room-tags', $theme_uri . '/assets/js/room-tags.js', array('noelclark-v1-browse-core'), noelclark_v1_asset_version('assets/js/room-tags.js'), true);

    wp_localize_script(
        'noelclark-v1-room-tags',
        'noelclarkV1Ps',
        array(
            'roomPath' => wp_parse_url(home_url('/ps/'), PHP_URL_PATH) ?: '/ps/',
        )
    );
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_ps_work_assets');

/**
 * Piece-specific Invisible Thread CSS/JS — only this work page.
 * CSS after shared ps.css; JS after shared work scripts (browse-core, ps-data, room-tags).
 */
function noelclark_v1_enqueue_ps_invisible_thread_assets() {
    if (!noelclark_v1_is_ps_invisible_thread_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-ps-invisible-thread',
        $theme_uri . '/assets/css/ps-invisible-thread.css',
        array('noelclark-v1-ps'),
        noelclark_v1_asset_version('assets/css/ps-invisible-thread.css')
    );

    wp_enqueue_script(
        'noelclark-v1-ps-invisible-thread',
        $theme_uri . '/assets/js/ps-invisible-thread.js',
        array('noelclark-v1-ps-data', 'noelclark-v1-room-tags'),
        noelclark_v1_asset_version('assets/js/ps-invisible-thread.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_ps_invisible_thread_assets');

/**
 * Google Fonts preconnect hints for Contact, Mail Room, Return to Nature, and P.S. pages.
 */
function noelclark_v1_theme_resource_hints($urls, $relation_type) {
    if ($relation_type !== 'preconnect') {
        return $urls;
    }

    if (!noelclark_v1_is_front_page_experience() && !noelclark_v1_is_contact_page() && !noelclark_v1_is_mail_room_page() && !noelclark_v1_is_return_to_nature_page() && !noelclark_v1_is_ps_page() && !noelclark_v1_is_ps_work_page()) {
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

/**
 * Mail Room page document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_mail_room_document_title($parts) {
    if (!noelclark_v1_is_mail_room_page()) {
        return $parts;
    }

    $parts['title'] = 'The Mail Room';
    $parts['site']  = 'Noel Clark';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_mail_room_document_title');

/**
 * Return to Nature page meta description.
 */
function noelclark_v1_return_to_nature_meta_description() {
    if (!noelclark_v1_is_return_to_nature_page()) {
        return;
    }

    echo '<meta name="description" content="Return to Nature by Noèl Clark — a journey through traumatic loss and chronic illness, and the empowering magic of nature.">' . "\n";
}
add_action('wp_head', 'noelclark_v1_return_to_nature_meta_description', 1);

/**
 * Return to Nature page document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_return_to_nature_document_title($parts) {
    if (!noelclark_v1_is_return_to_nature_page()) {
        return $parts;
    }

    $parts['title'] = 'Return to Nature';
    $parts['site']  = 'Noel Clark';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_return_to_nature_document_title');

/**
 * P.S. index document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_ps_document_title($parts) {
    if (!noelclark_v1_is_ps_page()) {
        return $parts;
    }

    $parts['title'] = 'P.S.';
    $parts['site']  = 'Noel Clark';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_ps_document_title');

/**
 * P.S. work-page document title — Magenta Bloom | P.S. | Noel Clark.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function noelclark_v1_ps_work_document_title($parts) {
    if (!noelclark_v1_is_ps_work_page()) {
        return $parts;
    }

    $parts['title'] = get_the_title();
    $parts['site']  = 'P.S. | Noel Clark';

    return $parts;
}
add_filter('document_title_parts', 'noelclark_v1_ps_work_document_title');
