<?php
/**
 * P.S. reader highlights — Open Journal pilot.
 *
 * Phase B2: Akismet-screened submit + approved-only public notes list.
 * Stores in wp_comments + wp_commentmeta. Pending/spam stay private.
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
const NOELCLARK_V1_PS_HIGHLIGHTS_META_COLOR     = '_noelclark_ps_color';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY  = '_noelclark_ps_passage_identity';
const NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT     = '_noelclark_ps_highlight_count';
const NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX      = 2000;
const NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX         = 1000;
const NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX = 40;
const NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX      = 48;
const NOELCLARK_V1_PS_HIGHLIGHTS_RATE_MAX         = 5;
const NOELCLARK_V1_PS_HIGHLIGHTS_RATE_WINDOW      = HOUR_IN_SECONDS;
const NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX  = 100;
const NOELCLARK_V1_PS_HIGHLIGHTS_LOCK_TIMEOUT     = 8;
const NOELCLARK_V1_PS_HIGHLIGHTS_IDEMPOTENCY_TTL  = 120;

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
 * Candidate permalink paths for the pilot essay.
 *
 * REST requests lack front-end page context, so resolution must try the
 * nested /ps/ path used by the P.S. journey as well as a top-level slug.
 *
 * @return array<int, string>
 */
function noelclark_v1_ps_highlights_pilot_page_paths() {
    return array(
        'ps/returning-to-the-open-journal',
        'returning-to-the-open-journal',
    );
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

    $template = noelclark_v1_ps_highlights_pilot_template();

    foreach (noelclark_v1_ps_highlights_pilot_page_paths() as $path) {
        $page = get_page_by_path($path);

        if ($page instanceof WP_Post && get_page_template_slug($page) === $template) {
            return (int) $page->ID;
        }
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

    $parts = array();

    /* Title is highlightable — include it in membership plaintext. */
    if (preg_match('/class="ps-writing__title"[^>]*>([\s\S]*?)<\/h1>/i', $raw, $title_match)) {
        $parts[] = $title_match[1];
    }

    if (!preg_match('/data-ps-highlights-root[^>]*>([\s\S]*?)<div[^>]*data-ps-tags/i', $raw, $matches)) {
        return '';
    }

    $parts[] = $matches[1];
    $html    = implode(' ', $parts);
    $text    = wp_strip_all_tags($html);
    $text    = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text    = preg_replace('/\s+/u', ' ', $text);

    return trim((string) $text);
}

/**
 * Allowlisted highlighter brand colors (hex, uppercase).
 *
 * @return string[]
 */
function noelclark_v1_ps_highlights_allowed_colors() {
    return array('#A21EFF', '#FF2D78', '#00BFBF', '#FFEE08');
}

/**
 * Sanitize optional highlighter color.
 *
 * @param mixed $value Raw value.
 * @return string Empty or allowlisted hex.
 */
function noelclark_v1_ps_highlights_color($value) {
    if (!is_string($value)) {
        return '';
    }

    $color = strtoupper(trim($value));

    if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
        return '';
    }

    return in_array($color, noelclark_v1_ps_highlights_allowed_colors(), true) ? $color : '';
}

/**
 * Whether a passage appears in the pilot essay plaintext.
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
 * Substring helper (characters).
 *
 * @param string $text   Text.
 * @param int    $start  Start.
 * @param int    $length Length.
 * @return string
 */
function noelclark_v1_ps_highlights_substr($text, $start, $length = null) {
    if (function_exists('mb_substr')) {
        return $length === null
            ? (string) mb_substr($text, $start)
            : (string) mb_substr($text, $start, $length);
    }

    return $length === null
        ? (string) substr($text, $start)
        : (string) substr($text, $start, $length);
}

/**
 * Find next occurrence offset of $needle in $haystack at/after $offset.
 *
 * @param string $haystack Haystack.
 * @param string $needle   Needle.
 * @param int    $offset   Offset.
 * @return int|false
 */
function noelclark_v1_ps_highlights_strpos($haystack, $needle, $offset = 0) {
    if (function_exists('mb_strpos')) {
        return mb_strpos($haystack, $needle, $offset);
    }

    return strpos($haystack, $needle, $offset);
}

/**
 * Validate that prefix + passage + suffix form a contiguous essay match
 * at the claimed location. Rejects forged context that does not appear
 * adjacent to the passage in the pilot essay.
 *
 * Empty prefix/suffix are allowed only when they match the essay edges
 * (or when the client legitimately had no surrounding text within CONTEXT_MAX).
 *
 * @param string $passage Passage.
 * @param string $prefix  Prefix context.
 * @param string $suffix  Suffix context.
 * @return bool
 */
