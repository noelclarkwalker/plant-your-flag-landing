<?php
/**
 * Mail Room window scenery — owner-managed inventory (disabled by default).
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NOELCLARK_V1_MAIL_ROOM_SCENES_OPTION', 'noelclark_v1_mail_room_window_scenes');

/**
 * Absolute path to theme Mail Room image root.
 *
 * @return string
 */
function noelclark_v1_mail_room_window_image_root() {
    return trailingslashit(get_template_directory()) . 'assets/images/mail-room/';
}

/**
 * Theme URI for Mail Room images.
 *
 * @return string
 */
function noelclark_v1_mail_room_window_image_uri() {
    return trailingslashit(get_template_directory_uri()) . 'assets/images/mail-room/';
}

/**
 * Seed inventory shipped with the theme (all disabled).
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_mail_room_window_scenes_seed() {
    $path = get_template_directory() . '/inc/mail-room/window-scenes-seed.json';
    if (!is_readable($path)) {
        return array();
    }
    $raw = json_decode((string) file_get_contents($path), true);
    return is_array($raw) ? $raw : array();
}

/**
 * Normalize one scene row.
 *
 * @param array<string, mixed> $scene Raw scene.
 * @return array<string, mixed>|null
 */
function noelclark_v1_mail_room_window_normalize_scene($scene) {
    if (!is_array($scene) || empty($scene['id']) || empty($scene['file'])) {
        return null;
    }

    $pool = isset($scene['pool']) ? sanitize_key($scene['pool']) : 'inventory';
    if ($pool !== 'review-only') {
        $pool = 'inventory';
    }

    $seasons = array();
    if (!empty($scene['seasons']) && is_array($scene['seasons'])) {
        foreach ($scene['seasons'] as $season) {
            $season = sanitize_key($season);
            if (in_array($season, array('all', 'spring', 'summer', 'autumn', 'winter'), true)) {
                $seasons[] = $season;
            }
        }
    }
    if (!$seasons) {
        $seasons = array('all');
    }

    $focal = function ($key) use ($scene) {
        $src = isset($scene[$key]) && is_array($scene[$key]) ? $scene[$key] : array();
        return array(
            'x' => isset($src['x']) ? max(0, min(100, floatval($src['x']))) : 50,
            'y' => isset($src['y']) ? max(0, min(100, floatval($src['y']))) : 45,
        );
    };

    return array(
        'id'            => sanitize_title($scene['id']),
        'internal_name' => isset($scene['internal_name']) ? sanitize_text_field($scene['internal_name']) : sanitize_title($scene['id']),
        'file'          => ltrim(str_replace('\\', '/', (string) $scene['file']), '/'),
        'enabled'       => !empty($scene['enabled']),
        'status'        => isset($scene['status']) ? sanitize_key($scene['status']) : 'review',
        'pool'          => $pool,
        'seasons'       => array_values(array_unique($seasons)),
        'focal_desktop' => $focal('focal_desktop'),
        'focal_mobile'  => $focal('focal_mobile'),
        'focal_tablet'  => $focal('focal_tablet'),
        'attachment_id' => isset($scene['attachment_id']) ? absint($scene['attachment_id']) : 0,
    );
}

/**
 * Stored scenes merged over seed (preserves owner toggles).
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_mail_room_window_scenes_all() {
    $seed = array();
    foreach (noelclark_v1_mail_room_window_scenes_seed() as $row) {
        $norm = noelclark_v1_mail_room_window_normalize_scene($row);
        if ($norm) {
            $seed[$norm['id']] = $norm;
        }
    }

    $stored = get_option(NOELCLARK_V1_MAIL_ROOM_SCENES_OPTION, array());
    if (is_array($stored)) {
        foreach ($stored as $row) {
            $norm = noelclark_v1_mail_room_window_normalize_scene($row);
            if (!$norm) {
                continue;
            }
            if (isset($seed[$norm['id']])) {
                $base = $seed[$norm['id']];
                $base['enabled'] = !empty($norm['enabled']);
                $base['internal_name'] = $norm['internal_name'];
                $base['seasons'] = $norm['seasons'];
                $base['focal_desktop'] = $norm['focal_desktop'];
                $base['focal_mobile'] = $norm['focal_mobile'];
                $base['focal_tablet'] = $norm['focal_tablet'];
                if ($norm['attachment_id']) {
                    $base['attachment_id'] = $norm['attachment_id'];
                }
                /* Never promote review-only into inventory via save. */
                $seed[$norm['id']] = $base;
            } elseif ($norm['pool'] !== 'review-only' && $norm['attachment_id']) {
                $seed[$norm['id']] = $norm;
            }
        }
    }

    return array_values($seed);
}

