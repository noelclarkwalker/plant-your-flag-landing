<?php
/**
 * P.S. journey + unified archive content bridge.
 *
 * Maps the approved journey UI and the below-hero archive to existing
 * WordPress Pages / page templates. Does not introduce a CPT.
 * Does not overwrite ps-data.js thematic tags.
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
 * Seed catalog of known P.S. works (template-backed Pages architecture).
 * Presentation hints support the unified archive; members-only entries
 * stay listed without public hrefs.
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_journey_catalog() {
    $uri = get_template_directory_uri();

    return array(
        array(
            'id'       => 'ps-work-returning-open-journal',
            'slug'     => 'returning-to-the-open-journal',
            'medium'   => 'writing',
            'format'   => 'essay',
            'access'   => 'public',
            'title'    => 'Returning to the Open Journal',
            'summary'  => 'I stopped blogging a few years ago.',
            'sub'      => '',
            'template' => 'page-ps-returning-to-the-open-journal.php',
            'image'    => $uri . '/assets/images/lavender-on-keyboard.PNG',
            'cta'      => 'Read',
            'search'   => 'blogging open journal instagram captions anxiety intuition God writing 2010',
        ),
        array(
            'id'       => 'ps-preview-invisible-thread',
            'slug'     => 'the-invisible-thread',
            'medium'   => 'writing',
            'format'   => 'essay',
            'access'   => 'public',
            'title'    => 'The Invisible Thread',
            'summary'  => '',
            'sub'      => '',
            'template' => 'page-ps-the-invisible-thread.php',
            'image'    => $uri . '/assets/images/invisible-thread-ego-fitted.png',
            'cta'      => 'Read',
            'search'   => 'ego vulnerability Jay-Z Amy Winehouse music accountability thread',
        ),
        array(
            'id'       => 'ps-work-hell-its-forever-lit',
            'slug'     => 'hell-its-forever-lit',
            'medium'   => 'music',
            'format'   => 'music',
            'access'   => 'public',
            'title'    => "Hell (It's Forever Lit)",
            'summary'  => '',
            'sub'      => "Walker's Wraith",
            'template' => 'page-ps-hell-its-forever-lit.php',
            'image'    => '',
            'video'    => $uri . '/assets/video/forever-lit-walkers-wraith.mp4',
            'cta'      => 'Play',
            'search'   => 'Walker Wraith Halloween costume party original song lyric video forever lit',
        ),
        array(
            'id'       => 'ps-work-magenta-bloom',
            'slug'     => 'magenta-bloom',
            'medium'   => 'art',
            'format'   => 'photography',
            'access'   => 'public',
            'title'    => 'Magenta Bloom',
            'summary'  => '',
            'sub'      => '',
            'template' => 'page-ps-magenta-bloom.php',
            'image'    => $uri . '/assets/images/flower-compound.JPG',
            'cta'      => 'View',
            'search'   => 'flower bloom magenta photograph',
        ),
        array(
            'id'       => 'ps-work-observing-a-butterfly',
            'slug'     => 'observing-a-butterfly',
            'medium'   => 'art',
            'format'   => 'video',
            'access'   => 'public',
            'title'    => 'Observing a Butterfly',
            'summary'  => '',
            'sub'      => '',
            'template' => 'page-ps-observing-a-butterfly.php',
            'image'    => '',
            'video'    => $uri . '/assets/video/observing-a-butterfly.MP4',
            'cta'      => 'Watch',
            'search'   => 'butterfly motion watch nature',
        ),
        array(
            'id'       => 'ps-work-lavender-nostalgia',
            'slug'     => 'lavender-nostalgia',
            'medium'   => 'art',
            'format'   => 'photography',
            'access'   => 'members',
            'title'    => 'Lavender Nostalgia',
            'summary'  => '',
            'sub'      => '',
            'template' => '',
            'image'    => $uri . '/assets/images/lavender-nostalgia.JPG',
            'cta'      => 'View',
            'search'   => 'lavender nostalgia photograph',
        ),
        array(
            'id'       => 'ps-work-out-the-windows',
            'slug'     => 'out-the-windows',
            'medium'   => 'art',
            'format'   => 'photography',
            'access'   => 'members',
            'title'    => 'Out the Windows',
            'summary'  => '',
            'sub'      => '',
            'template' => '',
            'image'    => $uri . '/assets/images/out-the-windows.JPG',
            'cta'      => 'View',
            'search'   => 'windows view photograph',
        ),
    );
}

/**
 * Resolve a published, publicly readable WordPress page for a P.S. work slug.
 * Draft, private, and password-protected pages are not returned.
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
        if ($page instanceof WP_Post && noelclark_v1_ps_page_is_publicly_readable($page)) {
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

    if ($query->have_posts() && noelclark_v1_ps_page_is_publicly_readable($query->posts[0])) {
        return $query->posts[0];
    }

    return null;
}

/**
 * Whether a page may appear in the public P.S. archive / journey shelf.
 *
 * @param WP_Post $page Page.
 * @return bool
 */
