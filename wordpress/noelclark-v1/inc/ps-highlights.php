<?php
/**
 * P.S. reader highlights — Phase 1 private pilot (Open Journal only).
 *
 * Stores moderated submissions in wp_comments + wp_commentmeta.
 * No public list endpoint in Phase 1.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

const NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE = 'noelclark/v1';
const NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE     = '/ps-highlights';
const NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION   = 'noelclark_v1_ps_highlights';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG      = '_noelclark_ps_highlight';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE   = '_noelclark_ps_passage';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_PREFIX    = '_noelclark_ps_prefix';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_SUFFIX    = '_noelclark_ps_suffix';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE      = '_noelclark_ps_submission_type';
const NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX      = 2000;
const NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX         = 1000;
const NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX = 40;
const NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX      = 48;
const NOELCLARK_V1_PS_HIGHLIGHTS_RATE_MAX         = 5;
const NOELCLARK_V1_PS_HIGHLIGHTS_RATE_WINDOW      = HOUR_IN_SECONDS;

/**
 * Pilot page only: Returning to the Open Journal.
 *
 * @return bool
 */
function noelclark_v1_is_ps_highlights_pilot_page() {
    return is_page_template('page-ps-returning-to-the-open-journal.php');
}

/**
 * Pilot template slug (single allowed essay).
 *
 * @return string
 */
function noelclark_v1_ps_highlights_pilot_template() {
    return 'page-ps-returning-to-the-open-journal.php';
}

/**
 * Resolve the pilot essay page ID when known.
 *
 * @return int
 */
function noelclark_v1_ps_highlights_pilot_page_id() {
    if (noelclark_v1_is_ps_highlights_pilot_page()) {
        return (int) get_queried_object_id();
    }

    $page = get_page_by_path('returning-to-the-open-journal');

    if ($page instanceof WP_Post && get_page_template_slug($page) === noelclark_v1_ps_highlights_pilot_template()) {
        return (int) $page->ID;
    }

    return 0;
}

/**
 * Whether a page ID is the allowed pilot essay.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function noelclark_v1_ps_highlights_is_allowed_page_id($page_id) {
    $page_id = (int) $page_id;

    if ($page_id <= 0) {
        return false;
    }

    $page = get_post($page_id);

    if (!$page instanceof WP_Post || $page->post_type !== 'page') {
        return false;
    }

    if (get_page_template_slug($page) !== noelclark_v1_ps_highlights_pilot_template()) {
        return false;
    }

    $pilot_id = noelclark_v1_ps_highlights_pilot_page_id();

    return $pilot_id > 0 && $page_id === $pilot_id;
}

/**
 * Normalized plaintext of the pilot essay body (from the template file).
 *
 * Essay copy is template-rendered, not post_content — read the pilot template
 * and extract the highlightable root markup.
 *
 * @return string
 */
function noelclark_v1_ps_highlights_pilot_essay_plaintext() {
    $path = get_template_directory() . '/' . noelclark_v1_ps_highlights_pilot_template();

    if (!is_readable($path)) {
        return '';
    }

    $raw = file_get_contents($path);

    if (!is_string($raw) || $raw === '') {
        return '';
    }

    if (!preg_match('/data-ps-highlights-root[^>]*>([\s\S]*?)<div[^>]*data-ps-tags/i', $raw, $matches)) {
        return '';
    }

    $html = $matches[1];
    $text = wp_strip_all_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim((string) $text);
}

/**
 * Whether a passage appears in the pilot essay plaintext.
 *
 * Prefix/suffix are stored for later re-binding; membership security is the
 * passage itself (normalization may differ slightly for context strings).
 *
 * @param string $passage Selected passage.
 * @return bool
 */
function noelclark_v1_ps_highlights_passage_in_essay($passage) {
    $essay = noelclark_v1_ps_highlights_pilot_essay_plaintext();

    if ($essay === '' || $passage === '') {
        return false;
    }

    if (function_exists('mb_strpos')) {
        return mb_strpos($essay, $passage) !== false;
    }

    return strpos($essay, $passage) !== false;
}

/**
 * Exclude P.S. highlight comments from public front-end queries and feeds.
 *
 * @param array $clauses Query clauses.
 * @return array
 */