/**
 * Persist scenes option.
 *
 * @param array<int, array<string, mixed>> $scenes Scenes.
 */
function noelclark_v1_mail_room_window_scenes_save($scenes) {
    $out = array();
    foreach ($scenes as $scene) {
        $norm = noelclark_v1_mail_room_window_normalize_scene($scene);
        if ($norm) {
            $out[] = $norm;
        }
    }
    update_option(NOELCLARK_V1_MAIL_ROOM_SCENES_OPTION, $out, false);
}

/**
 * Public URL for a scene file or attachment.
 *
 * @param array<string, mixed> $scene Scene.
 * @return string
 */
function noelclark_v1_mail_room_window_scene_url($scene) {
    if (!empty($scene['attachment_id'])) {
        $url = wp_get_attachment_image_url((int) $scene['attachment_id'], 'full');
        if ($url) {
            return $url;
        }
    }
    $file = isset($scene['file']) ? $scene['file'] : '';
    if ($file === '') {
        return '';
    }
    $abs = noelclark_v1_mail_room_window_image_root() . $file;
    if (!is_readable($abs)) {
        return '';
    }
    return noelclark_v1_mail_room_window_image_uri() . $file;
}

/**
 * Current northern-hemisphere meteorological season (site local time).
 *
 * @return string spring|summer|autumn|winter
 */
function noelclark_v1_mail_room_window_current_season() {
    $month = (int) current_time('n');
    if ($month >= 3 && $month <= 5) {
        return 'spring';
    }
    if ($month >= 6 && $month <= 8) {
        return 'summer';
    }
    if ($month >= 9 && $month <= 11) {
        return 'autumn';
    }
    return 'winter';
}

/**
 * Whether a scene is eligible for visitor rotation.
 *
 * @param array<string, mixed> $scene Scene.
 * @return bool
 */
function noelclark_v1_mail_room_window_scene_is_eligible($scene) {
    if (empty($scene['enabled'])) {
        return false;
    }
    if (($scene['pool'] ?? '') === 'review-only' || ($scene['status'] ?? '') === 'review-only') {
        return false;
    }
    $seasons = isset($scene['seasons']) && is_array($scene['seasons']) ? $scene['seasons'] : array('all');
    $current = noelclark_v1_mail_room_window_current_season();
    if (!in_array('all', $seasons, true) && !in_array($current, $seasons, true)) {
        return false;
    }
    return noelclark_v1_mail_room_window_scene_url($scene) !== '';
}

/**
 * Eligible enabled scenes for the front end.
 *
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_mail_room_window_scenes_eligible() {
    $eligible = array();
    foreach (noelclark_v1_mail_room_window_scenes_all() as $scene) {
        if (!noelclark_v1_mail_room_window_scene_is_eligible($scene)) {
            continue;
        }
        $url = noelclark_v1_mail_room_window_scene_url($scene);
        $eligible[] = array(
            'id'            => $scene['id'],
            'url'           => $url,
            'seasons'       => $scene['seasons'],
            'focal_desktop' => $scene['focal_desktop'],
            'focal_mobile'  => $scene['focal_mobile'],
            'focal_tablet'  => $scene['focal_tablet'],
        );
    }
    return $eligible;
}

/**
 * Architecture mask URLs.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_window_architecture() {
    $uri = noelclark_v1_mail_room_window_image_uri() . 'architecture/';
    return array(
        'desktop' => $uri . 'desktop-mask.webp',
        'mobile'  => $uri . 'mobile-mask.webp',
        'desktopPng' => $uri . 'desktop-mask.png',
        'mobilePng'  => $uri . 'mobile-mask.png',
    );
}

/**
 * Front-end config localized to JS.
 *
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_window_frontend_config() {
    return array(
        'scenes'       => noelclark_v1_mail_room_window_scenes_eligible(),
        'architecture' => noelclark_v1_mail_room_window_architecture(),
        'storageKey'   => 'noelclark-v1-mail-room-window-last',
        'fallback'     => 'neutral',
    );
}

/**
 * Register admin page.
 */