function noelclark_v1_ps_page_is_publicly_readable($page) {
    if (!$page instanceof WP_Post || $page->post_type !== 'page') {
        return false;
    }
    if ($page->post_status !== 'publish') {
        return false;
    }
    if ($page->post_password !== '') {
        return false;
    }
    return true;
}

/**
 * Medium label for archive UI.
 *
 * @param string $medium writing|music|art.
 * @return string
 */
function noelclark_v1_ps_medium_label($medium) {
    $map = array(
        'writing' => 'Writing',
        'music'   => 'Music',
        'art'     => 'Art',
    );
    return isset($map[$medium]) ? $map[$medium] : ucfirst((string) $medium);
}

/**
 * CTA label for a work.
 *
 * @param array<string, mixed> $work Work row.
 * @return string
 */
function noelclark_v1_ps_work_cta($work) {
    if (!empty($work['cta'])) {
        return (string) $work['cta'];
    }
    if (($work['medium'] ?? '') === 'writing') {
        return 'Read';
    }
    if (($work['medium'] ?? '') === 'music') {
        return 'Play';
    }
    if (($work['format'] ?? '') === 'video') {
        return 'Watch';
    }
    return 'View';
}

/**
 * Normalize categories from meta or assignment list.
 *
 * @param mixed $raw Raw categories.
 * @return string[]
 */
function noelclark_v1_ps_normalize_categories($raw) {
    if (is_string($raw)) {
        $raw = preg_split('/\s*,\s*/', $raw) ?: array();
    }
    if (!is_array($raw)) {
        return array();
    }
    $out = array();
    foreach ($raw as $label) {
        $label = trim((string) $label);
        if ($label !== '' && !in_array($label, $out, true)) {
            $out[] = $label;
        }
    }
    return $out;
}

/**
 * Build a work row from catalog seed + optional live page.
 *
 * @param array<string, mixed> $item Catalog item.
 * @return array<string, mixed>|null
 */
function noelclark_v1_ps_build_work_from_catalog($item) {
    $assignments = noelclark_v1_ps_journey_category_assignments();
    $access      = isset($item['access']) ? $item['access'] : 'public';
    $page        = null;
    $href        = '';

    if ($access === 'public') {
        $page = noelclark_v1_ps_journey_resolve_page($item['slug']);
        if ($page instanceof WP_Post) {
            $href  = get_permalink($page);
            $title = get_the_title($page);
            if (is_string($title) && $title !== '') {
                $item['title'] = $title;
            }
            $summary = get_post_meta($page->ID, '_ps_summary', true);
            if (is_string($summary) && $summary !== '') {
                $item['summary'] = $summary;
            }
            $meta_cats = get_post_meta($page->ID, '_ps_categories', true);
            if ($meta_cats) {
                $item['categories'] = noelclark_v1_ps_normalize_categories($meta_cats);
            }
            $thumb = get_the_post_thumbnail_url($page, 'large');
            if (is_string($thumb) && $thumb !== '') {
                $item['image'] = $thumb;
            }
        } else {
            $template = isset($item['template']) ? (string) $item['template'] : '';
            if ($template !== '') {
                $path = get_template_directory() . '/' . $template;
                if (!is_readable($path)) {
                    return null;
                }
            }
            $href = home_url('/ps/' . $item['slug'] . '/');
        }
    }

    $categories = isset($item['categories'])
        ? noelclark_v1_ps_normalize_categories($item['categories'])
        : (isset($assignments[$item['id']]) ? $assignments[$item['id']] : array());

    return array(
        'id'         => $item['id'],
        'slug'       => $item['slug'],
        'medium'     => $item['medium'],
        'format'     => isset($item['format']) ? $item['format'] : '',
        'access'     => $access,
        'title'      => $item['title'],
        'summary'    => isset($item['summary']) ? $item['summary'] : '',
        'sub'        => isset($item['sub']) ? $item['sub'] : '',
        'categories' => $categories,
        'href'       => $href,
        'image'      => isset($item['image']) ? $item['image'] : '',
        'video'      => isset($item['video']) ? $item['video'] : '',
        'cta'        => noelclark_v1_ps_work_cta($item),
        'search'     => isset($item['search']) ? $item['search'] : '',
        'source'     => 'catalog',
    );
}