function noelclark_v1_ps_highlights_context_matches_essay($passage, $prefix, $suffix) {
    $essay = noelclark_v1_ps_highlights_pilot_essay_plaintext();

    if ($essay === '' || $passage === '') {
        return false;
    }

    $prefix = (string) $prefix;
    $suffix = (string) $suffix;
    $offset = 0;
    $plen   = noelclark_v1_ps_highlights_strlen($passage);

    while (($pos = noelclark_v1_ps_highlights_strpos($essay, $passage, $offset)) !== false) {
        $before = noelclark_v1_ps_highlights_substr($essay, 0, (int) $pos);
        $after  = noelclark_v1_ps_highlights_substr($essay, (int) $pos + $plen);

        $actual_prefix = $prefix === ''
            ? ''
            : (noelclark_v1_ps_highlights_strlen($before) >= noelclark_v1_ps_highlights_strlen($prefix)
                ? noelclark_v1_ps_highlights_substr(
                    $before,
                    noelclark_v1_ps_highlights_strlen($before) - noelclark_v1_ps_highlights_strlen($prefix)
                )
                : $before);

        $actual_suffix = $suffix === ''
            ? ''
            : (noelclark_v1_ps_highlights_strlen($after) >= noelclark_v1_ps_highlights_strlen($suffix)
                ? noelclark_v1_ps_highlights_substr($after, 0, noelclark_v1_ps_highlights_strlen($suffix))
                : $after);

        if ($actual_prefix === $prefix && $actual_suffix === $suffix) {
            return true;
        }

        $offset = (int) $pos + 1;
    }

    return false;
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
 * Client IP for flood / Akismet context (sanitized).
 *
 * @return string
 */
function noelclark_v1_ps_highlights_client_ip() {
    if (!isset($_SERVER['REMOTE_ADDR']) || !is_string($_SERVER['REMOTE_ADDR'])) {
        return '';
    }

    return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
}

/**
 * Whether Akismet can screen comments (class + public API + key).
 *
 * @return bool
 */
function noelclark_v1_ps_highlights_akismet_ready() {
    if (!class_exists('Akismet')) {
        return false;
    }

    if (!is_callable(array('Akismet', 'get_api_key')) || !is_callable(array('Akismet', 'auto_check_comment'))) {
        return false;
    }

    $key = Akismet::get_api_key();

    return is_string($key) && $key !== '';
}

/**
 * Plain-text body sent to spam screening (not stored as comment_content).
 *
 * Always includes the passage so highlight-only posts are screened.
 *
 * @param string $passage Passage.
 * @param string $note    Optional note.
 * @return string
 */
function noelclark_v1_ps_highlights_screening_content($passage, $note) {
    $passage = (string) $passage;
    $note    = (string) $note;

    if ($passage === '') {
        return $note;
    }

    if ($note === '') {
        return $passage;
    }

    return $passage . "\n\n" . $note;
}

/**
 * Map Akismet / safety signals to a comment_approved value.
 *
 * @param array $commentdata Comment data after screening.
 * @return string '1' | '0' | 'spam' | 'discard'
 */
function noelclark_v1_ps_highlights_decide_approval($commentdata) {
    if (!is_array($commentdata)) {
        return '0';
    }

    $pro_tip = isset($commentdata['akismet_pro_tip']) ? (string) $commentdata['akismet_pro_tip'] : '';

    if ($pro_tip === 'discard' && is_callable(array('Akismet', 'allow_discard')) && Akismet::allow_discard()) {
        return 'discard';
    }

    $result = isset($commentdata['akismet_result']) ? (string) $commentdata['akismet_result'] : '';

    if ($result === 'true') {
        return 'spam';
    }

    if ($result === 'false') {
        /* Anticipated Akismet errors must not look like verified ham. */
        if (!empty($commentdata['comment_meta']['akismet_error'])) {
            return '0';
        }

        /* Akismet may delay full classification — keep private until confirmed. */
        if (isset($commentdata['comment_approved']) && (string) $commentdata['comment_approved'] === '0') {
            return '0';
        }

        if (!empty($commentdata['comment_meta']['akismet_schedule_approval_fallback'])) {
            return '0';
        }

        return '1';
    }

    /* Missing key result, skipped, error, or unexpected → fail closed. */
    return '0';
}

/**
 * Public-safe note payload (no email, IP, or private meta).
 *
 * @param int         $comment_id Comment ID.
 * @param string      $author     Display name.
 * @param string      $date_gmt   GMT mysql datetime.
 * @param string      $passage    Passage.
 * @param string      $note       Written note.
 * @param string|null $type       Submission type.
 * @return array<string, mixed>
 */
function noelclark_v1_ps_highlights_public_note_payload($comment_id, $author, $date_gmt, $passage, $note, $type = null, $color = null, $count = null, $identity = null) {
    $iso = '';

    if (is_string($date_gmt) && $date_gmt !== '') {
        $timestamp = strtotime($date_gmt . ' UTC');

        if ($timestamp) {
            $iso = gmdate('c', $timestamp);
        }
    }

    /* Map legacy "note" type to public "comment". */
    if ($type === 'note') {
        $type = 'comment';
    }

    $payload = array(
        'id'          => (int) $comment_id,
        'displayName' => (string) $author,
        'date'        => $iso,
        'passage'     => (string) $passage,
        'note'        => (string) $note,
    );

    if (is_string($type) && $type !== '') {
        $payload['type'] = $type;
    }

    if ($type === 'highlight') {
        $n = is_numeric($count) ? (int) $count : 1;
        if ($n < 1) {
            $n = 1;
        }
        $payload['count'] = $n;
        /* Highlight-only public cards do not show author/date in the UI. */

        if (is_string($identity) && $identity !== '') {
            $payload['identity'] = $identity;
        }
    }

    $safe_color = noelclark_v1_ps_highlights_color($color);

    if ($safe_color !== '') {
        $payload['color'] = $safe_color;
    }

    return $payload;
}

/**
 * Sanitize a client submission idempotency key.
 *
 * Not treated as visitor identity — only deduplicates retries of one attempt.
 * Accepts UUID or opaque token: 8–64 chars of [A-Za-z0-9_-].
 *
 * @param mixed $value Raw value.
 * @return string Empty when invalid.
 */
function noelclark_v1_ps_highlights_client_request_id($value) {
    if (!is_string($value)) {
        return '';
    }

    $id = trim($value);

    if ($id === '' || noelclark_v1_ps_highlights_strlen($id) < 8 || noelclark_v1_ps_highlights_strlen($id) > 64) {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
        return '';
    }

    return $id;
}

/**
 * Transient key for per-submission idempotency (page + identity + request id).
 * Does not include IP — shared Wi-Fi visitors must not suppress each other.
 *
 * @param int    $page_id    Page ID.
 * @param string $identity   Passage identity hash.
 * @param string $request_id Sanitized client request id.
 * @return string
 */
function noelclark_v1_ps_highlights_idempotency_key($page_id, $identity, $request_id) {
    return 'nc_ps_idem_' . md5((string) (int) $page_id . '|' . (string) $identity . '|' . (string) $request_id);
}

/**
 * Recall a prior successful highlight response for the same submission attempt.
 *
 * @param string $key Transient key.
 * @return array|null {status:int, body:array}|null
 */
function noelclark_v1_ps_highlights_idempotency_get($key) {
    if ($key === '') {
        return null;
    }

    $cached = get_transient($key);

    if (!is_array($cached) || !isset($cached['status'], $cached['body']) || !is_array($cached['body'])) {
        return null;
    }

    return array(
        'status' => (int) $cached['status'],
        'body'   => $cached['body'],
    );
}

/**
 * Store idempotent highlight response for retries of the same attempt.
 *
 * @param string $key    Transient key.
 * @param int    $status HTTP status.
 * @param array  $body   Response body.
 * @return void
 */
function noelclark_v1_ps_highlights_idempotency_put($key, $status, $body) {
    if ($key === '' || !is_array($body)) {
        return;
    }

    set_transient(
        $key,
        array(
            'status' => (int) $status,
            'body'   => $body,
        ),
        NOELCLARK_V1_PS_HIGHLIGHTS_IDEMPOTENCY_TTL
    );
}

/**
 * Whether a near-identical P.S. submission already exists (duplicate guard).
 *
 * Bypasses the public comments_clauses exclusion via a direct meta join.
 *
 * @param int    $page_id Page ID.
 * @param string $author  Display name.
 * @param string $passage Passage.
 * @param string $note    Note.
 * @return bool
 */

/**
 * Article-scoped exact passage identity (passage + location context).
 *
 * @param string $passage Passage.
 * @param string $prefix  Prefix context.
 * @param string $suffix  Suffix context.
 * @return string
 */
function noelclark_v1_ps_highlights_passage_identity($passage, $prefix, $suffix) {
    return hash('sha256', (string) $passage . "\n" . (string) $prefix . "\n" . (string) $suffix);
}

/**
 * Find an approved public highlight entry for an exact passage identity.
 *
 * @param int    $page_id  Page ID.
 * @param string $identity Passage identity hash.
 * @return object|null Row with comment_ID and highlight_count.
 */
function noelclark_v1_ps_highlights_find_approved_highlight($page_id, $identity) {
    global $wpdb;

    $page_id  = (int) $page_id;
    $identity = (string) $identity;

    if ($page_id <= 0 || $identity === '') {
        return null;
    }

    $flag = NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG;
    $idk  = NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY;
    $type = NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE;
    $cnt  = NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT c.comment_ID,
                COALESCE(MAX(CASE WHEN cm.meta_key = %s THEN cm.meta_value END), '1') AS highlight_count
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} fm
                ON fm.comment_id = c.comment_ID AND fm.meta_key = %s AND fm.meta_value = '1'
            INNER JOIN {$wpdb->commentmeta} im
                ON im.comment_id = c.comment_ID AND im.meta_key = %s AND im.meta_value = %s
            INNER JOIN {$wpdb->commentmeta} tm
                ON tm.comment_id = c.comment_ID AND tm.meta_key = %s AND tm.meta_value = 'highlight'
            LEFT JOIN {$wpdb->commentmeta} cm
                ON cm.comment_id = c.comment_ID AND cm.meta_key = %s
            WHERE c.comment_post_ID = %d
                AND c.comment_approved = '1'
            GROUP BY c.comment_ID
            ORDER BY c.comment_date_gmt ASC, c.comment_ID ASC
            LIMIT 1",
            $cnt,
            $flag,
            $idk,
            $identity,
            $type,
            $cnt,
            $page_id
        )
    );

    return $row ?: null;
}

