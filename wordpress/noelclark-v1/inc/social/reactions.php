<?php
/**
 * Homepage Like + Save global counters.
 *
 * Every accepted request adds exactly 1 to a global, cumulative total. The
 * once-per-page-visit limit is enforced in the browser only; the server keeps no
 * visitor identity, cookies, or uniqueness constraints. There is no Unlike or Unsave.
 *
 * Totals live in two non-autoloaded wp_options rows changed only by direct atomic
 * SQL. They are never read or written through get_option()/update_option(), so the
 * object cache can neither serve nor write back a stale total.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string REST namespace for homepage social counters. */
const NOELCLARK_V1_SOCIAL_REST_NAMESPACE = 'noelclark/v1';

/** @var string REST route returning both totals. */
const NOELCLARK_V1_SOCIAL_REST_TOTALS_ROUTE = '/social';

/** @var string REST route adding 1 to one total. */
const NOELCLARK_V1_SOCIAL_REST_REACTION_ROUTE = '/social/reaction';

/**
 * Option names keyed by reaction.
 *
 * @return array<string, string>
 */
function noelclark_v1_social_option_names() {
    return array(
        'like' => 'noelclark_v1_social_like_total',
        'save' => 'noelclark_v1_social_save_total',
    );
}

/**
 * Read both totals directly from the database.
 *
 * Missing rows read as 0.
 *
 * @return array{likes: int, saves: int}|null Null on database error.
 */
function noelclark_v1_social_read_totals() {
    global $wpdb;

    $names = noelclark_v1_social_option_names();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
            $names['like'],
            $names['save']
        ),
        ARRAY_A
    );

    if ($wpdb->last_error !== '' || !is_array($rows)) {
        return null;
    }

    $totals = array(
        'likes' => 0,
        'saves' => 0,
    );

    foreach ($rows as $row) {
        $value = max(0, (int) $row['option_value']);

        if ($row['option_name'] === $names['like']) {
            $totals['likes'] = $value;
        } elseif ($row['option_name'] === $names['save']) {
            $totals['saves'] = $value;
        }
    }

    return $totals;
}

/**
 * Atomically add 1 to one total.
 *
 * The row lock taken by UPDATE serializes concurrent clicks, and LAST_INSERT_ID(expr)
 * returns this request's exact post-increment value on the same connection.
 *
 * @param string $reaction 'like' or 'save'.
 * @return int|null New total, or null when nothing was counted.
 */
function noelclark_v1_social_increment($reaction) {
    global $wpdb;

    $names = noelclark_v1_social_option_names();

    if (!isset($names[$reaction])) {
        return null;
    }

    $option_name = $names[$reaction];

    $increment_sql = $wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID(CAST(option_value AS UNSIGNED) + 1) WHERE option_name = %s",
        $option_name
    );

    $updated = $wpdb->query($increment_sql);

    if ($updated === 0) {
        $seeded = $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '0', 'no')",
                $option_name
            )
        );

        if ($seeded === false) {
            return null;
        }

        $updated = $wpdb->query($increment_sql);
    }

    if ($updated !== 1) {
        return null;
    }

    $total = $wpdb->get_var('SELECT LAST_INSERT_ID()');

    if ($wpdb->last_error !== '' || $total === null) {
        return null;
    }

    return (int) $total;
}

/**
 * Whether a request's Origin (or Referer) belongs to this site.
 *
 * Absent headers are allowed. This blocks other sites from inflating totals through
 * their visitors' browsers; it is a filter, not an identity check.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function noelclark_v1_social_request_is_same_site(WP_REST_Request $request) {
    $source = $request->get_header('origin');

    if (!is_string($source) || $source === '') {
        $source = $request->get_header('referer');
    }

    if (!is_string($source) || $source === '') {
        return true;
    }

    $source_host = wp_parse_url($source, PHP_URL_HOST);

    if (!is_string($source_host) || $source_host === '') {
        return false;
    }

    $source_host = strtolower($source_host);

    foreach (array(home_url(), site_url()) as $site) {
        $site_host = wp_parse_url($site, PHP_URL_HOST);

        if (is_string($site_host) && strtolower($site_host) === $source_host) {
            return true;
        }
    }

    return false;
}

/**
 * Build a JSON response that edge and browser caches must not store.
 *
 * @param array $data   Response body.
 * @param int   $status HTTP status.
 * @return WP_REST_Response
 */
function noelclark_v1_social_response($data, $status = 200) {
    $response = new WP_REST_Response($data, $status);

    foreach (wp_get_nocache_headers() as $name => $value) {
        if (is_string($value) && $value !== '') {
            $response->header($name, $value);
        }
    }

    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

    return $response;
}

/**
 * REST callback: return both totals.
 *
 * @return WP_REST_Response
 */
function noelclark_v1_social_rest_get_totals() {
    $totals = noelclark_v1_social_read_totals();

    if ($totals === null) {
        return noelclark_v1_social_response(array('code' => 'unavailable'), 503);
    }

    return noelclark_v1_social_response($totals);
}

/**
 * REST callback: add 1 to the requested total.
 *
 * Body must be exactly {"reaction": "like"|"save"}.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function noelclark_v1_social_rest_add_reaction(WP_REST_Request $request) {
    if (!noelclark_v1_social_request_is_same_site($request)) {
        return noelclark_v1_social_response(array('code' => 'forbidden'), 403);
    }

    $params = $request->get_json_params();

    if (
        !is_array($params)
        || array_keys($params) !== array('reaction')
        || !is_string($params['reaction'])
        || !array_key_exists($params['reaction'], noelclark_v1_social_option_names())
    ) {
        return noelclark_v1_social_response(array('code' => 'invalid_reaction'), 400);
    }

    $reaction = $params['reaction'];
    $total    = noelclark_v1_social_increment($reaction);

    if ($total === null) {
        return noelclark_v1_social_response(array('code' => 'unavailable'), 503);
    }

    return noelclark_v1_social_response(
        array(
            'reaction' => $reaction,
            'total'    => $total,
        )
    );
}

/**
 * Register homepage social REST routes.
 *
 * @return void
 */
function noelclark_v1_social_register_rest_routes() {
    register_rest_route(
        NOELCLARK_V1_SOCIAL_REST_NAMESPACE,
        NOELCLARK_V1_SOCIAL_REST_TOTALS_ROUTE,
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'noelclark_v1_social_rest_get_totals',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        NOELCLARK_V1_SOCIAL_REST_NAMESPACE,
        NOELCLARK_V1_SOCIAL_REST_REACTION_ROUTE,
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'noelclark_v1_social_rest_add_reaction',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'noelclark_v1_social_register_rest_routes');

/**
 * Localized frontend config for the homepage Like + Save script.
 *
 * @return array<string, string>
 */
function noelclark_v1_social_frontend_config() {
    return array(
        'totalsUrl'   => esc_url_raw(rest_url(NOELCLARK_V1_SOCIAL_REST_NAMESPACE . NOELCLARK_V1_SOCIAL_REST_TOTALS_ROUTE)),
        'reactionUrl' => esc_url_raw(rest_url(NOELCLARK_V1_SOCIAL_REST_NAMESPACE . NOELCLARK_V1_SOCIAL_REST_REACTION_ROUTE)),
    );
}