/**
 * Discover published Pages marked with P.S. work meta (owner-managed, no code edit).
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_discover_meta_works() {
    $query = new WP_Query(
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => '_ps_medium',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    $works = array();
    foreach ($query->posts as $page) {
        if (!noelclark_v1_ps_page_is_publicly_readable($page)) {
            continue;
        }

        $medium = sanitize_key((string) get_post_meta($page->ID, '_ps_medium', true));
        if (!in_array($medium, array('writing', 'music', 'art'), true)) {
            continue;
        }

        $access = sanitize_key((string) get_post_meta($page->ID, '_ps_access', true));
        if ($access === '') {
            $access = 'public';
        }
        if ($access === 'members') {
            /* Members works managed via meta are listed without a public href. */
            $href = '';
        } else {
            $href = get_permalink($page);
        }

        $format = sanitize_key((string) get_post_meta($page->ID, '_ps_format', true));
        if ($format === '') {
            $format = $medium === 'writing' ? 'essay' : ($medium === 'music' ? 'music' : 'photography');
        }

        $thumb = get_the_post_thumbnail_url($page, 'large');

        $works[] = array(
            'id'         => 'ps-page-' . $page->ID,
            'slug'       => $page->post_name,
            'medium'     => $medium,
            'format'     => $format,
            'access'     => $access,
            'title'      => get_the_title($page),
            'summary'    => (string) get_post_meta($page->ID, '_ps_summary', true),
            'sub'        => (string) get_post_meta($page->ID, '_ps_sub', true),
            'categories' => noelclark_v1_ps_normalize_categories(get_post_meta($page->ID, '_ps_categories', true)),
            'href'       => is_string($href) ? $href : '',
            'image'      => is_string($thumb) ? $thumb : '',
            'video'      => '',
            'cta'        => noelclark_v1_ps_work_cta(array('medium' => $medium, 'format' => $format)),
            'search'     => wp_strip_all_tags(get_the_excerpt($page) . ' ' . $page->post_title),
            'source'     => 'meta',
            'page_id'    => $page->ID,
        );
    }

    return $works;
}

/**
 * Full archive list: catalog seed + owner-tagged Pages, de-duplicated by slug.
 * Public journey shelf uses the public subset only.
 *
 * @param string $mode 'archive' (public + members stubs) | 'journey' (public with href only).
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_archive_works($mode = 'archive') {
    $by_slug = array();

    foreach (noelclark_v1_ps_journey_catalog() as $item) {
        $work = noelclark_v1_ps_build_work_from_catalog($item);
        if (!$work) {
            continue;
        }
        $by_slug[$work['slug']] = $work;
    }

    foreach (noelclark_v1_ps_discover_meta_works() as $work) {
        if (isset($by_slug[$work['slug']])) {
            /* Live meta enriches the seed without duplicating. */
            $seed = $by_slug[$work['slug']];
            if ($work['summary'] !== '') {
                $seed['summary'] = $work['summary'];
            }
            if ($work['categories']) {
                $seed['categories'] = $work['categories'];
            }
            if ($work['image'] !== '') {
                $seed['image'] = $work['image'];
            }
            if ($work['href'] !== '') {
                $seed['href'] = $work['href'];
            }
            $by_slug[$work['slug']] = $seed;
            continue;
        }
        $by_slug[$work['slug']] = $work;
    }

    $works = array_values($by_slug);

    if ($mode === 'journey') {
        $works = array_values(
            array_filter(
                $works,
                function ($work) {
                    return ($work['access'] === 'public') && !empty($work['href']);
                }
            )
        );
    }

    return $works;
}

/**
 * Build public journey works for the P.S. index shelf.
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_ps_journey_public_works() {
    return noelclark_v1_ps_archive_works('journey');
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

/**
 * Render one unified archive entry.
 *
 * @param array<string, mixed> $work  Work row.
 * @param int                  $index Index for alternating layout.
 */