/**
 * MySQL advisory lock name for a page + passage identity (≤64 chars).
 *
 * @param int    $page_id  Page ID.
 * @param string $identity Passage identity hash.
 * @return string
 */
function noelclark_v1_ps_highlights_lock_name($page_id, $identity) {
    return 'nc_ps_hl_' . md5((string) (int) $page_id . '|' . (string) $identity);
}

/**
 * Acquire a session advisory lock for exact-match coordination.
 *
 * @param string $lock_name Lock name.
 * @return bool True when acquired.
 */
function noelclark_v1_ps_highlights_acquire_lock($lock_name) {
    global $wpdb;

    if (!is_string($lock_name) || $lock_name === '') {
        return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $got = $wpdb->get_var(
        $wpdb->prepare(
            'SELECT GET_LOCK(%s, %d)',
            $lock_name,
            NOELCLARK_V1_PS_HIGHLIGHTS_LOCK_TIMEOUT
        )
    );

    return (string) $got === '1';
}

/**
 * Release a session advisory lock.
 *
 * @param string $lock_name Lock name.
 * @return void
 */
function noelclark_v1_ps_highlights_release_lock($lock_name) {
    global $wpdb;

    if (!is_string($lock_name) || $lock_name === '') {
        return;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
}

/**
 * Atomically increment highlight count on an existing public entry.
 *
 * Uses a single UPDATE … SET meta_value = meta_value + 1 when the row
 * already exists; otherwise seeds to 2 (canonical entry started at 1).
 *
 * @param int $comment_id Comment ID.
 * @return int|false New count, or false on failure.
 */
function noelclark_v1_ps_highlights_increment_count($comment_id) {
    global $wpdb;

    $comment_id = (int) $comment_id;

    if ($comment_id <= 0) {
        return false;
    }

    $key = NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->commentmeta}
            SET meta_value = CAST(meta_value AS UNSIGNED) + 1
            WHERE comment_id = %d AND meta_key = %s",
            $comment_id,
            $key
        )
    );

    if ($updated === false) {
        return false;
    }

    if ((int) $updated === 0) {
        /* Meta missing — seed as first rematch (canonical was 1). */
        $ok = add_comment_meta($comment_id, $key, '2', true);

        if (!$ok) {
            /* Race: another writer inserted — retry atomic bump once. */
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->commentmeta}
                    SET meta_value = CAST(meta_value AS UNSIGNED) + 1
                    WHERE comment_id = %d AND meta_key = %s",
                    $comment_id,
                    $key
                )
            );
        }
    }

    clean_comment_cache($comment_id);

    $count = (int) get_comment_meta($comment_id, $key, true);

    return $count >= 1 ? $count : false;
}

/**
 * Find any non-trash P.S. highlight (approved/pending/spam) for an identity.
 *
 * Used when reconciling later approval of a pending duplicate.
 *
 * @param int    $page_id  Page ID.
 * @param string $identity Passage identity hash.
 * @param string $status   comment_approved value ('1'|'0'|'spam') or '' for any.
 * @return object|null
 */
