<?php
/**
 * P.S. journey content bridge — Phase 2.
 *
 * Maps the approved journey UI to existing WordPress Pages / page templates.
 * Does not introduce a CPT. Does not overwrite ps-data.js thematic tags.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Owner-approved journey browsing category labels by medium.
 * Available for future WordPress content management. Does not
 * reclassify existing works — assignments are separate.
 *
 * @return array<string, string[]> Map of medium => category labels (excluding All).
 */
function noelclark_v1_ps_journey_category_labels() {
    return array(
        'writing' => array('Discoveries', 'Experiences', 'Creations', 'Reflections'),
        'music'   => array('Holiday', 'Inspired by TMR'),
        'art'     => array('Paintings', 'Digital Art', 'Photography'),
    );
}

/**
 * Owner-approved journey browsing category assignments.
 * Only explicitly approved mappings live here.
 * Historical thematic tags in ps-data.js are preserved separately.
 *
 * @return array<string, string[]> Map of work id => category labels.
 */
function noelclark_v1_ps_journey_category_assignments() {
    return array(
        'ps-work-hell-its-forever-lit' => array('Holiday'),
    );
}

/**
 * Public journey catalog (template-backed Pages architecture).
 * Members-only / unpublished works are omitted.
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_journey_catalog() {
    return array(
        array(
            'id'         => 'ps-work-returning-open-journal',
            'slug'       => 'returning-to-the-open-journal',
            'medium'     => 'writing',
            'title'      => 'Returning to the Open Journal',
            'summary'    => 'I stopped blogging a few years ago.',
            'sub'        => '',
            'template'   => 'page-ps-returning-to-the-open-journal.php',
        ),
        array(
            'id'         => 'ps-preview-invisible-thread',
            'slug'       => 'the-invisible-thread',
            'medium'     => 'writing',
            'title'      => 'The Invisible Thread',
            'summary'    => '',
            'sub'        => '',
            'template'   => 'page-ps-the-invisible-thread.php',
        ),
        array(
            'id'         => 'ps-work-hell-its-forever-lit',
            'slug'       => 'hell-its-forever-lit',
            'medium'     => 'music',
            'title'      => "Hell (It's Forever Lit)",
            'summary'    => '',
            'sub'        => "Walker's Wraith",
            'template'   => 'page-ps-hell-its-forever-lit.php',
        ),
        array(
            'id'         => 'ps-work-magenta-bloom',
            'slug'       => 'magenta-bloom',
            'medium'     => 'art',
            'title'      => 'Magenta Bloom',
            'summary'    => '',
            'sub'        => '',
            'template'   => 'page-ps-magenta-bloom.php',
        ),
        array(
            'id'         => 'ps-work-observing-a-butterfly',
            'slug'       => 'observing-a-butterfly',
            'medium'     => 'art',
            'title'      => 'Observing a Butterfly',
            'summary'    => '',
            'sub'        => '',
            'template'   => 'page-ps-observing-a-butterfly.php',
        ),
    );
}

/**
 * Resolve a published WordPress page for a P.S. work slug.
 *
 * @param string $slug Page slug under /ps/.
 * @return WP_Post|null
 */
function noelclark_v1_ps_journey_resolve_page($slug) {
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return null;
    }

    $candidates = array(
        'ps/' . $slug,
        $slug,
    );

    foreach ($candidates as $path) {
        $page = get_page_by_path($path);
        if ($page instanceof WP_Post && $page->post_status === 'publish' && $page->post_type === 'page') {
            return $page;
        }
    }

    $query = new WP_Query(
        array(
            'name'           => $slug,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        )
    );

    if ($query->have_posts()) {
        return $query->posts[0];
    }

    return null;
}

/**
 * Build public journey works for the P.S. index.
 * Prefers live published Pages; falls back to template-backed public URLs
 * when the page is not yet present in the local database.
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_journey_public_works() {
    $assignments = noelclark_v1_ps_journey_category_assignments();
    $works       = array();

    foreach (noelclark_v1_ps_journey_catalog() as $item) {
        $page = noelclark_v1_ps_journey_resolve_page($item['slug']);
        $href = '';

        if ($page instanceof WP_Post) {
            $href  = get_permalink($page);
            $title = get_the_title($page);
            if (is_string($title) && $title !== '') {
                $item['title'] = $title;
            }
        } else {
            $template = get_template_directory() . '/' . $item['template'];
            if (!is_readable($template)) {
                continue;
            }
            $href = home_url('/ps/' . $item['slug'] . '/');
        }

        if (!$href) {
            continue;
        }

        $works[] = array(
            'id'         => $item['id'],
            'medium'     => $item['medium'],
            'title'      => $item['title'],
            'summary'    => $item['summary'],
            'sub'        => $item['sub'],
            'categories' => isset($assignments[$item['id']]) ? $assignments[$item['id']] : array(),
            'href'       => $href,
        );
    }

    return $works;
}

/**
 * Render #ps-journey-data nodes for the journey JS bridge.
 */
function noelclark_v1_ps_journey_render_data() {
    $works = noelclark_v1_ps_journey_public_works();
    echo '<div id="ps-journey-data" hidden>' . "\n";
    foreach ($works as $work) {
        printf(
            '<span class="ps-journey-work" data-id="%s" data-medium="%s" data-title="%s" data-summary="%s" data-sub="%s" data-categories="%s" data-href="%s"></span>' . "\n",
            esc_attr($work['id']),
            esc_attr($work['medium']),
            esc_attr($work['title']),
            esc_attr($work['summary']),
            esc_attr($work['sub']),
            esc_attr(implode(',', $work['categories'])),
            esc_url($work['href'])
        );
    }
    echo '</div>' . "\n";
}