function noelclark_v1_mail_room_window_admin_menu() {
    add_theme_page(
        'Mail Room Window',
        'Mail Room Window',
        'edit_theme_options',
        'noelclark-mail-room-window',
        'noelclark_v1_mail_room_window_admin_render'
    );
}
add_action('admin_menu', 'noelclark_v1_mail_room_window_admin_menu');

/**
 * Handle admin save.
 */
function noelclark_v1_mail_room_window_admin_save() {
    if (!isset($_POST['noelclark_mail_room_window_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['noelclark_mail_room_window_nonce'])), 'noelclark_mail_room_window_save')) {
        return;
    }
    if (!current_user_can('edit_theme_options')) {
        return;
    }

    $existing = noelclark_v1_mail_room_window_scenes_all();
    $posted   = isset($_POST['scenes']) && is_array($_POST['scenes']) ? wp_unslash($_POST['scenes']) : array();
    $next     = array();

    foreach ($existing as $scene) {
        $id = $scene['id'];
        $row = isset($posted[$id]) && is_array($posted[$id]) ? $posted[$id] : array();
        $scene['internal_name'] = isset($row['internal_name']) ? sanitize_text_field($row['internal_name']) : $scene['internal_name'];
        /*
         * Review-only may be promoted to inventory via admin metadata (no file move).
         * Until promoted, it cannot be enabled for visitor rotation.
         */
        if (!empty($row['promote_inventory']) && ($scene['pool'] ?? '') === 'review-only') {
            $scene['pool'] = 'inventory';
            $scene['status'] = 'review';
        }
        if (($scene['pool'] ?? '') === 'review-only') {
            $scene['enabled'] = false;
        } else {
            $scene['enabled'] = !empty($row['enabled']);
        }
        $seasons = array();
        if (!empty($row['seasons']) && is_array($row['seasons'])) {
            foreach ($row['seasons'] as $season) {
                $season = sanitize_key($season);
                if (in_array($season, array('all', 'spring', 'summer', 'autumn', 'winter'), true)) {
                    $seasons[] = $season;
                }
            }
        }
        $scene['seasons'] = $seasons ? array_values(array_unique($seasons)) : array('all');
        foreach (array('focal_desktop', 'focal_mobile', 'focal_tablet') as $fkey) {
            $scene[$fkey] = array(
                'x' => isset($row[$fkey]['x']) ? max(0, min(100, floatval($row[$fkey]['x']))) : $scene[$fkey]['x'],
                'y' => isset($row[$fkey]['y']) ? max(0, min(100, floatval($row[$fkey]['y']))) : $scene[$fkey]['y'],
            );
        }
        if (array_key_exists('attachment_id', $row)) {
            $scene['attachment_id'] = absint($row['attachment_id']);
        }
        $next[] = $scene;
    }

    /* Optional: add a new Media Library–backed inventory scene. */
    if (!empty($_POST['new_scene_attachment_id'])) {
        $attachment_id = absint($_POST['new_scene_attachment_id']);
        $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'full') : '';
        if ($url) {
            $name = isset($_POST['new_scene_name']) ? sanitize_text_field(wp_unslash($_POST['new_scene_name'])) : '';
            $id_base = $name !== '' ? $name : ('scene-' . $attachment_id);
            $id = sanitize_title($id_base);
            if ($id === '') {
                $id = 'scene-' . $attachment_id;
            }
            $existing_ids = array();
            foreach ($next as $row_scene) {
                $existing_ids[$row_scene['id']] = true;
            }
            $suffix = 2;
            $candidate = $id;
            while (isset($existing_ids[$candidate])) {
                $candidate = $id . '-' . $suffix;
                $suffix++;
            }
            $next[] = noelclark_v1_mail_room_window_normalize_scene(
                array(
                    'id'            => $candidate,
                    'internal_name' => $name !== '' ? $name : $candidate,
                    'file'          => 'media/' . $candidate,
                    'enabled'       => false,
                    'status'        => 'review',
                    'pool'          => 'inventory',
                    'seasons'       => array('all'),
                    'attachment_id' => $attachment_id,
                    'focal_desktop' => array('x' => 50, 'y' => 45),
                    'focal_mobile'  => array('x' => 50, 'y' => 45),
                    'focal_tablet'  => array('x' => 50, 'y' => 45),
                )
            );
        }
    }

    noelclark_v1_mail_room_window_scenes_save(array_filter($next));
    wp_safe_redirect(add_query_arg(array('page' => 'noelclark-mail-room-window', 'updated' => '1'), admin_url('themes.php')));
    exit;
}
add_action('admin_init', 'noelclark_v1_mail_room_window_admin_save');

/**
 * Admin UI.
 */
function noelclark_v1_mail_room_window_admin_render() {
    if (!current_user_can('edit_theme_options')) {
        return;
    }
    $scenes = noelclark_v1_mail_room_window_scenes_all();
    $arch   = noelclark_v1_mail_room_window_architecture();
    ?>
    <div class="wrap">
        <h1>Mail Room Window</h1>
        <p>Manage exterior scenery. All scenes ship <strong>disabled</strong>. Review-only assets cannot enter rotation. Enable only after compositing review.</p>
        <?php if (!empty($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Scenes saved.</p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('noelclark_mail_room_window_save', 'noelclark_mail_room_window_nonce'); ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Internal name</th>
                        <th>Pool</th>
                        <th>Enabled</th>
                        <th>Seasons</th>
                        <th>Desktop focal % (x,y)</th>
                        <th>Mobile focal % (x,y)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($scenes as $scene) :
                    $url = noelclark_v1_mail_room_window_scene_url($scene);
                    $is_review = ($scene['pool'] === 'review-only');
                    $fx = esc_attr($scene['focal_desktop']['x']);
                    $fy = esc_attr($scene['focal_desktop']['y']);
                    ?>
                    <tr>
                        <td style="width:220px">
                            <div style="position:relative;width:200px;height:112px;background:#1a1a1a;overflow:hidden">
                                <?php if ($url) : ?>
                                    <img src="<?php echo esc_url($url); ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:<?php echo $fx; ?>% <?php echo $fy; ?>%" />
                                <?php endif; ?>
                                <img src="<?php echo esc_url($arch['desktop']); ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;pointer-events:none" />
                            </div>
                            <?php if ($is_review) : ?>
                                <p><em>Review-only — excluded from rotation.</em></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="text" name="scenes[<?php echo esc_attr($scene['id']); ?>][internal_name]" value="<?php echo esc_attr($scene['internal_name']); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html($scene['id']); ?></p>
                        </td>
                        <td><?php echo esc_html($scene['pool']); ?></td>
                        <td>
                            <?php if ($is_review) : ?>
                                <input type="checkbox" disabled />
                                <p class="description">Excluded until promoted.</p>
                                <label>
                                    <input type="checkbox" name="scenes[<?php echo esc_attr($scene['id']); ?>][promote_inventory]" value="1" />
                                    Promote to inventory
                                </label>
                            <?php else : ?>
                                <label><input type="checkbox" name="scenes[<?php echo esc_attr($scene['id']); ?>][enabled]" value="1" <?php checked(!empty($scene['enabled'])); ?> /> On</label>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php foreach (array('all', 'spring', 'summer', 'autumn', 'winter') as $season) : ?>
                                <label style="display:block">
                                    <input type="checkbox" name="scenes[<?php echo esc_attr($scene['id']); ?>][seasons][]" value="<?php echo esc_attr($season); ?>" <?php checked(in_array($season, $scene['seasons'], true)); ?> />
                                    <?php echo esc_html(ucfirst($season)); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_desktop][x]" value="<?php echo esc_attr($scene['focal_desktop']['x']); ?>" style="width:4.5rem" />
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_desktop][y]" value="<?php echo esc_attr($scene['focal_desktop']['y']); ?>" style="width:4.5rem" />
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_mobile][x]" value="<?php echo esc_attr($scene['focal_mobile']['x']); ?>" style="width:4.5rem" />
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_mobile][y]" value="<?php echo esc_attr($scene['focal_mobile']['y']); ?>" style="width:4.5rem" />
                            <p class="description">Tablet focal</p>
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_tablet][x]" value="<?php echo esc_attr($scene['focal_tablet']['x']); ?>" style="width:4.5rem" />
                            <input type="number" min="0" max="100" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][focal_tablet][y]" value="<?php echo esc_attr($scene['focal_tablet']['y']); ?>" style="width:4.5rem" />
                            <?php if (!$is_review) : ?>
                                <p class="description">Media Library attachment ID (optional override)</p>
                                <input type="number" min="0" step="1" name="scenes[<?php echo esc_attr($scene['id']); ?>][attachment_id]" value="<?php echo esc_attr((string) (int) $scene['attachment_id']); ?>" style="width:6rem" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button('Save scenes'); ?>

            <h2>Add scenery from Media Library</h2>
            <p>Upload an image in the Media Library first, then enter its attachment ID. New scenes ship disabled.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="new_scene_name">Internal name</label></th>
                    <td><input type="text" class="regular-text" name="new_scene_name" id="new_scene_name" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="new_scene_attachment_id">Attachment ID</label></th>
                    <td><input type="number" min="1" step="1" name="new_scene_attachment_id" id="new_scene_attachment_id" /></td>
                </tr>
            </table>
            <?php submit_button('Add scene', 'secondary'); ?>
        </form>
    </div>
    <?php
}