function noelclark_v1_ps_highlights_find_highlight_by_status($page_id, $identity, $status = '1') {
    global $wpdb;

    $page_id  = (int) $page_id;
    $identity = (string) $identity;

    if ($page_id <= 0 || $identity === '') {
        return null;
    }

    $flag = NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG;
    $idk  = NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY;
    $type = NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE;
    $cnt  = NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT;

    $sql = "SELECT c.comment_ID, c.comment_approved, c.comment_date_gmt,
                COALESCE(MAX(CASE WHEN cm.meta_key = %s THEN cm.meta_value END), '1') AS highlight_count
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} fm
                ON fm.comment_id = c.comment_ID AND fm.meta_key = %s AND fm.meta_value = '1'
            INNER JOIN {$wpdb->commentmeta} im
                ON im.comment_id = c.comment_ID AND im.meta_key = %s AND im.meta_value = %s
            INNER JOIN {$wpdb->commentmeta} tm
                ON tm.comment_id = c.comment_ID AND tm.meta_key = %s AND tm.meta_value = 'highlight'
            LEFT JOIN {$wpdb->commentmeta} cm
                ON cm.comment_id = c.comment_ID AND cm.meta_key = %s
            WHERE c.comment_post_ID = %d";

    $args = array($cnt, $flag, $idk, $identity, $type, $cnt, $page_id);

    if ($status !== '') {
        $sql   .= ' AND c.comment_approved = %s';
        $args[] = $status;
    } else {
        $sql .= " AND c.comment_approved IN ('1', '0', 'spam')";
    }

    $sql .= ' GROUP BY c.comment_ID, c.comment_approved, c.comment_date_gmt
            ORDER BY
                CASE c.comment_approved WHEN \'1\' THEN 0 WHEN \'0\' THEN 1 ELSE 2 END ASC,
                c.comment_date_gmt ASC,
                c.comment_ID ASC
            LIMIT 1';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args)));

    return $row ?: null;
}

function noelclark_v1_ps_highlights_is_duplicate($page_id, $author, $passage, $note) {
    global $wpdb;

    $page_id = (int) $page_id;

    if ($page_id <= 0) {
        return false;
    }

    $flag = NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG;
    $pass = NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT c.comment_ID, c.comment_content, pm.meta_value AS passage
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} fm
                ON fm.comment_id = c.comment_ID AND fm.meta_key = %s AND fm.meta_value = '1'
            INNER JOIN {$wpdb->commentmeta} pm
                ON pm.comment_id = c.comment_ID AND pm.meta_key = %s
            WHERE c.comment_post_ID = %d
                AND c.comment_author = %s
                AND c.comment_approved IN ('1', '0', 'spam')
            ORDER BY c.comment_ID DESC
            LIMIT 25",
            $flag,
            $pass,
            $page_id,
            $author
        )
    );

    if (!is_array($rows)) {
        return false;
    }

    foreach ($rows as $row) {
        if (!isset($row->passage, $row->comment_content)) {
            continue;
        }

        if ((string) $row->passage === $passage && (string) $row->comment_content === $note) {
            return true;
        }
    }

    return false;
}

/**
 * Persist a P.S. comment with decided moderation status.
 *
 * @param array  $args {
 *     @type int    $page_id
 *     @type string $display_name
 *     @type string $note
 *     @type string $passage
 *     @type string $prefix
 *     @type string $suffix
 *     @type string $submission_type
 *     @type string $color
 *     @type string $approved          '1'|'0'|'spam'
 *     @type array  $akismet_meta      Optional meta from Akismet check.
 *     @type string $ip
 * }
 * @return int|false Comment ID or false.
 */
function noelclark_v1_ps_highlights_insert_comment($args) {
    $page_id          = isset($args['page_id']) ? (int) $args['page_id'] : 0;
    $display_name     = isset($args['display_name']) ? (string) $args['display_name'] : '';
    $note             = isset($args['note']) ? (string) $args['note'] : '';
    $passage          = isset($args['passage']) ? (string) $args['passage'] : '';
    $prefix           = isset($args['prefix']) ? (string) $args['prefix'] : '';
    $suffix           = isset($args['suffix']) ? (string) $args['suffix'] : '';
    $submission_type  = isset($args['submission_type']) ? (string) $args['submission_type'] : 'highlight';
    $color            = isset($args['color']) ? noelclark_v1_ps_highlights_color($args['color']) : '';
    $approved         = isset($args['approved']) ? (string) $args['approved'] : '0';
    $akismet_meta     = isset($args['akismet_meta']) && is_array($args['akismet_meta']) ? $args['akismet_meta'] : array();
    $ip               = isset($args['ip']) ? (string) $args['ip'] : '';

    if (!in_array($approved, array('1', '0', 'spam'), true)) {
        $approved = '0';
    }

    /*
     * Store the optional note only in comment_content — never the screening composite.
     * Highlight-only remains an empty comment_content.
     */
    $comment_id = wp_insert_comment(
        array(
            'comment_post_ID'      => $page_id,
            'comment_author'       => $display_name,
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_author_IP'    => $ip,
            'comment_content'      => $note,
            'comment_type'         => '',
            'comment_parent'       => 0,
            'user_id'              => 0,
            'comment_agent'        => 'NoelClark P.S. Highlights',
            'comment_approved'     => $approved,
            'comment_date'         => current_time('mysql'),
            'comment_date_gmt'     => current_time('mysql', true),
        )
    );

    if (!$comment_id) {
        return false;
    }

    $comment_id = (int) $comment_id;

    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG, '1');
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE, $passage);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PREFIX, $prefix);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_SUFFIX, $suffix);
    update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE, $submission_type);

    if ($passage !== '') {
        $identity = noelclark_v1_ps_highlights_passage_identity($passage, $prefix, $suffix);
        update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY, $identity);
    }

    if ($submission_type === 'highlight') {
        update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT, '1');
    }

    if ($color !== '') {
        update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_COLOR, $color);
    }

    foreach ($akismet_meta as $meta_key => $meta_value) {
        if (!is_string($meta_key) || $meta_key === '') {
            continue;
        }

        /* Only store Akismet's own keys — never overwrite P.S. meta. */
        if (strpos($meta_key, 'akismet_') !== 0) {
            continue;
        }

        update_comment_meta($comment_id, $meta_key, $meta_value);
    }

    $stored = get_comment($comment_id);

    if (!$stored) {
        return $comment_id;
    }

    $actual = (string) $stored->comment_approved;

    /*
     * Fail closed: never leave a comment publicly approved unless we intended '1'
     * after a successful ham result. Do not alter historical rows elsewhere.
     */
    if ($approved === '1' && $actual !== '1') {
        wp_update_comment(
            array(
                'comment_ID'       => $comment_id,
                'comment_approved' => '0',
            )
        );
    } elseif ($approved !== '1' && $actual === '1') {
        wp_set_comment_status($comment_id, $approved === 'spam' ? 'spam' : 'hold');
    }

    return $comment_id;
}