function noelclark_v1_ps_archive_render_entry($work, $index = 0) {
    $reverse   = ($index % 2) === 1;
    $body_class = 'ps-preview__body' . ($reverse ? ' ps-preview__body--reverse' : '');
    $medium    = noelclark_v1_ps_medium_label($work['medium']);
    $cats      = $work['categories'];
    $access    = $work['access'];
    $cta       = strtoupper($work['cta']) . ' →';
    $has_media = ($work['video'] !== '') || ($work['image'] !== '');

    printf(
        '<article class="ps-preview ps-preview--archive" id="%s" data-work-id="%s" data-medium="%s" data-format="%s" data-access="%s" data-categories="%s" data-search="%s">' . "\n",
        esc_attr($work['id']),
        esc_attr($work['id']),
        esc_attr($work['medium']),
        esc_attr($work['format']),
        esc_attr($access),
        esc_attr(implode(',', $cats)),
        esc_attr($work['title'] . ' ' . $work['summary'] . ' ' . $work['sub'] . ' ' . $work['search'] . ' ' . implode(' ', $cats))
    );

    echo '  <div class="' . esc_attr($body_class) . '">' . "\n";
    echo '    <div class="ps-preview__primary">' . "\n";
    echo '      <p class="ps-preview__medium">' . esc_html($medium) . '</p>' . "\n";
    echo '      <h2 class="ps-preview__title">' . esc_html(strtoupper($work['title'])) . '</h2>' . "\n";

    if ($work['sub'] !== '') {
        echo '      <p class="ps-preview__sub">' . esc_html($work['sub']) . '</p>' . "\n";
    }
    if ($work['summary'] !== '') {
        echo '      <p class="ps-preview__lead">' . esc_html($work['summary']) . '</p>' . "\n";
    }
    if ($cats) {
        echo '      <p class="ps-preview__cats">' . esc_html(implode(' · ', $cats)) . '</p>' . "\n";
    }

    if ($access === 'members') {
        echo '      <button type="button" class="ps-preview__access ps-preview__access--members" data-members-prototype="' . esc_attr($work['id']) . '">';
        echo '<span class="ps-preview__access-label">Members only</span></button>' . "\n";
    } elseif ($work['href'] !== '') {
        $class = 'ps-preview__read';
        if ($work['medium'] === 'music') {
            $class = 'ps-preview__listen';
        } elseif (($work['format'] ?? '') === 'video') {
            $class = 'ps-preview__watch';
        } elseif ($work['medium'] === 'art') {
            $class = 'ps-preview__view';
        }
        printf(
            '      <a class="%s" href="%s">%s</a>' . "\n",
            esc_attr($class),
            esc_url($work['href']),
            esc_html($cta)
        );
    }

    echo '    </div>' . "\n";

    if ($has_media) {
        echo '    <figure class="ps-preview__visual ps-preview__visual--single">' . "\n";
        if ($work['video'] !== '') {
            printf(
                '      <video class="ps-preview__video" muted playsinline loop autoplay preload="metadata"><source src="%s" type="video/mp4" /></video>' . "\n",
                esc_url($work['video'])
            );
        } else {
            printf(
                '      <img src="%s" alt="" loading="lazy" />' . "\n",
                esc_url($work['image'])
            );
        }
        echo '    </figure>' . "\n";
    }

    echo '  </div>' . "\n";
    echo '</article>' . "\n";
}

/**
 * Render the unified archive collection.
 * Medium / category filtering lives in the P.S. trail and journey tags above —
 * not duplicated beneath the hero.
 */
function noelclark_v1_ps_archive_render() {
    $works = noelclark_v1_ps_archive_works('archive');

    echo '<p class="ps-browse__empty" id="ps-browse-empty" hidden>Nothing in P.S. matches that.</p>' . "\n";
    echo '<div class="ps-collection" id="ps-archive-collection">' . "\n";
    foreach ($works as $i => $work) {
        noelclark_v1_ps_archive_render_entry($work, $i);
    }
    echo '</div>' . "\n";
}

/**
 * Register P.S. work meta for owner-managed Pages (no CPT).
 */