function noelclark_v1_ps_highlights_exclude_public_comment_clauses($clauses) {
    global $wpdb;

    if (is_admin() && !wp_doing_ajax()) {
        return $clauses;
    }

    /* Keep admin comment screens intact; hide from public loops/feeds/REST. */
    if (is_admin()) {
        return $clauses;
    }

    $flag = NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG;
    $clauses['where'] .= $wpdb->prepare(
        " AND {$wpdb->comments}.comment_ID NOT IN (
            SELECT comment_id FROM {$wpdb->commentmeta}
            WHERE meta_key = %s AND meta_value = %s
        )",
        $flag,
        '1'
    );

    return $clauses;
}
add_filter('comments_clauses', 'noelclark_v1_ps_highlights_exclude_public_comment_clauses');

/**
 * Keep highlight comments out of the public comments REST collection.
 *
 * @param array           $args    Query args.
 * @param WP_REST_Request $request Request.
 * @return array
 */
function noelclark_v1_ps_highlights_rest_comment_query($args, $request) {
    unset($request);

    if (!isset($args['meta_query']) || !is_array($args['meta_query'])) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = array(
        'key'     => NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG,
        'compare' => 'NOT EXISTS',
    );

    return $args;
}
add_filter('rest_comment_query', 'noelclark_v1_ps_highlights_rest_comment_query', 10, 2);

/**
 * Same-site Origin/Referer check (absent headers allowed).
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function noelclark_v1_ps_highlights_request_is_same_site(WP_REST_Request $request) {
    if (function_exists('noelclark_v1_social_request_is_same_site')) {
        return noelclark_v1_social_request_is_same_site($request);
    }

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
 * Client rate-limit key (hashed; never stored raw).
 *
 * @param int $page_id Essay page ID.
 * @return string
 */
function noelclark_v1_ps_highlights_rate_key($page_id) {
    $ip = '';

    if (isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    return 'nc_ps_hl_' . md5($ip . '|' . (string) (int) $page_id);
}

/**
 * Whether the client is within the rate window.
 *
 * @param int $page_id Essay page ID.
 * @return bool
 */
function noelclark_v1_ps_highlights_rate_allow($page_id) {
    $key   = noelclark_v1_ps_highlights_rate_key($page_id);
    $count = (int) get_transient($key);

    return $count < NOELCLARK_V1_PS_HIGHLIGHTS_RATE_MAX;
}

/**
 * Increment the rate counter after an accepted submission.
 *
 * @param int $page_id Essay page ID.
 * @return void
 */
function noelclark_v1_ps_highlights_rate_hit($page_id) {
    $key   = noelclark_v1_ps_highlights_rate_key($page_id);
    $count = (int) get_transient($key);
    set_transient($key, $count + 1, NOELCLARK_V1_PS_HIGHLIGHTS_RATE_WINDOW);
}

/**
 * String length helper (characters, not bytes).
 *
 * @param string $text Text.
 * @return int
 */
function noelclark_v1_ps_highlights_strlen($text) {
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($text);
    }

    return strlen($text);
}

/**
 * Normalize passage / context plain text (no HTML; whitespace collapsed).
 *
 * Used for essay membership checks. Does not silently truncate — callers
 * must enforce max length and reject over-limit input.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function noelclark_v1_ps_highlights_plain_text($value) {
    if (!is_string($value)) {
        return '';
    }

    $text = wp_strip_all_tags($value);
    $text = sanitize_textarea_field($text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim((string) $text);

    return is_string($text) ? $text : '';
}

/**
 * Sanitize optional written note while preserving paragraph breaks.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function noelclark_v1_ps_highlights_note_text($value) {
    if (!is_string($value)) {
        return '';
    }

    $text = wp_strip_all_tags($value);
    $text = sanitize_textarea_field($text);
    $text = str_replace(array("\r\n", "\r"), "\n", (string) $text);
    /* Collapse horizontal whitespace only; keep newlines. */
    $text = preg_replace('/[^\S\n]+/u', ' ', $text);
    $text = preg_replace('/\n{3,}/u', "\n\n", $text);
    $text = trim($text);

    return is_string($text) ? $text : '';
}

/**
 * Sanitize required display name for comment_author.
 *
 * @param mixed $value Raw value.
 * @return string Empty string if invalid.
 */