/**
 * REST: accept a highlight (optional note). Screen then publish / hold / spam.
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

    $raw_name = isset($params['displayName']) ? $params['displayName'] : '';
    $raw_len  = is_string($raw_name) ? noelclark_v1_ps_highlights_strlen(trim(wp_strip_all_tags($raw_name))) : 0;

    if ($raw_len > NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX) {
        return noelclark_v1_ps_highlights_response(array('code' => 'name_too_long'), 400);
    }

    $display_name = noelclark_v1_ps_highlights_display_name($raw_name);

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
    $color       = noelclark_v1_ps_highlights_color(
        isset($params['color']) ? $params['color'] : ''
    );

    if ($note_len > NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX) {
        return noelclark_v1_ps_highlights_response(array('code' => 'note_too_long'), 400);
    }

    $intent = isset($params['intent']) ? (string) $params['intent'] : '';

    if ($passage === '') {
        /* Ordinary comment / note-only: require written text; never invent a quotation. */
        if ($note === '') {
            return noelclark_v1_ps_highlights_response(array('code' => 'empty'), 400);
        }

        if ($display_name === '') {
            return noelclark_v1_ps_highlights_response(array('code' => 'name_required'), 400);
        }

        $submission_type = ($intent === 'comment' || $intent === 'note') ? 'comment' : 'comment';
        $prefix          = '';
        $suffix          = '';
        $color           = '';
    } else {
        if ($passage_len < 3) {
            return noelclark_v1_ps_highlights_response(array('code' => 'passage_required'), 400);
        }

        if ($passage_len > NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX) {
            return noelclark_v1_ps_highlights_response(array('code' => 'passage_too_long'), 400);
        }

        if (!noelclark_v1_ps_highlights_passage_in_essay($passage)) {
            return noelclark_v1_ps_highlights_response(array('code' => 'passage_mismatch'), 400);
        }

        /* Do not trust client prefix/suffix without contiguous essay validation. */
        if (!noelclark_v1_ps_highlights_context_matches_essay($passage, $prefix, $suffix)) {
            return noelclark_v1_ps_highlights_response(array('code' => 'context_mismatch'), 400);
        }

        /* Prototype UX: highlight submissions are highlight-only (no forced note). */
        $submission_type = 'highlight';
        $note            = '';

        if ($display_name === '') {
            $display_name = 'Reader';
        }

        if ($color === '') {
            $color = '#A21EFF';
        }
    }

    $ip        = noelclark_v1_ps_highlights_client_ip();
    $agent     = 'NoelClark P.S. Highlights';
    $screening = noelclark_v1_ps_highlights_screening_content($passage, $note);

    if (function_exists('wp_is_comment_flood') && wp_is_comment_flood($ip, '', current_time('mysql'), true)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'rate_limited'), 429);
    }

    if (function_exists('wp_check_comment_disallowed_list')
        && wp_check_comment_disallowed_list($display_name, '', '', $screening, $ip, $agent)
    ) {
        $comment_id = noelclark_v1_ps_highlights_insert_comment(
            array(
                'page_id'         => $page_id,
                'display_name'    => $display_name,
                'note'            => $note,
                'passage'         => $passage,
                'prefix'          => $prefix,
                'suffix'          => $suffix,
                'submission_type' => $submission_type,
                'color'           => $color,
                'approved'        => 'spam',
                'ip'              => $ip,
            )
        );

        if ($comment_id) {
            noelclark_v1_ps_highlights_rate_hit($page_id);
        }

        /* Do not reveal block reason to bots. */
        return noelclark_v1_ps_highlights_response(array('code' => 'accepted'), 202);
    }


    $identity        = '';
    $lock_name       = '';
    $idempotency_key = '';
    $request_id      = '';

    if ($submission_type === 'highlight' && $passage !== '') {
        $identity   = noelclark_v1_ps_highlights_passage_identity($passage, $prefix, $suffix);
        $lock_name  = noelclark_v1_ps_highlights_lock_name($page_id, $identity);
        $request_id = noelclark_v1_ps_highlights_client_request_id(
            isset($params['clientRequestId']) ? $params['clientRequestId'] : ''
        );

        /*
         * Per-submission idempotency (not IP-based). Same request id = retry of
         * one intentional attempt. Different visitors / new attempts use new ids
         * and both count even on shared Wi-Fi.
         */
        if ($request_id !== '') {
            $idempotency_key = noelclark_v1_ps_highlights_idempotency_key($page_id, $identity, $request_id);
            $replay          = noelclark_v1_ps_highlights_idempotency_get($idempotency_key);

            if ($replay) {
                return noelclark_v1_ps_highlights_response($replay['body'], $replay['status']);
            }
        }
    }

    if (noelclark_v1_ps_highlights_is_duplicate($page_id, $display_name, $passage, $note)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'duplicate'), 409);
    }

    $approved     = '0';
    $akismet_meta = array();

    if (!noelclark_v1_ps_highlights_akismet_ready()) {
        /* Screening unavailable — hold privately; never publish. */
        $approved = '0';
    } else {
        $commentdata = array(
            'comment_post_ID'      => $page_id,
            'comment_author'       => $display_name,
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_author_IP'    => $ip,
            'comment_content'      => $screening,
            'comment_type'         => '',
            'comment_parent'       => 0,
            'user_id'              => 0,
            'user_ID'              => 0,
            'comment_agent'        => $agent,
            'comment_meta'         => array(),
        );

        /*
         * Use Akismet's public auto_check_comment with rest_api context so
         * obvious discard returns WP_Error instead of redirect/die.
         */
        $checked = Akismet::auto_check_comment($commentdata, 'rest_api');

        if (is_wp_error($checked)) {
            noelclark_v1_ps_highlights_rate_hit($page_id);

            return noelclark_v1_ps_highlights_response(array('code' => 'accepted'), 202);
        }

        if (!is_array($checked)) {
            $approved = '0';
        } else {
            $decision = noelclark_v1_ps_highlights_decide_approval($checked);

            if ($decision === 'discard') {
                noelclark_v1_ps_highlights_rate_hit($page_id);

                return noelclark_v1_ps_highlights_response(array('code' => 'accepted'), 202);
            }

            $approved = $decision;

            if (isset($checked['comment_meta']) && is_array($checked['comment_meta'])) {
                $akismet_meta = $checked['comment_meta'];
            }
        }
    }

    /*
     * Exact-match coordination under MySQL GET_LOCK:
     * - Approved rematches increment the canonical public entry.
     * - Concurrent first inserts serialize so only one canonical public row is created.
     * Lock errors fail closed (503) so counts are never silently lost or doubled.
     */
    $held_lock = false;

    if ($submission_type === 'highlight' && $identity !== '' && $lock_name !== '') {
        $held_lock = noelclark_v1_ps_highlights_acquire_lock($lock_name);

        if (!$held_lock) {
            return noelclark_v1_ps_highlights_response(array('code' => 'unavailable'), 503);
        }
    }

    try {
        /* Under lock: another worker may have completed this same request id. */
        if ($idempotency_key !== '') {
            $replay = noelclark_v1_ps_highlights_idempotency_get($idempotency_key);

            if ($replay) {
                return noelclark_v1_ps_highlights_response($replay['body'], $replay['status']);
            }
        }

        if ($submission_type === 'highlight' && $approved === '1' && $identity !== '') {
            $existing = noelclark_v1_ps_highlights_find_approved_highlight($page_id, $identity);

            if ($existing && isset($existing->comment_ID)) {
                $existing_id = (int) $existing->comment_ID;
                $count       = noelclark_v1_ps_highlights_increment_count($existing_id);

                if ($count === false) {
                    return noelclark_v1_ps_highlights_response(array('code' => 'unavailable'), 503);
                }

                noelclark_v1_ps_highlights_rate_hit($page_id);

                $stored = get_comment($existing_id);
                $body   = array(
                    'code' => 'published',
                    'type' => 'highlight',
                    'note' => noelclark_v1_ps_highlights_public_note_payload(
                        $existing_id,
                        '',
                        $stored ? (string) $stored->comment_date_gmt : '',
                        $passage,
                        '',
                        'highlight',
                        $color,
                        $count,
                        $identity
                    ),
                );
                noelclark_v1_ps_highlights_idempotency_put($idempotency_key, 200, $body);

                return noelclark_v1_ps_highlights_response($body, 200);
            }
        }

        /*
         * Rejected / spam never become public entries and do not increment.
         * Pending inserts remain private until transition_comment_status reconciles.
         */
        $comment_id = noelclark_v1_ps_highlights_insert_comment(
            array(
                'page_id'         => $page_id,
                'display_name'    => $display_name,
                'note'            => $note,
                'passage'         => $passage,
                'prefix'          => $prefix,
                'suffix'          => $suffix,
                'submission_type' => $submission_type,
                'color'           => $color,
                'approved'        => $approved,
                'akismet_meta'    => $akismet_meta,
                'ip'              => $ip,
            )
        );

        if (!$comment_id) {
            return noelclark_v1_ps_highlights_response(array('code' => 'unavailable'), 503);
        }

        /*
         * Under lock: if another request published the same identity first,
         * fold this approved insert into the canonical entry and demote ours.
         */
        if ($submission_type === 'highlight' && $approved === '1' && $identity !== '') {
            $canonical = noelclark_v1_ps_highlights_find_approved_highlight($page_id, $identity);

            if ($canonical && isset($canonical->comment_ID) && (int) $canonical->comment_ID !== (int) $comment_id) {
                $canonical_id = (int) $canonical->comment_ID;
                $count        = noelclark_v1_ps_highlights_increment_count($canonical_id);

                if ($count === false) {
                    return noelclark_v1_ps_highlights_response(array('code' => 'unavailable'), 503);
                }

                wp_set_comment_status($comment_id, 'hold');
                noelclark_v1_ps_highlights_rate_hit($page_id);

                $stored = get_comment($canonical_id);
                $body   = array(
                    'code' => 'published',
                    'type' => 'highlight',
                    'note' => noelclark_v1_ps_highlights_public_note_payload(
                        $canonical_id,
                        '',
                        $stored ? (string) $stored->comment_date_gmt : '',
                        $passage,
                        '',
                        'highlight',
                        $color,
                        $count,
                        $identity
                    ),
                );
                noelclark_v1_ps_highlights_idempotency_put($idempotency_key, 200, $body);

                return noelclark_v1_ps_highlights_response($body, 200);
            }
        }

        noelclark_v1_ps_highlights_rate_hit($page_id);

        $stored = get_comment($comment_id);
        $actual = $stored ? (string) $stored->comment_approved : '0';

        if ($approved === '1' && $actual === '1') {
            $count_meta = get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT, true);
            $body       = array(
                'code' => 'published',
                'type' => $submission_type,
                'note' => noelclark_v1_ps_highlights_public_note_payload(
                    $comment_id,
                    $display_name,
                    $stored ? (string) $stored->comment_date_gmt : '',
                    $passage,
                    $note,
                    $submission_type,
                    $color,
                    $count_meta,
                    $submission_type === 'highlight' ? $identity : null
                ),
            );
            noelclark_v1_ps_highlights_idempotency_put($idempotency_key, 201, $body);

            return noelclark_v1_ps_highlights_response($body, 201);
        }

        if ($actual === 'spam' || $approved === 'spam') {
            $body = array('code' => 'accepted');
            noelclark_v1_ps_highlights_idempotency_put($idempotency_key, 202, $body);

            return noelclark_v1_ps_highlights_response($body, 202);
        }

        $body = array(
            'code' => 'pending',
            'type' => $submission_type,
        );
        noelclark_v1_ps_highlights_idempotency_put($idempotency_key, 202, $body);

        return noelclark_v1_ps_highlights_response($body, 202);
    } finally {
        if ($held_lock && $lock_name !== '') {
            noelclark_v1_ps_highlights_release_lock($lock_name);
        }
    }
}