function noelclark_v1_ps_register_work_meta() {
    $keys = array(
        '_ps_medium'     => 'string',
        '_ps_format'     => 'string',
        '_ps_access'     => 'string',
        '_ps_summary'    => 'string',
        '_ps_sub'        => 'string',
        '_ps_categories' => 'string',
    );

    foreach ($keys as $key => $type) {
        register_post_meta(
            'page',
            $key,
            array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'auth_callback'     => function () {
                    return current_user_can('edit_pages');
                },
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }
}
add_action('init', 'noelclark_v1_ps_register_work_meta');

/**
 * Meta box so the owner can add Writing/Music/Art pages without editing code.
 */
function noelclark_v1_ps_add_work_meta_box() {
    add_meta_box(
        'noelclark_v1_ps_work',
        'P.S. Archive',
        'noelclark_v1_ps_render_work_meta_box',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'noelclark_v1_ps_add_work_meta_box');

/**
 * @param WP_Post $post Page.
 */
function noelclark_v1_ps_render_work_meta_box($post) {
    wp_nonce_field('noelclark_v1_ps_work_meta', 'noelclark_v1_ps_work_nonce');

    $medium  = (string) get_post_meta($post->ID, '_ps_medium', true);
    $access  = (string) get_post_meta($post->ID, '_ps_access', true);
    $format  = (string) get_post_meta($post->ID, '_ps_format', true);
    $summary = (string) get_post_meta($post->ID, '_ps_summary', true);
    $sub     = (string) get_post_meta($post->ID, '_ps_sub', true);
    $cats    = (string) get_post_meta($post->ID, '_ps_categories', true);
    $labels  = noelclark_v1_ps_journey_category_labels();

    echo '<p><label for="ps_medium"><strong>Medium</strong></label><br />';
    echo '<select name="ps_medium" id="ps_medium">';
    echo '<option value="">— Not in P.S. archive —</option>';
    foreach (array('writing' => 'Writing', 'music' => 'Music', 'art' => 'Art') as $value => $label) {
        printf('<option value="%s"%s>%s</option>', esc_attr($value), selected($medium, $value, false), esc_html($label));
    }
    echo '</select></p>';

    echo '<p><label for="ps_access"><strong>Access</strong></label><br />';
    echo '<select name="ps_access" id="ps_access">';
    printf('<option value="public"%s>Public</option>', selected($access === '' || $access === 'public', true, false));
    printf('<option value="members"%s>Members only</option>', selected($access, 'members', false));
    echo '</select></p>';

    echo '<p><label for="ps_format"><strong>Format</strong> (optional)</label><br />';
    echo '<input type="text" class="widefat" name="ps_format" id="ps_format" value="' . esc_attr($format) . '" placeholder="essay, music, photography, video" /></p>';

    echo '<p><label for="ps_summary"><strong>Summary</strong></label><br />';
    echo '<textarea class="widefat" rows="3" name="ps_summary" id="ps_summary">' . esc_textarea($summary) . '</textarea></p>';

    echo '<p><label for="ps_sub"><strong>Subtitle</strong></label><br />';
    echo '<input type="text" class="widefat" name="ps_sub" id="ps_sub" value="' . esc_attr($sub) . '" /></p>';

    echo '<p><label for="ps_categories"><strong>Categories</strong></label><br />';
    echo '<input type="text" class="widefat" name="ps_categories" id="ps_categories" value="' . esc_attr($cats) . '" placeholder="Comma-separated" /></p>';
    echo '<p class="description">Writing: ' . esc_html(implode(', ', $labels['writing'])) . '<br />';
    echo 'Music: ' . esc_html(implode(', ', $labels['music'])) . '<br />';
    echo 'Art: ' . esc_html(implode(', ', $labels['art'])) . '</p>';
    echo '<p class="description">Published pages with a Medium set appear in the P.S. archive automatically. Draft, private, and password-protected pages stay out.</p>';
}

/**
 * Save P.S. archive meta from the page editor.
 *
 * @param int $post_id Post ID.
 */
function noelclark_v1_ps_save_work_meta($post_id) {
    if (!isset($_POST['noelclark_v1_ps_work_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['noelclark_v1_ps_work_nonce'])), 'noelclark_v1_ps_work_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    $medium = isset($_POST['ps_medium']) ? sanitize_key(wp_unslash($_POST['ps_medium'])) : '';
    if ($medium === '') {
        delete_post_meta($post_id, '_ps_medium');
    } elseif (in_array($medium, array('writing', 'music', 'art'), true)) {
        update_post_meta($post_id, '_ps_medium', $medium);
    }

    $access = isset($_POST['ps_access']) ? sanitize_key(wp_unslash($_POST['ps_access'])) : 'public';
    update_post_meta($post_id, '_ps_access', $access === 'members' ? 'members' : 'public');

    $map = array(
        'ps_format'     => '_ps_format',
        'ps_summary'    => '_ps_summary',
        'ps_sub'        => '_ps_sub',
        'ps_categories' => '_ps_categories',
    );
    foreach ($map as $field => $meta_key) {
        if (!isset($_POST[$field])) {
            continue;
        }
        $value = sanitize_text_field(wp_unslash($_POST[$field]));
        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
}
add_action('save_post_page', 'noelclark_v1_ps_save_work_meta');