function noelclark_v1_ps_highlights_display_name($value) {
    if (!is_string($value)) {
        return '';
    }

    $name = wp_strip_all_tags($value);
    $name = sanitize_text_field($name);
    $name = trim(preg_replace('/\s+/u', ' ', (string) $name));

    if ($name === '') {
        return '';
    }

    if (noelclark_v1_ps_highlights_strlen($name) > NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX) {
        return '';
    }

    /* Reject URLs / protocol-like strings — nicknames only. */
    if (preg_match('/https?:\/\/|www\.|\/\/|\.(com|net|org|io|co|info)\b/i', $name)) {
        return '';
    }

    if (preg_match('/[<>{}[\]]/', $name)) {
        return '';
    }

    return $name;
}

/**
 * JSON response that must not be cached.
 *
 * @param array $data   Body.
 * @param int   $status HTTP status.
 * @return WP_REST_Response
 */
function noelclark_v1_ps_highlights_response($data, $status = 200) {
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
 * REST: accept a highlight (optional note). Always pending.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function noelclark_v1_ps_highlights_rest_submit(WP_REST_Request $request) {
    if (!noelclark_v1_ps_highlights_request_is_same_site($request)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'forbidden'), 403);
    }

    $params = $request->get_json_params();

    if (!is_array($params)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'invalid'), 400);
    }

    $nonce = isset($params['nonce']) && is_string($params['nonce']) ? $params['nonce'] : '';

    if ($nonce === '' || !wp_verify_nonce($nonce, NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'nonce_error'), 403);
    }

    /* Honeypot: bots fill this; humans never see it. */
    $honeypot = isset($params['company']) ? $params['company'] : '';

    if (is_string($honeypot) && trim($honeypot) !== '') {
        return noelclark_v1_ps_highlights_response(array('code' => 'accepted'), 202);
    }

    $page_id = isset($params['pageId']) ? (int) $params['pageId'] : 0;

    if (!noelclark_v1_ps_highlights_is_allowed_page_id($page_id)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'forbidden_page'), 403);
    }

    if (!noelclark_v1_ps_highlights_rate_allow($page_id)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'rate_limited'), 429);
    }

    $display_name = noelclark_v1_ps_highlights_display_name(
        isset($params['displayName']) ? $params['displayName'] : ''
    );

    if ($display_name === '') {
        $raw_name = isset($params['displayName']) ? $params['displayName'] : '';
        $raw_len  = is_string($raw_name) ? noelclark_v1_ps_highlights_strlen(trim(wp_strip_all_tags($raw_name))) : 0;

        if ($raw_len > NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX) {
            return noelclark_v1_ps_highlights_response(array('code' => 'name_too_long'), 400);
        }

        return noelclark_v1_ps_highlights_response(array('code' => 'name_required'), 400);
    }

    $passage = noelclark_v1_ps_highlights_plain_text(
        isset($params['passage']) ? $params['passage'] : ''
    );
    $prefix = noelclark_v1_ps_highlights_plain_text(
        isset($params['prefix']) ? $params['prefix'] : ''
    );
    $suffix = noelclark_v1_ps_highlights_plain_text(
        isset($params['suffix']) ? $params['suffix'] : ''
    );
    $note = noelclark_v1_ps_highlights_note_text(
        isset($params['note']) ? $params['note'] : ''
    );

    if (noelclark_v1_ps_highlights_strlen($prefix) > NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX) {
        $prefix = function_exists('mb_substr')
            ? mb_substr($prefix, 0, NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX)
            : substr($prefix, 0, NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX);
    }

    if (noelclark_v1_ps_highlights_strlen($suffix) > NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX) {
        $suffix = function_exists('mb_substr')
            ? mb_substr($suffix, 0, NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX)
            : substr($suffix, 0, NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX);
    }

    $passage_len = noelclark_v1_ps_highlights_strlen($passage);
    $note_len    = noelclark_v1_ps_highlights_strlen($note);

    if ($passage === '' || $passage_len < 3) {
        return noelclark_v1_ps_highlights_response(array('code' => 'passage_required'), 400);
    }

    if ($passage_len > NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX) {
        return noelclark_v1_ps_highlights_response(array('code' => 'passage_too_long'), 400);
    }

    if ($note_len > NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX) {
        return noelclark_v1_ps_highlights_response(array('code' => 'note_too_long'), 400);
    }

    if (!noelclark_v1_ps_highlights_passage_in_essay($passage)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'passage_mismatch'), 400);
    }

    $submission_type = $note === '' ? 'highlight' : 'highlight_note';

    /*
     * Highlight-only: empty comment_content via wp_insert_comment (no placeholder).
     * Optional note is plain text in comment_content — never visitor HTML.
     * Display name stored in comment_author (nickname only; not verified identity).
     * Always pending — Phase B1 does not auto-publish.
     */
    $comment_id = wp_insert_comment(
        array(
            'comment_post_ID'      => $page_id,
            'comment_author'       => $display_name,
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_content'      => $note,
            'comment_type'         => '',
            'comment_parent'       => 0,
            'user_id'              => 0,
            'comment_agent'        => 'NoelClark P.S. Highlights',
            'comment_approved'     => 0,
            'comment_date'         => current_time('mysql'),
            'comment_date_gmt'     => current_time('mysql', true),
        )
    );

    if (!$comment_id) {
        return noelclark_v1_ps_highlights_response(array('code' => 'unavailable'), 503);
    }

    $comment_id = (int) $comment_id;

    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG, '1');
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE, $passage);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PREFIX, $prefix);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_SUFFIX, $suffix);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE, $submission_type);

    /* Force pending even if a plugin auto-approves on insert. */
    wp_set_comment_status($comment_id, 'hold');

    $stored = get_comment($comment_id);

    if ($stored && (string) $stored->comment_approved !== '0') {
        wp_update_comment(
            array(
                'comment_ID'       => $comment_id,
                'comment_approved' => '0',
            )
        );
    }

    noelclark_v1_ps_highlights_rate_hit($page_id);

    return noelclark_v1_ps_highlights_response(
        array(
            'code' => 'pending',
            'type' => $submission_type,
        ),
        202
    );
}