/**
 * Load approved P.S. notes for a page (direct query; keeps generic exclusions).
 *
 * Oldest-first chronological page. Optional cursor (afterDate + afterId) loads
 * the next page so newer approved entries are never silently omitted.
 *
 * @param int         $page_id    Page ID.
 * @param string|null $after_date GMT mysql datetime cursor (exclusive with after_id).
 * @param int         $after_id   Comment ID cursor.
 * @param int|null    $limit      Page size (defaults to PUBLIC_LIST_MAX).
 * @return array{notes: array<int, array<string, mixed>>, has_more: bool, next_cursor: array{date: string, id: int}|null}
 */
function noelclark_v1_ps_highlights_fetch_approved_notes($page_id, $after_date = null, $after_id = 0, $limit = null) {
    global $wpdb;

    $page_id  = (int) $page_id;
    $after_id = (int) $after_id;
    $limit    = $limit === null ? (int) NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX : (int) $limit;

    if ($limit < 1) {
        $limit = (int) NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX;
    }

    if ($limit > (int) NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX) {
        $limit = (int) NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX;
    }

    if ($page_id <= 0) {
        return array(
            'notes'       => array(),
            'has_more'    => false,
            'next_cursor' => null,
        );
    }

    $flag  = NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG;
    $pass  = NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE;
    $type  = NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE;
    $color = NOELCLARK_V1_PS_HIGHLIGHTS_META_COLOR;
    $idk   = NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY;
    /* Fetch one extra row to detect has_more without a second COUNT query. */
    $fetch = $limit + 1;

    $sql = "SELECT c.comment_ID, c.comment_author, c.comment_date_gmt, c.comment_content,
                MAX(CASE WHEN xm.meta_key = %s THEN xm.meta_value END) AS passage,
                MAX(CASE WHEN xm.meta_key = %s THEN xm.meta_value END) AS submission_type,
                MAX(CASE WHEN xm.meta_key = %s THEN xm.meta_value END) AS highlight_color,
                MAX(CASE WHEN xm.meta_key = %s THEN xm.meta_value END) AS passage_identity
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} fm
                ON fm.comment_id = c.comment_ID AND fm.meta_key = %s AND fm.meta_value = '1'
            LEFT JOIN {$wpdb->commentmeta} xm
                ON xm.comment_id = c.comment_ID AND xm.meta_key IN (%s, %s, %s, %s)
            WHERE c.comment_post_ID = %d
                AND c.comment_approved = '1'";

    $args = array($pass, $type, $color, $idk, $flag, $pass, $type, $color, $idk, $page_id);

    if (is_string($after_date) && $after_date !== '' && $after_id > 0) {
        $sql   .= ' AND (c.comment_date_gmt > %s OR (c.comment_date_gmt = %s AND c.comment_ID > %d))';
        $args[] = $after_date;
        $args[] = $after_date;
        $args[] = $after_id;
    }

    $sql   .= ' GROUP BY c.comment_ID, c.comment_author, c.comment_date_gmt, c.comment_content
            ORDER BY c.comment_date_gmt ASC, c.comment_ID ASC
            LIMIT %d';
    $args[] = $fetch;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $rows = $wpdb->get_results(call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args)));

    if (!is_array($rows)) {
        return array(
            'notes'       => array(),
            'has_more'    => false,
            'next_cursor' => null,
        );
    }

    $has_more = count($rows) > $limit;

    if ($has_more) {
        $rows = array_slice($rows, 0, $limit);
    }

    $notes = array();

    foreach ($rows as $row) {
        $passage = isset($row->passage) ? (string) $row->passage : '';
        $body    = isset($row->comment_content) ? (string) $row->comment_content : '';
        $stype   = isset($row->submission_type) ? (string) $row->submission_type : '';

        /* Require at least a passage or a written note. */
        if ($passage === '' && $body === '') {
            continue;
        }

        $count = null;

        if ($stype === 'highlight' || ($passage !== '' && $body === '')) {
            $count = (int) get_comment_meta((int) $row->comment_ID, NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT, true);

            if ($count < 1) {
                $count = 1;
            }

            if ($stype === '') {
                $stype = 'highlight';
            }
        }

        $payload = noelclark_v1_ps_highlights_public_note_payload(
            isset($row->comment_ID) ? (int) $row->comment_ID : 0,
            isset($row->comment_author) ? (string) $row->comment_author : '',
            isset($row->comment_date_gmt) ? (string) $row->comment_date_gmt : '',
            $passage,
            $body,
            $stype !== '' ? $stype : null,
            isset($row->highlight_color) ? (string) $row->highlight_color : null,
            $count,
            isset($row->passage_identity) ? (string) $row->passage_identity : null
        );

        /* Cursor fields for clients (GMT mysql + id). */
        $payload['dateGmt'] = isset($row->comment_date_gmt) ? (string) $row->comment_date_gmt : '';

        $notes[] = $payload;
    }

    $next_cursor = null;

    if ($has_more && !empty($notes)) {
        $last        = $notes[count($notes) - 1];
        $next_cursor = array(
            'date' => isset($last['dateGmt']) ? (string) $last['dateGmt'] : '',
            'id'   => isset($last['id']) ? (int) $last['id'] : 0,
        );
    }

    return array(
        'notes'       => $notes,
        'has_more'    => $has_more,
        'next_cursor' => $next_cursor,
    );
}