/**
 * Render the public architectural stage markup.
 * Correspondence and discovery live inside the stage so the sill is one environment.
 */
function noelclark_v1_mail_room_window_render_stage() {
    $arch = noelclark_v1_mail_room_window_architecture();
    $eligible = noelclark_v1_mail_room_window_scenes_eligible();
    $initial = $eligible ? $eligible[array_rand($eligible)] : null;
    $pos = $initial ? $initial['focal_desktop']['x'] . '% ' . $initial['focal_desktop']['y'] . '%' : '50% 45%';
    ?>
    <section class="mail-room-stage" aria-label="The Mail Room">
        <div class="mail-room-stage__frame">
            <div class="mail-room-stage__scenery" data-mail-room-scenery>
                <?php if ($initial) : ?>
                    <img
                        class="mail-room-stage__scenery-img"
                        src="<?php echo esc_url($initial['url']); ?>"
                        alt=""
                        width="1672"
                        height="941"
                        decoding="async"
                        fetchpriority="high"
                        data-scene-id="<?php echo esc_attr($initial['id']); ?>"
                        style="object-position: <?php echo esc_attr($pos); ?>"
                    />
                <?php else : ?>
                    <div class="mail-room-stage__fallback" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <picture class="mail-room-stage__architecture">
                <source media="(max-width: 700px)" srcset="<?php echo esc_url($arch['mobile']); ?>" type="image/webp" />
                <source media="(max-width: 700px)" srcset="<?php echo esc_url($arch['mobilePng']); ?>" type="image/png" />
                <source srcset="<?php echo esc_url($arch['desktop']); ?>" type="image/webp" />
                <img
                    class="mail-room-stage__architecture-img"
                    src="<?php echo esc_url($arch['desktopPng']); ?>"
                    alt=""
                    width="1671"
                    height="941"
                    decoding="async"
                    fetchpriority="high"
                />
            </picture>

            <div class="mail-room-stage__titles">
                <h1 class="mail-room-stage__title">
                    <span class="mail-room-stage__title-line">THE</span>
                    <span class="mail-room-stage__title-line mail-room-stage__title-line--room">MAIL ROOM</span>
                </h1>
            </div>

            <div class="mail-room-stage__sill" aria-labelledby="mail-room-field-heading">
                <h2 class="visually-hidden" id="mail-room-field-heading">Correspondence</h2>

                <div class="mail-room-stage__letters">
                    <button type="button" class="mail-envelope-nav mail-envelope-nav--prev" id="mail-envelope-prev" aria-label="Previous correspondence">
                        <svg class="mail-envelope-nav__icon" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">
                            <path d="M15.2 4.8 L7.8 12 l7.4 7.2" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div
                        class="mail-envelope-rail"
                        id="mail-room-artifacts"
                        tabindex="0"
                        aria-label="Correspondence"
                    ></div>
                    <button type="button" class="mail-envelope-nav mail-envelope-nav--next" id="mail-envelope-next" aria-label="Next correspondence">
                        <svg class="mail-envelope-nav__icon" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false">
                            <path d="M8.8 4.8 L16.2 12 l-7.4 7.2" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>

                <p class="mail-browse__empty" id="mail-browse-empty" hidden>No correspondence matches.</p>
                <button type="button" class="mail-browse__more" id="mail-browse-more" hidden>Load more</button>
            </div>

            <div class="mail-room-stage__chrome" id="mail-browse" aria-label="Search and explore correspondence">
                <div class="mail-explore">
                    <button
                        type="button"
                        class="mail-explore__toggle"
                        id="mail-explore-toggle"
                        aria-expanded="false"
                        aria-controls="mail-browse-discovery"
                    >
                        <span class="mail-explore__glyph" aria-hidden="true">
                            <svg viewBox="0 0 16 16" width="14" height="14" focusable="false">
                                <rect x="1" y="1" width="6" height="6" fill="currentColor"/>
                                <rect x="9" y="1" width="6" height="6" fill="currentColor"/>
                                <rect x="1" y="9" width="6" height="6" fill="currentColor"/>
                                <rect x="9" y="9" width="6" height="6" fill="currentColor"/>
                            </svg>
                        </span>
                        Explore
                    </button>
                    <div class="mail-explore__panel" id="mail-browse-discovery" hidden></div>
                </div>

                <div class="mail-room-discovery__primary" role="group" aria-label="Era">
                    <button type="button" class="mail-era" data-mail-era="all" aria-pressed="true">All correspondence</button>
                    <button type="button" class="mail-era" data-mail-era="contemporary" aria-pressed="false">Contemporary</button>
                    <button type="button" class="mail-era" data-mail-era="historical" aria-pressed="false">Historical</button>
                </div>

                <div class="mail-browse__search">
                    <button
                        type="button"
                        class="mail-browse__search-toggle"
                        id="mail-browse-search-toggle"
                        aria-expanded="false"
                        aria-controls="mail-browse-query"
                        aria-label="Search correspondence"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M15.8 15.8L21 21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <label class="visually-hidden" for="mail-browse-query">Search correspondence</label>
                    <input
                        type="search"
                        id="mail-browse-query"
                        class="mail-browse__search-input"
                        placeholder="Search"
                        autocomplete="off"
                        enterkeyhint="search"
                        hidden
                    />
                </div>

                <button type="button" class="mail-room-write-invite__cta" id="mail-room-send-letter">
                    <span class="mail-room-write-invite__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 14" width="16" height="11" focusable="false">
                            <rect x="1" y="2" width="18" height="11" fill="none" stroke="#ffee08" stroke-width="1.5"/>
                            <path d="M1.5 3.5L10 9L18.5 3.5" fill="none" stroke="#ffee08" stroke-width="1.5" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    WRITE TO THE MAIL ROOM
                </button>

                <p class="mail-browse__active" id="mail-browse-active" hidden>
                    <span id="mail-browse-active-text"></span>
                    <button type="button" class="mail-browse__clear" id="mail-browse-clear">Clear</button>
                </p>
            </div>
        </div>
    </section>
    <?php
}