/**
 * Register submit-only REST route (no public read).
 *
 * @return void
 */
function noelclark_v1_ps_highlights_register_rest_routes() {
    register_rest_route(
        NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE,
        NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE,
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'noelclark_v1_ps_highlights_rest_submit',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'noelclark_v1_ps_highlights_register_rest_routes');

/**
 * Label P.S. highlight rows in the Comments admin list.
 *
 * @param string     $text    Existing text.
 * @param WP_Comment $comment Comment.
 * @return string
 */
function noelclark_v1_ps_highlights_comment_row_action($text, $comment) {
    if (!is_admin() || !$comment instanceof WP_Comment) {
        return $text;
    }

    if (get_comment_meta($comment->comment_ID, NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG, true) !== '1') {
        return $text;
    }

    $passage = (string) get_comment_meta($comment->comment_ID, NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE, true);
    $type    = (string) get_comment_meta($comment->comment_ID, NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE, true);
    $label   = $type === 'highlight_note' ? 'P.S. highlight + note' : 'P.S. highlight';
    $note    = is_string($text) ? trim(wp_strip_all_tags($text)) : '';

    if ($passage !== '') {
        $excerpt = function_exists('mb_substr') ? mb_substr($passage, 0, 80) : substr($passage, 0, 80);
        $line    = $label . ': "' . $excerpt . (strlen($passage) > 80 ? '…' : '') . '"';

        if ($note !== '') {
            $line .= "\n\n" . $note;
        }

        return $line;
    }

    return $label;
}
add_filter('get_comment_text', 'noelclark_v1_ps_highlights_comment_row_action', 10, 2);

/**
 * Frontend config for the pilot page only.
 *
 * @param int $page_id Essay page ID.
 * @return array<string, mixed>
 */
function noelclark_v1_ps_highlights_frontend_config($page_id) {
    return array(
        'submitUrl'       => esc_url_raw(rest_url(NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE . NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE)),
        'nonce'           => wp_create_nonce(NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION),
        'pageId'          => (int) $page_id,
        'passageMax'      => NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX,
        'noteMax'         => NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX,
        'displayNameMax'  => NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX,
        'contextMax'      => NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX,
    );
}