/**
 * REST: public approved-only notes list for the pilot essay.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function noelclark_v1_ps_highlights_rest_list(WP_REST_Request $request) {
    $page_id = (int) $request->get_param('pageId');

    if (!noelclark_v1_ps_highlights_is_allowed_page_id($page_id)) {
        return noelclark_v1_ps_highlights_response(array('code' => 'forbidden_page'), 403);
    }

    $after_date = $request->get_param('afterDate');
    $after_id   = (int) $request->get_param('afterId');

    if (!is_string($after_date) || $after_date === '') {
        $after_date = null;
        $after_id   = 0;
    }

    $result = noelclark_v1_ps_highlights_fetch_approved_notes($page_id, $after_date, $after_id);

    return noelclark_v1_ps_highlights_response(
        array(
            'code'       => 'ok',
            'count'      => count($result['notes']),
            'notes'      => $result['notes'],
            'hasMore'    => !empty($result['has_more']),
            'nextCursor' => $result['next_cursor'],
        ),
        200
    );
}

/**
 * Register submit + approved-list REST routes.
 *
 * @return void
 */
function noelclark_v1_ps_highlights_register_rest_routes() {
    register_rest_route(
        NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE,
        NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE,
        array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => 'noelclark_v1_ps_highlights_rest_submit',
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => 'noelclark_v1_ps_highlights_rest_list',
                'permission_callback' => '__return_true',
                'args'                => array(
                    'pageId' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'afterDate' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'afterId' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        )
    );
}
add_action('rest_api_init', 'noelclark_v1_ps_highlights_register_rest_routes');

/**
 * When a pending/spam P.S. highlight is later approved, fold into the
 * canonical public entry instead of leaving a duplicate public row.
 *
 * @param string     $new_status New status slug.
 * @param string     $old_status Old status slug.
 * @param WP_Comment $comment    Comment.
 * @return void
 */
function noelclark_v1_ps_highlights_on_status_transition($new_status, $old_status, $comment) {
    if (!$comment instanceof WP_Comment) {
        return;
    }

    if ($new_status !== 'approve' || $old_status === 'approve') {
        return;
    }

    $comment_id = (int) $comment->comment_ID;

    if (get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_FLAG, true) !== '1') {
        return;
    }

    $type = (string) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_TYPE, true);

    if ($type !== 'highlight') {
        return;
    }

    $identity = (string) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_IDENTITY, true);

    if ($identity === '') {
        $passage  = (string) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PASSAGE, true);
        $prefix   = (string) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_PREFIX, true);
        $suffix   = (string) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_SUFFIX, true);
        $identity = noelclark_v1_ps_highlights_passage_identity($passage, $prefix, $suffix);
    }

    if ($identity === '') {
        return;
    }

    $page_id   = (int) $comment->comment_post_ID;
    $lock_name = noelclark_v1_ps_highlights_lock_name($page_id, $identity);

    if (!noelclark_v1_ps_highlights_acquire_lock($lock_name)) {
        /* Fail closed: demote back to pending so a later retry can reconcile. */
        wp_set_comment_status($comment_id, 'hold');

        return;
    }

    try {
        $canonical = noelclark_v1_ps_highlights_find_approved_highlight($page_id, $identity);

        if ($canonical && isset($canonical->comment_ID) && (int) $canonical->comment_ID !== $comment_id) {
            $count = noelclark_v1_ps_highlights_increment_count((int) $canonical->comment_ID);

            if ($count === false) {
                wp_set_comment_status($comment_id, 'hold');

                return;
            }

            /* Keep chronological position of the original public entry. */
            wp_set_comment_status($comment_id, 'hold');
            update_comment_meta($comment_id, '_noelclark_ps_merged_into', (string) (int) $canonical->comment_ID);

            return;
        }

        /* This becomes (or remains) the canonical public entry. */
        $current = (int) get_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT, true);

        if ($current < 1) {
            update_comment_meta($comment_id, NOELCLARK_V1_PS_HIGHLIGHTS_META_COUNT, '1');
        }
    } finally {
        noelclark_v1_ps_highlights_release_lock($lock_name);
    }
}
add_action('transition_comment_status', 'noelclark_v1_ps_highlights_on_status_transition', 10, 3);

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
    if ($type === 'note') {
        $label = 'P.S. note';
    } elseif ($type === 'highlight_note') {
        $label = 'P.S. highlight + note';
    } else {
        $label = 'P.S. highlight';
    }

    $note = is_string($text) ? trim(wp_strip_all_tags($text)) : '';

    if ($passage !== '') {
        $excerpt = function_exists('mb_substr') ? mb_substr($passage, 0, 80) : substr($passage, 0, 80);
        $line    = $label . ': "' . $excerpt . (strlen($passage) > 80 ? '…' : '') . '"';

        if ($note !== '') {
            $line .= "\n\n" . $note;
        }

        return $line;
    }

    if ($note !== '') {
        return $label . "\n\n" . $note;
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
    $list_url = esc_url_raw(
        add_query_arg(
            'pageId',
            (int) $page_id,
            rest_url(NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE . NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE)
        )
    );

    return array(
        'submitUrl'        => esc_url_raw(rest_url(NOELCLARK_V1_PS_HIGHLIGHTS_REST_NAMESPACE . NOELCLARK_V1_PS_HIGHLIGHTS_REST_ROUTE)),
        'listUrl'          => $list_url,
        'nonce'            => wp_create_nonce(NOELCLARK_V1_PS_HIGHLIGHTS_NONCE_ACTION),
        'pageId'           => (int) $page_id,
        'passageMax'       => NOELCLARK_V1_PS_HIGHLIGHTS_PASSAGE_MAX,
        'noteMax'          => NOELCLARK_V1_PS_HIGHLIGHTS_NOTE_MAX,
        'displayNameMax'   => NOELCLARK_V1_PS_HIGHLIGHTS_DISPLAY_NAME_MAX,
        'contextMax'       => NOELCLARK_V1_PS_HIGHLIGHTS_CONTEXT_MAX,
        'listPageSize'     => NOELCLARK_V1_PS_HIGHLIGHTS_PUBLIC_LIST_MAX,
        'colors'           => noelclark_v1_ps_highlights_allowed_colors(),
        'candidateSeconds' => 40,
        'penColor'         => '#A21EFF',
        'configGlobal'     => 'noelclarkV1PsHighlights',
    );
}
