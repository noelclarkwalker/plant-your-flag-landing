<?php
/**
 * Mail Room private admin inbox + submission detail — Admin Slice 1.
 *
 * wp-admin screen gated by NOELCLARK_V1_MAIL_ROOM_CAPABILITY.
 * Writes are the letter-page editorial status control and the private note.
 * Status updates editorial_status and updated_at, and appends one
 * editorial_status_changed event. A note updates private_note only.
 * Opening a letter does not write.
 * No public forms, REST, or AJAX.
 * URLs carry page, filters, paged, mr (an opaque mr_ref), and a save notice.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/permission-ceiling.php';
require_once __DIR__ . '/author-requests.php';

/** @var string wp-admin page slug. */
const NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG = 'noelclark-mail-room';

/** @var int Maximum stored length of one private editorial note. */
const NOELCLARK_V1_MAIL_ROOM_PRIVATE_NOTE_MAX = 10000;

/**
 * Register the private Mail Room menu.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_register_menu() {
    add_menu_page(
        'Mail Room',
        'Mail Room',
        NOELCLARK_V1_MAIL_ROOM_CAPABILITY,
        NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG,
        'noelclark_v1_mail_room_admin_render_page',
        'dashicons-email-alt',
        26
    );
}
add_action('admin_menu', 'noelclark_v1_mail_room_admin_register_menu');

/**
 * Enqueue admin styles on the Mail Room screen only.
 * Fonts are enqueued separately, and only on this screen.
 *
 * @param string $hook_suffix Current admin screen hook.
 * @return void
 */
function noelclark_v1_mail_room_admin_enqueue($hook_suffix) {
    if ($hook_suffix !== 'toplevel_page_' . NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG) {
        return;
    }

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return;
    }

    wp_enqueue_style(
        'noelclark-v1-mail-room-admin',
        get_template_directory_uri() . '/assets/css/mail-room-admin.css',
        array('noelclark-v1-mail-room-admin-fonts'),
        noelclark_v1_asset_version('assets/css/mail-room-admin.css')
    );
}
add_action('admin_enqueue_scripts', 'noelclark_v1_mail_room_admin_enqueue');

/**
 * Raw GET string, unslashed and trimmed. Callers must allowlist.
 *
 * @param string $key Query key.
 * @return string
 */
function noelclark_v1_mail_room_admin_request_string($key) {
    if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
        return '';
    }

    return trim(wp_unslash($_GET[$key]));
}

/**
 * Raw POST string, unslashed and trimmed. Callers must allowlist.
 *
 * @param string $key Body key.
 * @return string
 */
function noelclark_v1_mail_room_admin_post_string($key) {
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return '';
    }

    return trim(wp_unslash($_POST[$key]));
}

/**
 * Statuses an editor may save. Published is omitted until a real public
 * Mail Room piece can be linked to the letter.
 *
 * @return array<string, string> Stored value => label.
 */
function noelclark_v1_mail_room_admin_writable_statuses() {
    $statuses = noelclark_v1_mail_room_admin_editorial_statuses();
    $writable = array();

    foreach (array(NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW, 'reviewed', 'selected', 'not_selected') as $stored) {
        if (isset($statuses[$stored])) {
            $writable[$stored] = $statuses[$stored];
        }
    }

    return $writable;
}

/**
 * Parse and allowlist inbox filters from the request.
 *
 * @return array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]}
 */
function noelclark_v1_mail_room_admin_parse_filters() {
    $filters = array(
        'status' => '',
        'share'  => '',
        'ref'    => '',
    );
    $notices     = array();
    $ref_invalid = false;
    $statuses    = noelclark_v1_mail_room_admin_editorial_statuses();
    $answers     = noelclark_v1_mail_room_admin_share_filter_answers();

    $status = sanitize_key(noelclark_v1_mail_room_admin_request_string('status'));

    if ($status !== '') {
        if (isset($statuses[$status])) {
            $filters['status'] = $status;
        } else {
            $notices[] = 'Unrecognized status filter was ignored.';
        }
    }

    $share = sanitize_key(noelclark_v1_mail_room_admin_request_string('share'));

    if ($share !== '') {
        if (isset($answers[$share])) {
            $filters['share'] = $share;
        } else {
            $notices[] = 'Unrecognized sharing filter was ignored.';
        }
    }

    $ref_raw = noelclark_v1_mail_room_admin_request_string('ref');

    if ($ref_raw !== '') {
        $ref = noelclark_v1_mail_room_admin_normalize_ref($ref_raw);

        if ($ref === null) {
            $ref_invalid = true;
        } else {
            $filters['ref'] = $ref;
        }
    }

    return array(
        'filters'     => $filters,
        'page'        => max(1, absint(noelclark_v1_mail_room_admin_request_string('paged'))),
        'ref_invalid' => $ref_invalid,
        'notices'     => $notices,
    );
}

/**
 * Admin URL for this screen. Values must already be allowlisted.
 *
 * @param array<string, string|int> $args Query args; empty values are dropped.
 * @return string Unescaped URL.
 */
function noelclark_v1_mail_room_admin_url($args = array()) {
    $query = array('page' => NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG);

    foreach ($args as $key => $value) {
        if ($value === '' || $value === null || ($key === 'paged' && (int) $value <= 1)) {
            continue;
        }

        $query[$key] = rawurlencode((string) $value);
    }

    return add_query_arg($query, admin_url('admin.php'));
}

/**
 * Filter args preserved across inbox, pagination, and detail links.
 *
 * @param array{status: string, share: string, ref: string} $filters Validated filters.
 * @param int $page Current page.
 * @return array<string, string|int>
 */
function noelclark_v1_mail_room_admin_context_args($filters, $page) {
    return array(
        'status' => $filters['status'],
        'share'  => $filters['share'],
        'ref'    => $filters['ref'],
        'paged'  => (int) $page,
    );
}

/**
 * Muted placeholder text.
 *
 * @param string $text Plain text.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_muted_html($text) {
    return '<span class="nc-mr-admin__muted">' . esc_html($text) . '</span>';
}

/**
 * Stored code shown as monospace.
 *
 * @param string $value Stored value.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_code_html($value) {
    return '<code class="nc-mr-admin__code">' . esc_html($value) . '</code>';
}

/**
 * GMT datetime rendered in US Eastern time (EDT/EST), GMT on hover.
 *
 * @param mixed $gmt Stored GMT datetime.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_time_html($gmt) {
    if (!is_string($gmt) || $gmt === '' || $gmt === '0000-00-00 00:00:00') {
        return noelclark_v1_mail_room_admin_muted_html('Not recorded');
    }

    $timestamp = strtotime($gmt . ' UTC');

    if ($timestamp === false) {
        return noelclark_v1_mail_room_admin_code_html($gmt);
    }

    $display = function_exists('noelclark_v1_mail_room_format_eastern')
        ? noelclark_v1_mail_room_format_eastern($gmt)
        : '';

    return sprintf(
        '<time datetime="%1$s" title="%2$s">%3$s</time>',
        esc_attr(gmdate('c', $timestamp)),
        esc_attr($gmt . ' GMT'),
        esc_html($display !== '' ? $display : $gmt . ' GMT')
    );
}

/**
 * Editorial status badge.
 *
 * @param mixed $stored Stored editorial_status, or null when the state row is missing.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_status_badge_html($stored) {
    if ($stored === null || $stored === '') {
        return '<span class="nc-mr-admin__badge nc-mr-admin__badge--alert">Status record missing</span>';
    }

    $statuses = noelclark_v1_mail_room_admin_editorial_statuses();
    $stored   = (string) $stored;

    if (!isset($statuses[$stored])) {
        return '<span class="nc-mr-admin__badge">Unrecognized status</span> '
            . noelclark_v1_mail_room_admin_code_html($stored);
    }

    return sprintf(
        '<span class="nc-mr-admin__badge nc-mr-admin__badge--status nc-mr-admin__badge--%1$s">%2$s</span>',
        esc_attr(sanitize_html_class($stored)),
        esc_html($statuses[$stored])
    );
}

/**
 * Plain-English meaning of an editorial status.
 *
 * @param mixed $stored Stored editorial_status, or null when the state row is missing.
 * @return string Plain text (caller escapes); empty when unknown.
 */
function noelclark_v1_mail_room_admin_status_meaning($stored) {
    $meanings = noelclark_v1_mail_room_admin_editorial_status_meanings();

    return ($stored !== null && isset($meanings[(string) $stored])) ? $meanings[(string) $stored] : '';
}

/**
 * Sharing choice exactly as the sender saw it.
 *
 * @param mixed $stored Stored sharing value.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_treatment_html($stored) {
    if ($stored === null || $stored === '') {
        return noelclark_v1_mail_room_admin_muted_html('Not recorded');
    }

    $treatments = noelclark_v1_mail_room_admin_treatments();
    $stored     = (string) $stored;

    if (!isset($treatments[$stored])) {
        return noelclark_v1_mail_room_admin_muted_html('Unrecognized sharing choice') . ' '
            . noelclark_v1_mail_room_admin_code_html($stored);
    }

    return sprintf(
        '<span class="nc-mr-admin__treatment nc-mr-admin__treatment--%1$s">%2$s</span>',
        esc_attr(sanitize_html_class($stored)),
        esc_html(noelclark_v1_mail_room_admin_permission_label($stored, ''))
    );
}

/**
 * Plain-text excerpt for inbox scanning.
 *
 * @param mixed $text Letter prefix.
 * @return string Plain text (caller escapes).
 */
function noelclark_v1_mail_room_admin_excerpt($text) {
    $text  = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    $limit = (int) NOELCLARK_V1_MAIL_ROOM_ADMIN_EXCERPT_CHARS;

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '…';
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit)) . '…';
}

/**
 * Inline notice.
 *
 * @param string $type    warning|error|info.
 * @param string $message Plain text.
 * @return void
 */
function noelclark_v1_mail_room_admin_notice($type, $message) {
    printf(
        '<div class="notice notice-%1$s inline nc-mr-admin__notice"><p>%2$s</p></div>',
        esc_attr($type),
        esc_html($message)
    );
}

/**
 * Derived share answers the inbox filter can request.
 *
 * Full permission is a named public credit. Anonymous is the recorded
 * restriction. Private is everything that does not authorize publication.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_share_filter_answers() {
    return array(
        'no'         => 'Private',
        'restricted' => 'Anonymous only',
        'yes'        => 'Full permission',
    );
}

/**
 * Inline icon. Markup is fixed, never caller content.
 *
 * @param string $name Icon name.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_icon($name) {
    $icons = array(
        'lock'    => '<rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path>',
        'shield'  => '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path>',
        'check'   => '<path d="M5 12.5l4.5 4.5L19 7"></path>',
        'x'       => '<path d="M6 6l12 12M18 6L6 18"></path>',
        'copy'    => '<rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15V5a2 2 0 0 1 2-2h10"></path>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path>',
        'chevron' => '<path d="M9 6l6 6-6 6"></path>',
        'back'    => '<path d="M19 12H5M11 18l-6-6 6-6"></path>',
        'eye'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>',
        'guide'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
    );

    if (!isset($icons[$name])) {
        return '';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$name] . '</svg>';
}

/**
 * Copy button. The value is already on the page.
 *
 * @param string $value Text to copy.
 * @param string $label Button label.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_copy_button($value, $label) {
    return sprintf(
        '<button type="button" class="btn ghost" data-nc-mr-copy="%1$s" data-nc-mr-copy-label="%2$s">%3$s<span>%2$s</span></button>',
        esc_attr($value),
        esc_attr($label),
        noelclark_v1_mail_room_admin_icon('copy')
    );
}

/**
 * US Eastern date and time parts for the reference layout.
 *
 * @param mixed $gmt Stored GMT datetime.
 * @return array{date: string, time: string}|null
 */
function noelclark_v1_mail_room_admin_eastern_parts($gmt) {
    if (!is_string($gmt) || $gmt === '' || $gmt === '0000-00-00 00:00:00') {
        return null;
    }

    try {
        $time = new DateTimeImmutable($gmt, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return null;
    }

    $local = $time->setTimezone(new DateTimeZone('America/New_York'));

    return array(
        'date' => $local->format('M j, Y'),
        'time' => $local->format('g:i A T'),
    );
}

/**
 * Opening of a letter, cut at a word boundary around 60 characters.
 *
 * @param mixed $text Letter text.
 * @return string Plain text. Empty when the letter is empty.
 */
function noelclark_v1_mail_room_admin_opening_title($text) {
    $text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));

    if ($text === '') {
        return '';
    }

    $limit  = 60;
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);

    if ($length <= $limit) {
        return $text;
    }

    $slice = function_exists('mb_substr') ? mb_substr($text, 0, $limit, 'UTF-8') : substr($text, 0, $limit);
    $space = function_exists('mb_strrpos') ? mb_strrpos($slice, ' ', 0, 'UTF-8') : strrpos($slice, ' ');

    if ($space !== false && $space >= 24) {
        $slice = function_exists('mb_substr') ? mb_substr($slice, 0, $space, 'UTF-8') : substr($slice, 0, $space);
    }

    return rtrim($slice) . '…';
}

/**
 * Whether a withdrawal request is pending. Handled is not a block.
 *
 * @param array<string, mixed> $row Submission or inbox row.
 * @return bool
 */
function noelclark_v1_mail_room_admin_is_withdrawal_pending($row) {
    $withdrawal = isset($row['withdrawal_request_status']) ? (string) $row['withdrawal_request_status'] : '';

    return $withdrawal === NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING;
}

/**
 * Whether the new-letter alert's last try failed and it remains eligible for another.
 *
 * @param array<string, mixed> $row Submission or inbox row.
 * @return bool
 */
function noelclark_v1_mail_room_admin_alert_failed($row) {
    return isset($row['notify_status']) && (string) $row['notify_status'] === NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED;
}

/**
 * Plain label for a stored sharing choice, including a named credit.
 *
 * @param mixed $choice Stored choice.
 * @param mixed $credit Stored credit.
 * @return string
 */
function noelclark_v1_mail_room_admin_permission_label($choice, $credit) {
    $choice = trim((string) $choice);
    $credit = trim((string) $credit);
    $labels = array(
        'not_public'   => 'Keep it private',
        'anonymous'    => 'Anonymously',
        'named_credit' => 'With their name',
    );

    if (!isset($labels[$choice])) {
        return $choice === '' ? 'Not recorded' : $choice;
    }

    if ($choice === 'named_credit' && $credit !== '') {
        return $labels[$choice] . ' — ' . $credit;
    }

    return $labels[$choice];
}

/**
 * Original choice versus the permission the verdict actually follows.
 *
 * A broader operative value is not a separate grant, so the current
 * permission shown here cannot outrun the original choice.
 *
 * @param array<string, mixed> $row Submission row.
 * @return array{differs: bool, original: string, current: string}
 */
function noelclark_v1_mail_room_admin_effective_permission($row) {
    $original_choice = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $original_credit = isset($row['public_credit']) ? (string) $row['public_credit'] : '';
    $original_label  = noelclark_v1_mail_room_admin_permission_label($original_choice, $original_credit);

    if (!isset($row['editorial_status']) || $row['editorial_status'] === null) {
        return array(
            'differs'  => true,
            'original' => $original_label,
            'current'  => 'Not recorded',
        );
    }

    $operative_choice = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $operative_credit = isset($row['operative_public_credit']) ? (string) $row['operative_public_credit'] : '';
    $original_level   = noelclark_v1_mail_room_admin_share_choice_level($original_choice, $original_credit);
    $operative_level  = noelclark_v1_mail_room_admin_share_choice_level($operative_choice, $operative_credit);
    $effective_choice = $operative_choice;
    $effective_credit = $operative_credit;

    if ($original_level !== 'unknown' && $operative_level !== 'unknown') {
        $original_rank  = noelclark_v1_mail_room_admin_share_level_rank($original_level);
        $operative_rank = noelclark_v1_mail_room_admin_share_level_rank($operative_level);

        if ($operative_rank > $original_rank) {
            $effective_choice = $original_choice;
            $effective_credit = $original_credit;
        }
    }

    $current_label = noelclark_v1_mail_room_admin_permission_label($effective_choice, $effective_credit);

    return array(
        'differs'  => $original_label !== $current_label,
        'original' => $original_label,
        'current'  => $current_label,
    );
}

/**
 * Clipboard handler for the copy buttons on this screen.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_print_copy_script() {
    ?>
    <script>
    document.addEventListener('click', function (event) {
        var statusLink = event.target.closest('[data-nc-mr-status]');
        if (statusLink) {
            var form = statusLink.closest('form');
            var input = form ? form.querySelector('input[name="status"]') : null;
            if (form && input) {
                event.preventDefault();
                input.value = statusLink.getAttribute('data-nc-mr-status') || '';
                form.submit();
            }
            return;
        }
        var button = event.target.closest('[data-nc-mr-copy]');
        if (!button) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        var value = button.getAttribute('data-nc-mr-copy') || '';
        var label = button.getAttribute('data-nc-mr-copy-label') || 'Copy';
        var text = button.querySelector('span');
        function done() {
            button.classList.add('is-copied');
            if (text) {
                text.textContent = 'Copied';
            }
            window.setTimeout(function () {
                button.classList.remove('is-copied');
                if (text) {
                    text.textContent = label;
                }
            }, 1600);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(done);
            return;
        }
        var area = document.createElement('textarea');
        area.value = value;
        document.body.appendChild(area);
        area.select();
        try {
            if (document.execCommand('copy')) {
                done();
            }
        } catch (error) {}
        document.body.removeChild(area);
    });
    </script>
    <?php
}

/**
 * Screen entry point. Dispatches inbox or detail.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_page() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $parsed = noelclark_v1_mail_room_admin_parse_filters();
    $mr_raw = noelclark_v1_mail_room_admin_request_string('mr');

    echo '<div class="wrap nc-mr-admin">';

    if ($mr_raw !== '') {
        noelclark_v1_mail_room_admin_render_detail($mr_raw, $parsed);
    } else {
        noelclark_v1_mail_room_admin_render_inbox($parsed);
    }

    echo '</div>';
    noelclark_v1_mail_room_admin_print_copy_script();
}

/**
 * One stored event as the permission-pause walker expects it.
 *
 * @param string $event_type   Stored event type.
 * @param mixed  $payload_json Stored payload.
 * @return array{type: string, requested: string, outcome: string}
 */
function noelclark_v1_mail_room_admin_permission_event_entry($event_type, $payload_json) {
    $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($payload_json);

    return array(
        'type'      => (string) $event_type,
        'requested' => is_array($pairs) && isset($pairs['requested']) ? (string) $pairs['requested'] : '',
        'outcome'   => is_array($pairs) && isset($pairs['outcome']) ? (string) $pairs['outcome'] : '',
    );
}

/**
 * Normalized permission events from a history list, oldest first.
 *
 * @param array<int, array<string, mixed>> $rows Event rows.
 * @return array<int, array{type: string, requested: string, outcome: string}>
 */
function noelclark_v1_mail_room_admin_permission_event_list($rows) {
    $events = array();

    if (!is_array($rows)) {
        return $events;
    }

    foreach ($rows as $row) {
        if (!is_array($row) || !isset($row['event_type'])) {
            continue;
        }

        $events[] = noelclark_v1_mail_room_admin_permission_event_entry(
            (string) $row['event_type'],
            isset($row['payload_json']) ? $row['payload_json'] : ''
        );
    }

    return $events;
}

/**
 * Copy the open permission request onto each inbox row. Not a stored column.
 *
 * @param array<int, array<string, mixed>> $rows Inbox rows.
 * @return array{ok: true, rows: array<int, array<string, mixed>>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_attach_pending_permission_requests($rows) {
    $refs = array();

    foreach ($rows as $row) {
        if (is_array($row) && isset($row['mr_ref']) && is_string($row['mr_ref']) && $row['mr_ref'] !== '') {
            $refs[] = $row['mr_ref'];
        }
    }

    $loaded = noelclark_v1_mail_room_admin_load_permission_events($refs);

    if (empty($loaded['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($loaded['code']) ? (string) $loaded['code'] : 'storage_error',
        );
    }

    $author = noelclark_v1_mail_room_admin_load_author_request_events($refs);

    if (empty($author['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($author['code']) ? (string) $author['code'] : 'storage_error',
        );
    }

    $by_ref = isset($loaded['by_ref']) && is_array($loaded['by_ref']) ? $loaded['by_ref'] : array();
    $author_by_ref = isset($author['by_ref']) && is_array($author['by_ref']) ? $author['by_ref'] : array();

    foreach ($rows as $index => $row) {
        $ref    = is_array($row) && isset($row['mr_ref']) ? (string) $row['mr_ref'] : '';
        $events = isset($by_ref[$ref]) ? noelclark_v1_mail_room_admin_permission_event_list($by_ref[$ref]) : array();
        $rows[$index]['pending_permission_request'] = noelclark_v1_mail_room_permission_open_requested($events);
        $author_events = isset($author_by_ref[$ref]) && is_array($author_by_ref[$ref]) ? $author_by_ref[$ref] : array();
        $credit_events = $author_events;

        if (isset($by_ref[$ref]) && is_array($by_ref[$ref])) {
            foreach ($by_ref[$ref] as $permission_event) {
                $credit_events[] = $permission_event;
            }
        }

        $rows[$index]['author_request_hold'] = noelclark_v1_mail_room_admin_author_hold_for_row(
            $author_events,
            is_array($row) ? $row : array()
        );
        $rows[$index]['author_credit_broadening_valid'] = noelclark_v1_mail_room_admin_author_broadening_valid(
            $credit_events,
            is_array($row) ? $row : array()
        );
        $rows[$index]['author_withdrawal_standing'] = noelclark_v1_mail_room_admin_author_withdrawal_standing(
            $credit_events,
            is_array($row) ? $row : array()
        );
    }

    return array(
        'ok'   => true,
        'rows' => $rows,
    );
}

/**
 * One letter's share view, from the stored assessments.
 *
 * @param array<string, mixed> $row       Submission or inbox row.
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_admin_letter_view($row, $responses = array()) {
    if ($responses === array()) {
        $responses = array(
            'ok'   => true,
            'rows' => array(),
        );
    }

    $assessment  = noelclark_v1_mail_room_admin_share_assessments($row, $responses);
    $verdicts    = noelclark_v1_mail_room_admin_share_verdicts();
    $letter      = $assessment['items'][0];
    $attribution = $assessment['items'][1];
    $verdict     = isset($verdicts[$letter['verdict']]) ? $letter['verdict'] : 'no';
    $explanation = noelclark_v1_mail_room_admin_share_explanation($letter, $attribution);

    foreach ($assessment['notes'] as $note) {
        if (strpos($note, 'sharing record is missing') !== false) {
            $explanation = 'The current sharing record is missing, so public sharing is not permitted until it is confirmed.';
            break;
        }

        if (strpos($note, 'could not be confirmed') !== false) {
            $explanation = 'A stored sharing choice could not be confirmed, so public sharing is not permitted.';
            break;
        }
    }

    if ($verdict === 'yes') {
        $mode = 'yes';
    } elseif ($verdict === 'restricted' && $attribution['verdict'] === 'no') {
        $mode = 'anonymous';
    } elseif ($verdict === 'restricted') {
        // A recorded public credit is attribution, not a sharing restriction.
        $mode    = 'named';
        $verdict = 'yes';
    } else {
        $mode = 'no';
    }

    return array(
        'verdict'     => $verdict,
        'mode'        => $mode,
        'explanation' => $explanation,
        'credit'      => (string) $attribution['credit'],
        'guidance'    => noelclark_v1_mail_room_admin_share_guidance($letter, $attribution),
        'notes'       => $assessment['notes'],
    );
}

/**
 * Short line under an inbox share pill.
 *
 * @param array<string, mixed> $view Letter view.
 * @return string
 */
function noelclark_v1_mail_room_admin_share_row_line($view) {
    if (strpos((string) $view['explanation'], 'request to reduce sharing is pending') !== false) {
        return 'New public sharing is paused.';
    }

    if (strpos((string) $view['explanation'], 'request to take it down is still open') !== false) {
        return 'New public sharing is paused.';
    }

    if (strpos((string) $view['explanation'], 'request to make it anonymous is still open') !== false) {
        return 'New public sharing is paused.';
    }

    if (strpos((string) $view['explanation'], 'credit request is open') !== false) {
        $held_credit = trim((string) $view['credit']);

        return $held_credit !== ''
            ? 'We\'re not making a new share. The current credit stays: ' . $held_credit . '.'
            : 'We\'re not making a new share. The current credit stays.';
    }

    if ($view['mode'] === 'named' && $view['credit'] !== '') {
        return 'Credit as ' . $view['credit'] . ' — exact spelling.';
    }

    if ($view['mode'] === 'anonymous') {
        return 'Can be published anonymously.';
    }

    if ($view['mode'] === 'yes') {
        return 'No extra conditions on record.';
    }

    return 'We cannot publish this letter.';
}

/**
 * Verdict block copy. The quoted sentence is the stored explanation.
 *
 * @param array<string, mixed> $view Letter view.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_admin_verdict_presentation($view) {
    if (strpos((string) $view['explanation'], 'credit request is open') !== false) {
        if ($view['mode'] === 'anonymous') {
            return array(
                'tone'      => 'restricted',
                'title'     => 'Interesting! A Letter We Can Use',
                'condition' => '(Anonymous Only)',
                'line'      => 'We\'re not making a new share while a credit request is open. The current credit stays.',
            );
        }

        if ($view['mode'] !== 'no') {
            return array(
                'tone'      => 'yes',
                'title'     => 'Great! A Letter We Can Use',
                'condition' => '(Full Permission)',
                'line'      => 'We\'re not making a new share while a credit request is open. The current credit stays.',
            );
        }
    }

    if ($view['mode'] === 'named' || $view['mode'] === 'yes') {
        return array(
            'tone'      => 'yes',
            'title'     => 'Great! A Letter We Can Use',
            'condition' => '(Full Permission)',
            'line'      => '',
        );
    }

    if ($view['mode'] === 'anonymous') {
        return array(
            'tone'      => 'restricted',
            'title'     => 'Interesting! A Letter We Can Use',
            'condition' => '(Anonymous Only)',
            'line'      => 'This letter can be published anonymously.',
        );
    }

    if (strpos((string) $view['explanation'], 'withdrawal request is pending') !== false) {
        return array(
            'tone'      => 'no',
            'title'     => 'Shhh! Keep This One Between Us',
            'condition' => '(Withdrawal pending)',
            'line'      => 'New public sharing is paused. This request is not a decision.',
        );
    }

    if (strpos((string) $view['explanation'], 'request to reduce sharing is pending') !== false
        || strpos((string) $view['explanation'], 'request to take it down is still open') !== false
        || strpos((string) $view['explanation'], 'request to make it anonymous is still open') !== false
    ) {
        $condition = '(Permission change pending)';

        if (strpos((string) $view['explanation'], 'request to take it down is still open') !== false) {
            $condition = '(Takedown pending)';
        } elseif (strpos((string) $view['explanation'], 'request to make it anonymous is still open') !== false) {
            $condition = '(Anonymous request pending)';
        }

        return array(
            'tone'      => 'no',
            'title'     => 'Shhh! Keep This One Between Us',
            'condition' => $condition,
            'line'      => 'New public sharing is paused. This request is not a decision.',
        );
    }

    return array(
        'tone'      => 'no',
        'title'     => 'Shhh! Keep This One Between Us',
        'condition' => '(Private)',
        'line'      => 'We cannot publish this letter.',
    );
}

/**
 * The three rule cards for one permission mode.
 *
 * @param array<string, mixed> $view Letter view.
 * @return array{can: string[], cant: string[], private: string[]}
 */
function noelclark_v1_mail_room_admin_rule_cards($view) {
    $mode   = (string) $view['mode'];
    $credit = (string) $view['credit'];
    $private = array(
        'Their contact name and email.',
        'Questionnaire answers — they have no publishing permission of their own.',
        'Identifying details about other people they mention, unless permission exists.',
        'Anything they restrict further or withdraw later.',
    );

    if ($mode === 'named' || $mode === 'anonymous') {
        $can = array(
            'Share the whole letter or a shorter excerpt.',
            'Fix typos and formatting.',
            'Shorten it, as long as the meaning stays the same.',
            'Remove private or identifying details before sharing.',
        );

        if ($mode === 'named' && $credit !== '') {
            array_unshift($can, 'Credit them as ' . $credit . '. That name is attribution, not an extra limit.');
        }

        if ($mode === 'anonymous') {
            array_unshift($can, 'Publish it anonymously, in whole or as a shorter excerpt.');
        }

        return array(
            'can'     => $can,
            'cant'    => array(
                'Add, swap, or reword anything so it says what they didn\'t write.',
                $mode === 'anonymous'
                    ? 'Include their name or anything that identifies them.'
                    : 'Credit them by their private contact name.',
                'Publish their questionnaire answers.',
            ),
            'private' => $private,
        );
    }

    if ($mode === 'yes') {
        return array(
            'can'     => array(
                'Share the whole letter or a shorter excerpt.',
                'No further limit on the letter text is recorded.',
            ),
            'cant'    => array(
                'Treat this as selection or publication.',
                'Publish their questionnaire answers.',
                'Use their private contact name as a credit.',
            ),
            'private' => $private,
        );
    }

    return array(
        'can'     => array(
            'Read it.',
            'Review it privately, as often as we like.',
        ),
        'cant'    => array(
            'Share any part of it publicly — whole, excerpt, or paraphrase.',
            'Quote it or credit it anywhere.',
        ),
        'private' => $private,
    );
}

/**
 * Share pill for the inbox and the color key.
 *
 * @param string $verdict no|restricted|yes.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_share_pill($verdict) {
    $pills = array(
        'no'         => array('v-no', 'lock', 'Private'),
        'restricted' => array('v-res', 'shield', 'Anonymous only'),
        'yes'        => array('v-yes', 'check', 'Full permission'),
    );
    $pill = isset($pills[$verdict]) ? $pills[$verdict] : $pills['no'];

    return '<span class="pill ' . esc_attr($pill[0]) . '">' . noelclark_v1_mail_room_admin_icon($pill[1]) . esc_html($pill[2]) . '</span>';
}

/**
 * Editorial status for an inbox card: approved icon plus label.
 *
 * @param mixed $stored Stored editorial_status.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_status_pill($stored) {
    if ($stored === null || $stored === '') {
        return '<span class="pill flag flag-alert">Status record missing</span>';
    }

    $statuses = noelclark_v1_mail_room_admin_editorial_statuses();
    $stored   = (string) $stored;

    if (!isset($statuses[$stored])) {
        return '<span class="nc-mr-admin__status-mark">' . esc_html('Unrecognized') . '</span> '
            . noelclark_v1_mail_room_admin_code_html($stored);
    }

    return '<span class="nc-mr-admin__status-mark">'
        . noelclark_v1_mail_room_admin_status_icon($stored)
        . '<span>' . esc_html($statuses[$stored]) . '</span></span>';
}

/**
 * Editorial-status mark. These are not sharing-permission icons.
 *
 * @param string $stored Stored editorial_status.
 * @return string Safe HTML, or empty when the status is unknown.
 */
function noelclark_v1_mail_room_admin_status_icon($stored) {
    $marks = array(
        'new'          => '<circle cx="17" cy="17" r="10.75" stroke="#000100" stroke-width="1.5"/>',
        'reviewed'     => '<ellipse cx="17" cy="17" rx="13.2" ry="11" stroke="#000100" stroke-width="1.6"/>'
            . '<circle cx="17" cy="17" r="6.5" fill="#000100" fill-opacity="0.07" stroke="#000100" stroke-width="1.6"/>'
            . '<circle cx="17" cy="17" r="2.8" fill="#000100"/>',
        'selected'     => '<rect width="34" height="34" rx="2" fill="#1E9E5A"/>'
            . '<path d="M7.2 17.8 L14.2 25.4 L27 8.6" stroke="#000100" stroke-width="3.6"/>',
        'not_selected' => '<rect width="34" height="34" rx="2" fill="#9E1B1B"/>'
            . '<path d="M8.2 8.2 L25.8 25.8 M25.8 8.2 L8.2 25.8" stroke="#000100" stroke-width="3.6"/>',
        'published'    => '<path d="M17 8.2 V2.2 M13.2 5.8 L17 2 L20.8 5.8" stroke="#000100" stroke-width="1.75"/>'
            . '<path d="M6 17.4 H28 V30.4 H6 Z" stroke="#000100" stroke-width="1.5"/>'
            . '<path d="M6 17.4 L11.2 12.9 H22.8 L28 17.4" stroke="#000100" stroke-width="1.5"/>'
            . '<path d="M6 17.4 L17 24.2 L28 17.4" stroke="#000100" stroke-width="1.35"/>',
    );
    $stored = (string) $stored;

    if (!isset($marks[$stored])) {
        return '';
    }

    return '<span class="nc-mr-admin__status-icon nc-mr-admin__status-icon--' . esc_attr($stored) . '" aria-hidden="true">'
        . '<svg viewBox="0 0 34 34" fill="none" stroke-linecap="round" stroke-linejoin="round">'
        . $marks[$stored]
        . '</svg></span>';
}

/**
 * Status filter. Same query as the previous select. Icons are editorial status only.
 *
 * @param array{status: string, share: string, ref: string} $filters  Validated filters.
 * @param array<string, string>                             $statuses Stored value => label.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_status_menu($filters, $statuses) {
    $current = $filters['status'];
    $base    = array(
        'share' => $filters['share'],
        'ref'   => $filters['ref'],
    );
    $current_label = ($current !== '' && isset($statuses[$current])) ? $statuses[$current] : 'All statuses';
    ?>
    <div class="field nc-mr-admin__status-field">
        <span class="nc-mr-admin__field-label" id="nc-mr-status-label">Status</span>
        <input type="hidden" name="status" value="<?php echo esc_attr($current); ?>">
        <details class="nc-mr-admin__status-menu">
            <summary id="nc-mr-status" aria-labelledby="nc-mr-status-label">
                <?php echo noelclark_v1_mail_room_admin_status_icon($current); ?>
                <span class="nc-mr-admin__status-current"><?php echo esc_html($current_label); ?></span>
                <span class="nc-mr-admin__status-chevron"><?php echo noelclark_v1_mail_room_admin_icon('chevron'); ?></span>
            </summary>
            <div class="nc-mr-admin__status-panel">
                <a class="nc-mr-admin__status-option nc-mr-admin__status-option--all<?php echo $current === '' ? ' is-on' : ''; ?>" data-nc-mr-status="" href="<?php echo esc_url(noelclark_v1_mail_room_admin_url($base)); ?>"<?php echo $current === '' ? ' aria-current="page"' : ''; ?>>All statuses</a>
                <?php foreach ($statuses as $stored => $label) : ?>
                    <a class="nc-mr-admin__status-option<?php echo $current === $stored ? ' is-on' : ''; ?>" data-nc-mr-status="<?php echo esc_attr($stored); ?>" href="<?php echo esc_url(noelclark_v1_mail_room_admin_url(array_merge($base, array('status' => $stored)))); ?>"<?php echo $current === $stored ? ' aria-current="page"' : ''; ?>>
                        <?php echo noelclark_v1_mail_room_admin_status_icon($stored); ?>
                        <span><?php echo esc_html($label); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
    <?php
}

/**
 * Compact sharing guide. Native disclosure, same behavior as the other folds.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_sharing_guide() {
    $items = array(
        array(
            'yes',
            'Full Permission',
            'We can publish the letter in full or in part, using the writer\'s exact authorized public credit name.',
        ),
        array(
            'anon',
            'Anonymous Only',
            'We can publish the letter in full or in part anonymously.',
        ),
        array(
            'no',
            'Private',
            'We cannot publish the letter.',
        ),
    );
    ?>
    <details class="nc-mr-admin__guide">
        <summary>
            <span class="nc-mr-admin__guide-mark"><?php echo noelclark_v1_mail_room_admin_icon('guide'); ?></span>
            <span>Sharing Guide</span>
            <span class="nc-mr-admin__guide-chevron"><?php echo noelclark_v1_mail_room_admin_icon('chevron'); ?></span>
        </summary>
        <div class="nc-mr-admin__guide-panel">
            <?php foreach ($items as $item) : ?>
                <details class="nc-mr-admin__guide-item nc-mr-admin__guide-item--<?php echo esc_attr($item[0]); ?>">
                    <summary>
                        <span class="nc-mr-admin__guide-swatch" aria-hidden="true"></span>
                        <span class="nc-mr-admin__guide-name"><?php echo esc_html($item[1]); ?></span>
                        <span class="nc-mr-admin__guide-chevron"><?php echo noelclark_v1_mail_room_admin_icon('chevron'); ?></span>
                    </summary>
                    <p><?php echo esc_html($item[2]); ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </details>
    <?php
}

/**
 * Inbox.
 *
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_inbox($parsed) {
    $filters   = $parsed['filters'];
    $counts    = noelclark_v1_mail_room_admin_status_counts();
    $recipient = noelclark_v1_mail_room_notify_recipient();
    $per_page  = (int) NOELCLARK_V1_MAIL_ROOM_ADMIN_PER_PAGE;
    $letters   = (!empty($counts['ok'])) ? (int) $counts['all'] : 0;
    $waiting   = (!empty($counts['ok']) && isset($counts['counts'][NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW]))
        ? (int) $counts['counts'][NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW]
        : 0;
    $statuses  = noelclark_v1_mail_room_admin_editorial_statuses();
    ?>
    <div class="nc-mr-admin__canvas">
        <header class="nc-mr-admin__mast">
            <div class="nc-mr-admin__mast-title">
                <p class="eyebrow">noelclark.com · Private inbox</p>
                <h1 class="disp nc-mr-admin__mast-name">Mail Room</h1>
            </div>
            <?php noelclark_v1_mail_room_admin_render_sharing_guide(); ?>
            <p class="muted nc-mr-admin__alert-line">
                <span class="nc-mr-admin__icon"><?php echo noelclark_v1_mail_room_admin_icon('mail'); ?></span>
                <?php if ($recipient !== '') : ?>
                    New-letter alerts go to <b><?php echo esc_html($recipient); ?></b>
                <?php else : ?>
                    No valid alert address is configured, so new-letter alerts cannot be sent.
                <?php endif; ?>
            </p>
        </header>

        <?php foreach ($parsed['notices'] as $notice) : ?>
            <?php noelclark_v1_mail_room_admin_notice('warning', $notice); ?>
        <?php endforeach; ?>

        <section class="tiles" aria-label="Summary">
            <div class="card nc-mr-admin__tile">
                <p class="eyebrow">Letters</p>
                <span class="disp nc-mr-admin__tile-num"><?php echo esc_html((string) $letters); ?></span>
            </div>
            <div class="card nc-mr-admin__tile nc-mr-admin__tile--wait">
                <p class="eyebrow">Waiting on us</p>
                <span class="disp nc-mr-admin__tile-num"><?php echo esc_html((string) $waiting); ?></span>
            </div>
        </section>

        <section class="filters" aria-label="Filters">
            <div class="seg" role="group" aria-label="View">
                <?php
                $seg_args = array(
                    'share' => $filters['share'],
                    'ref'   => $filters['ref'],
                );
                $all_url = noelclark_v1_mail_room_admin_url($seg_args);
                $new_url = noelclark_v1_mail_room_admin_url(array_merge($seg_args, array('status' => NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW)));
                ?>
                <a class="seg-btn<?php echo $filters['status'] === '' ? ' is-on' : ''; ?>" href="<?php echo esc_url($all_url); ?>"<?php echo $filters['status'] === '' ? ' aria-current="page"' : ''; ?>>All<b><?php echo esc_html((string) $letters); ?></b></a>
                <a class="seg-btn<?php echo $filters['status'] === NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW ? ' is-on' : ''; ?>" href="<?php echo esc_url($new_url); ?>"<?php echo $filters['status'] === NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW ? ' aria-current="page"' : ''; ?>>Unreviewed<b><?php echo esc_html((string) $waiting); ?></b></a>
            </div>
            <form class="nc-mr-admin__filter-form" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="<?php echo esc_attr(NOELCLARK_V1_MAIL_ROOM_ADMIN_PAGE_SLUG); ?>">
                <div class="field">
                    <label for="nc-mr-ref">Reference ID</label>
                    <input id="nc-mr-ref" type="search" name="ref" value="<?php echo esc_attr($parsed['ref_invalid'] ? '' : $filters['ref']); ?>" maxlength="64" autocomplete="off" spellcheck="false" placeholder="Paste exact ID">
                </div>
                <?php noelclark_v1_mail_room_admin_render_status_menu($filters, $statuses); ?>
                <div class="field">
                    <label for="nc-mr-share">Can I share it?</label>
                    <select id="nc-mr-share" name="share" onchange="this.form.submit()">
                        <option value="">Any answer</option>
                        <?php foreach (noelclark_v1_mail_room_admin_share_filter_answers() as $stored => $label) : ?>
                            <option value="<?php echo esc_attr($stored); ?>"<?php selected($filters['share'], $stored); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a class="nc-mr-admin__clear" href="<?php echo esc_url(noelclark_v1_mail_room_admin_url()); ?>">Clear</a>
            </form>
        </section>

        <?php
        if ($parsed['ref_invalid']) {
            noelclark_v1_mail_room_admin_notice('warning', 'Reference IDs are 32 characters (0–9, a–f).');
            noelclark_v1_mail_room_admin_render_meanings();
            echo '</div>';

            return;
        }

        $result = noelclark_v1_mail_room_admin_query_inbox($filters, $parsed['page'], $filters['share'] !== '');

        if (empty($result['ok'])) {
            noelclark_v1_mail_room_admin_notice('error', 'The Mail Room inbox could not be loaded.');
            noelclark_v1_mail_room_admin_render_meanings();
            echo '</div>';

            return;
        }

        $attached = noelclark_v1_mail_room_admin_attach_pending_permission_requests($result['rows']);

        if (empty($attached['ok'])) {
            noelclark_v1_mail_room_admin_notice('error', 'The Mail Room inbox could not be loaded.');
            noelclark_v1_mail_room_admin_render_meanings();
            echo '</div>';

            return;
        }

        $paired = array();

        foreach ($attached['rows'] as $row) {
            $view = noelclark_v1_mail_room_admin_letter_view($row);

            if ($filters['share'] !== '' && $view['verdict'] !== $filters['share']) {
                continue;
            }

            $paired[] = array(
                'row'  => $row,
                'view' => $view,
            );
        }

        if ($filters['share'] !== '') {
            $total       = count($paired);
            $total_pages = max(1, (int) ceil($total / $per_page));
            $page        = min(max(1, (int) $parsed['page']), $total === 0 ? 1 : $total_pages);
            $paired      = array_slice($paired, ($page - 1) * $per_page, $per_page);
        } else {
            $total       = (int) $result['total'];
            $page        = (int) $result['page'];
            $total_pages = (int) $result['total_pages'];
        }

        $has_filters = ($filters['status'] !== '' || $filters['share'] !== '' || $filters['ref'] !== '');

        if ($total === 0) {
            echo '<div class="card nc-mr-admin__empty">';

            if ($filters['ref'] !== '' && ($filters['status'] !== '' || $filters['share'] !== '')) {
                echo '<p>' . esc_html('No letter with that reference ID matches these filters.') . '</p>';
            } elseif ($filters['ref'] !== '') {
                echo '<p>' . esc_html('No letter found with that reference ID.') . '</p>';
            } elseif ($has_filters) {
                echo '<p>' . esc_html('No letters match these filters.') . '</p>';
            } else {
                echo '<p>' . esc_html('No letters have arrived yet.') . '</p>';
            }

            if ($has_filters) {
                printf('<p><a href="%1$s">%2$s</a></p>', esc_url(noelclark_v1_mail_room_admin_url()), esc_html('Clear filters'));
            }

            echo '</div>';
            noelclark_v1_mail_room_admin_render_meanings();
            echo '</div>';

            return;
        }

        $context = noelclark_v1_mail_room_admin_context_args($filters, $page);
        ?>
        <section class="nc-mr-admin__letters" aria-label="Letters">
            <?php foreach ($paired as $item) : ?>
                <?php noelclark_v1_mail_room_admin_render_inbox_item($item['row'], $context, $item['view']); ?>
            <?php endforeach; ?>
            <?php noelclark_v1_mail_room_admin_render_pagination($context, $page, $total_pages, $total); ?>
        </section>
        <?php noelclark_v1_mail_room_admin_render_meanings(); ?>
    </div>
    <?php
}

/**
 * Pending-withdrawal and failed-alert flags, with the short explanation beside each pill.
 *
 * @param array<string, mixed> $row Submission or inbox row.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_flags($row) {
    if (noelclark_v1_mail_room_admin_is_withdrawal_pending($row)) {
        echo '<span class="flag-pair"><span class="pill flag">Withdrawal pending</span><span class="flag-note">New public sharing is paused. This request is not a decision.</span></span>';
    }

    if (noelclark_v1_mail_room_admin_alert_failed($row)) {
        $recipient = noelclark_v1_mail_room_notify_recipient();
        $note      = $recipient !== ''
            ? 'The new-letter notification to ' . $recipient . ' failed. The visitor\'s letter was saved successfully.'
            : 'The new-letter notification failed. No valid alert address is configured. The visitor\'s letter was saved successfully.';

        echo '<span class="flag-pair"><span class="pill flag flag-alert">Alert failed</span><span class="flag-note">' . esc_html($note) . '</span></span>';
    }
}

/**
 * Collapsed status and flag definitions below the letter list.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_meanings() {
    $recipient = noelclark_v1_mail_room_notify_recipient();
    ?>
    <div class="card nc-mr-admin__meanings">
        <details class="fold">
            <summary>Letter statuses &amp; alerts explained</summary>
            <h3 class="fold-label">Status</h3>
            <ul class="fold-list">
                <?php foreach (noelclark_v1_mail_room_admin_editorial_statuses() as $stored => $label) : ?>
                    <li><b><?php echo esc_html($label); ?>.</b> <?php echo esc_html(noelclark_v1_mail_room_admin_status_meaning($stored)); ?></li>
                <?php endforeach; ?>
            </ul>
            <h3 class="fold-label">Flags</h3>
            <ul class="fold-list">
                <li><b>Withdrawal pending.</b> A withdrawal request is waiting for a decision. New public sharing is paused. The original and operative permissions are unchanged, and the request is not approved.</li>
                <li><b>Alert failed.</b> <?php echo esc_html(noelclark_v1_mail_room_admin_alert_failed_summary($recipient)); ?></li>
            </ul>
        </details>
    </div>
    <?php
}

/**
 * Inbox explanation of a failed alert. Names the configured recipient.
 *
 * @param string $recipient Configured alert address, or empty.
 * @return string
 */
function noelclark_v1_mail_room_admin_alert_failed_summary($recipient) {
    if ($recipient === '') {
        return 'The new-letter notification failed. No valid alert address is configured. The visitor\'s letter was saved successfully.';
    }

    return 'The new-letter notification to ' . $recipient . ' failed. The visitor\'s letter was saved successfully.';
}

/**
 * Authorized name for one inbox row.
 *
 * Named letters use the share answer's credit. Anonymous and private,
 * paused, or unauthorized letters do not reveal a stored or contact name.
 *
 * @param array<string, mixed> $view Letter view.
 * @return string
 */
function noelclark_v1_mail_room_admin_inbox_author_label($view) {
    $mode   = isset($view['mode']) ? (string) $view['mode'] : '';
    $credit = isset($view['credit']) ? trim((string) $view['credit']) : '';

    if ($mode === 'named' && $credit !== '') {
        return $credit;
    }

    if ($mode === 'anonymous') {
        return 'Anonymous';
    }

    return 'Private';
}

/**
 * Publication line for one inbox card, from the current share answer.
 *
 * @param array<string, mixed> $view Letter view.
 * @return array{tone: string, eye: string, label: string}
 */
function noelclark_v1_mail_room_admin_inbox_permission($view) {
    $mode   = isset($view['mode']) ? (string) $view['mode'] : '';
    $credit = isset($view['credit']) ? trim((string) $view['credit']) : '';

    if ($mode === 'named' && $credit !== '') {
        return array(
            'tone'  => 'credit',
            'eye'   => 'open',
            'label' => 'Can be published with credit to ' . $credit,
        );
    }

    if ($mode === 'anonymous') {
        return array(
            'tone'  => 'anonymous',
            'eye'   => 'open',
            'label' => 'Can be published anonymously',
        );
    }

    if ($mode === 'yes') {
        return array(
            'tone'  => 'credit',
            'eye'   => 'open',
            'label' => 'Can be published',
        );
    }

    return array(
        'tone'  => 'private',
        'eye'   => 'closed',
        'label' => 'Cannot be published',
    );
}

/**
 * Inbox card icon from the approved letter-list reference.
 *
 * @param string $name eye-open, eye-closed, envelope-new, envelope-read, star, or copy.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_inbox_mark($name) {
    $marks = array(
        'eye-open'      => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Public"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        'eye-closed'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Private"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><path d="M1 1l22 22"></path></svg>',
        'envelope-new'  => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 7l10 7 10-7"></path></svg>',
        'envelope-read' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10l9-6 9 6v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"></path><path d="M3 10l9 6 9-6"></path></svg>',
        'star'          => '<svg width="15" height="15" viewBox="0 0 24 24" fill="#FF2D78" stroke="#FF2D78" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4l-5.9 3.1 1.2-6.5-4.8-4.6 6.6-.9z"></path></svg>',
        'copy'          => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2"></rect><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"></path></svg>',
    );

    return isset($marks[$name]) ? $marks[$name] : '';
}

/**
 * One inbox row.
 *
 * @param array<string, mixed>      $row     Inbox row.
 * @param array<string, string|int> $context Filter context.
 * @param array<string, mixed>      $view    Letter view.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_inbox_item($row, $context, $view) {
    $mr_ref     = (string) $row['mr_ref'];
    $url        = noelclark_v1_mail_room_admin_url(array_merge($context, array('mr' => $mr_ref)));
    $preview    = noelclark_v1_mail_room_admin_excerpt(isset($row['excerpt']) ? $row['excerpt'] : '');
    $parts      = noelclark_v1_mail_room_admin_eastern_parts($row['accepted_at']);
    $author     = noelclark_v1_mail_room_admin_inbox_author_label($view);
    $permission = noelclark_v1_mail_room_admin_inbox_permission($view);
    $can_copy   = (isset($view['mode']) && (string) $view['mode'] === 'named' && trim((string) $view['credit']) !== '');
    $stored     = (isset($row['editorial_status']) && $row['editorial_status'] !== null && $row['editorial_status'] !== '')
        ? (string) $row['editorial_status']
        : '';
    $known      = noelclark_v1_mail_room_admin_editorial_statuses();
    $unreviewed = ($stored === NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW);
    $selected   = ($stored === 'selected');
    $when       = '';

    if ($parts !== null) {
        $clock = (string) preg_replace('/\s+[A-Z]{2,5}$/', '', (string) $parts['time']);
        $when  = $parts['date'] . ' · ' . $clock;
    }
    ?>
    <article class="nc-mr-inbox-card<?php echo $unreviewed ? ' is-new' : ' is-read'; ?>">
        <div class="nc-mr-inbox-card__main">
            <div class="nc-mr-inbox-card__identity">
                <a class="nc-mr-inbox-card__open" href="<?php echo esc_url($url); ?>"><?php echo esc_html($author); ?></a>
                <?php if ($can_copy) : ?>
                    <button type="button" class="nc-mr-inbox-card__copy" data-nc-mr-copy="<?php echo esc_attr($author); ?>" data-nc-mr-copy-label="Copy" aria-label="<?php echo esc_attr('Copy name: ' . $author); ?>">
                        <?php echo noelclark_v1_mail_room_admin_inbox_mark('copy'); ?>
                        <span class="screen-reader-text" aria-live="polite">Copy</span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="nc-mr-inbox-card__when">
                <?php if ($when !== '') : ?>
                    <?php echo esc_html($when); ?>
                <?php else : ?>
                    <?php echo noelclark_v1_mail_room_admin_time_html($row['accepted_at']); ?>
                <?php endif; ?>
            </div>
            <p class="nc-mr-inbox-card__preview"><?php echo $preview !== '' ? esc_html($preview) : noelclark_v1_mail_room_admin_muted_html('Empty letter'); ?></p>
        </div>
        <div class="nc-mr-inbox-card__side">
            <span class="nc-mr-inbox-card__permit nc-mr-inbox-card__permit--<?php echo esc_attr($permission['tone']); ?>">
                <?php echo noelclark_v1_mail_room_admin_inbox_mark($permission['eye'] === 'open' ? 'eye-open' : 'eye-closed'); ?>
                <span><?php echo esc_html($permission['label']); ?></span>
            </span>
            <div class="nc-mr-inbox-card__meta">
                <?php if ($stored !== '' && isset($known[$stored])) : ?>
                    <span class="nc-mr-inbox-card__review">
                        <?php echo noelclark_v1_mail_room_admin_inbox_mark($unreviewed ? 'envelope-new' : 'envelope-read'); ?>
                        <?php echo esc_html($unreviewed ? 'Unreviewed' : 'Reviewed'); ?>
                    </span>
                    <?php if ($selected) : ?>
                        <span class="nc-mr-inbox-card__selected">
                            <?php echo noelclark_v1_mail_room_admin_inbox_mark('star'); ?>
                            <?php echo esc_html('Selected'); ?>
                        </span>
                    <?php endif; ?>
                <?php else : ?>
                    <?php echo noelclark_v1_mail_room_admin_status_pill($row['editorial_status']); ?>
                <?php endif; ?>
            </div>
            <?php noelclark_v1_mail_room_admin_render_flags($row); ?>
        </div>
    </article>
    <?php
}

/**
 * Newer / older pagination, with the visible range.
 *
 * @param array<string, string|int> $context     Filter context.
 * @param int                       $page        Current page.
 * @param int                       $total_pages Total pages.
 * @param int                       $total       Matching letters.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_pagination($context, $page, $total_pages, $total) {
    $per_page = (int) NOELCLARK_V1_MAIL_ROOM_ADMIN_PER_PAGE;
    $from     = $total === 0 ? 0 : (($page - 1) * $per_page) + 1;
    $to       = min($total, $page * $per_page);
    ?>
    <div class="list-foot">
        <span class="muted"><?php echo esc_html(sprintf('Showing %1$d–%2$d of %3$d', $from, $to, $total)); ?></span>
        <?php if ($total_pages > 1) : ?>
            <span class="list-pages">
                <?php if ($page > 1) : ?>
                    <a class="btn ghost" href="<?php echo esc_url(noelclark_v1_mail_room_admin_url(array_merge($context, array('paged' => $page - 1)))); ?>">Newer</a>
                <?php endif; ?>
                <?php if ($page < $total_pages) : ?>
                    <a class="btn ghost" href="<?php echo esc_url(noelclark_v1_mail_room_admin_url(array_merge($context, array('paged' => $page + 1)))); ?>">Older</a>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Result of a redirect after an editorial-status POST.
 *
 * @param string               $mr_ref Normalized reference.
 * @param array<string, string> $notice saved and optional reason.
 * @return void
 */
function noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, $notice) {
    $args = array('mr' => $mr_ref);
    $statuses = noelclark_v1_mail_room_admin_editorial_statuses();
    $answers  = noelclark_v1_mail_room_admin_share_filter_answers();
    $status   = sanitize_key(noelclark_v1_mail_room_admin_post_string('return_status'));
    $share    = sanitize_key(noelclark_v1_mail_room_admin_post_string('return_share'));
    $ref      = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('return_ref'));
    $paged    = absint(noelclark_v1_mail_room_admin_post_string('return_paged'));

    if ($status !== '' && isset($statuses[$status])) {
        $args['status'] = $status;
    }

    if ($share !== '' && isset($answers[$share])) {
        $args['share'] = $share;
    }

    if ($ref !== null) {
        $args['ref'] = $ref;
    }

    if ($paged > 1) {
        $args['paged'] = $paged;
    }

    foreach ($notice as $key => $value) {
        $args[$key] = $value;
    }

    wp_safe_redirect(noelclark_v1_mail_room_admin_url($args));
    exit;
}

/**
 * Current status and history row, or a rollback when either write fails.
 *
 * An unchanged status is not written here. The caller returns before this.
 *
 * @param string $mr_ref Normalized reference.
 * @param string $status Allowlisted stored status.
 * @return array{ok: true, unchanged?: bool}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_commit_editorial_status_change($mr_ref, $status) {
    global $wpdb;

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_admin_log('status_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');
    $events      = noelclark_v1_mail_room_table('mr_events');
    $locked      = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT s.id AS mr_id, st.editorial_status FROM {$state} st "
            . "INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . 'WHERE s.mr_ref = %s LIMIT 1 FOR UPDATE',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($locked === null && $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('status_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if (!is_array($locked) || !isset($locked['mr_id'], $locked['editorial_status']) || (int) $locked['mr_id'] < 1) {
        $letter_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$submissions} WHERE mr_ref = %s LIMIT 1",
                $mr_ref
            )
        );
        $read_error = ($letter_exists === null) ? $wpdb->last_error : '';
        $wpdb->query('ROLLBACK');

        if ($letter_exists === null && $read_error !== '') {
            noelclark_v1_mail_room_admin_log('status_read_error');

            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }

        return array(
            'ok'   => false,
            'code' => ($letter_exists === null) ? 'not_found' : 'missing',
        );
    }

    $previous = (string) $locked['editorial_status'];

    if ($previous === $status) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'        => true,
            'unchanged' => true,
        );
    }

    $now     = current_time('mysql', true);
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$state} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . 'SET st.editorial_status = %s, st.updated_at = %s WHERE s.mr_ref = %s',
            $status,
            $now,
            $mr_ref
        )
    );

    if ($updated === false || $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('status_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if ((int) $updated < 1) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'not_found',
        );
    }

    $payload = wp_json_encode(
        array(
            'from' => $previous,
            'to'   => $status,
        )
    );

    if (!is_string($payload) || $payload === '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('status_history_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    $event_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $events,
        array(
            'mr_id'        => (int) $locked['mr_id'],
            'event_type'   => NOELCLARK_V1_MAIL_ROOM_EVENT_EDITORIAL_STATUS_CHANGED,
            'payload_json' => $payload,
            'created_at'   => $now,
        ),
        'event_ref',
        array('%d', '%s', '%s', '%s')
    );

    if ($event_id === false || (int) $event_id < 1) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('status_history_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('status_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    return array(
        'ok' => true,
    );
}

/**
 * Update one letter's editorial status. Sharing and publication are untouched.
 *
 * A genuine change also appends one editorial_status_changed event. An
 * unchanged status does not write and does not change updated_at.
 *
 * @param string $mr_ref Normalized reference.
 * @param string $status Allowlisted stored status.
 * @return array{ok: true, unchanged?: bool}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_update_editorial_status($mr_ref, $status) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $writable = noelclark_v1_mail_room_admin_writable_statuses();

    if (!isset($writable[$status])) {
        return array(
            'ok'   => false,
            'code' => 'invalid',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');
    $current     = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT st.editorial_status FROM {$state} st "
            . "INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . 'WHERE s.mr_ref = %s LIMIT 1',
            $mr_ref
        )
    );

    if ($current === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('status_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if ($current === null) {
        $letter_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$submissions} WHERE mr_ref = %s LIMIT 1",
                $mr_ref
            )
        );

        if ($letter_exists === null && $wpdb->last_error !== '') {
            noelclark_v1_mail_room_admin_log('status_read_error');

            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }

        return array(
            'ok'   => false,
            'code' => ($letter_exists === null) ? 'not_found' : 'missing',
        );
    }

    if ((string) $current === $status) {
        return array(
            'ok'        => true,
            'unchanged' => true,
        );
    }

    return noelclark_v1_mail_room_admin_commit_editorial_status_change($mr_ref, $status);
}

/**
 * Authorized editorial-status POST. Does not run when a letter is opened.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_editorial_status() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_editorial_status_' . $mr_ref);

    $status   = sanitize_key(noelclark_v1_mail_room_admin_post_string('editorial_status'));
    $writable = noelclark_v1_mail_room_admin_writable_statuses();

    if (!isset($writable[$status])) {
        noelclark_v1_mail_room_admin_redirect_after_status(
            $mr_ref,
            array(
                'saved'  => 'error',
                'reason' => 'invalid',
            )
        );
    }

    $result = noelclark_v1_mail_room_admin_update_editorial_status($mr_ref, $status);

    if (empty($result['ok'])) {
        $reason  = isset($result['code']) ? (string) $result['code'] : 'storage';
        $allowed = array('missing', 'not_found', 'storage', 'invalid');

        if (!in_array($reason, $allowed, true)) {
            $reason = 'storage';
        }

        if ($reason === 'not_found') {
            noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, array());
        }

        noelclark_v1_mail_room_admin_redirect_after_status(
            $mr_ref,
            array(
                'saved'  => 'error',
                'reason' => $reason,
            )
        );
    }

    noelclark_v1_mail_room_admin_redirect_after_status(
        $mr_ref,
        array(
            'saved' => !empty($result['unchanged']) ? 'same' : '1',
        )
    );
}

/**
 * Logged-out status posts are refused.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_reject_editorial_status() {
    wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
}
add_action('admin_post_noelclark_v1_mail_room_set_editorial_status', 'noelclark_v1_mail_room_admin_handle_editorial_status');
add_action('admin_post_nopriv_noelclark_v1_mail_room_set_editorial_status', 'noelclark_v1_mail_room_admin_reject_editorial_status');

/**
 * Plain-text private note, or null when it exceeds the storage limit.
 *
 * @param string $raw Unslashed textarea value.
 * @return string|null Empty string clears the note.
 */
function noelclark_v1_mail_room_admin_sanitize_private_note($raw) {
    $note = sanitize_textarea_field($raw);
    $length = function_exists('mb_strlen') ? mb_strlen($note) : strlen($note);

    if ($length > NOELCLARK_V1_MAIL_ROOM_PRIVATE_NOTE_MAX) {
        return null;
    }

    return $note;
}

/**
 * Replace one letter's private note. No other operational column is written.
 *
 * @param string $mr_ref Normalized reference.
 * @param string $note   Sanitized note. Empty clears it.
 * @return array{ok: true, cleared?: bool, unchanged?: bool}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_update_private_note($mr_ref, $note) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');
    $current     = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT st.private_note FROM {$state} st "
            . "INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . 'WHERE s.mr_ref = %s LIMIT 1',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($current === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('note_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if (!is_array($current)) {
        $letter_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$submissions} WHERE mr_ref = %s LIMIT 1",
                $mr_ref
            )
        );

        if ($letter_exists === null && $wpdb->last_error !== '') {
            noelclark_v1_mail_room_admin_log('note_read_error');

            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }

        return array(
            'ok'   => false,
            'code' => ($letter_exists === null) ? 'not_found' : 'missing',
        );
    }

    $stored = isset($current['private_note']) && $current['private_note'] !== null
        ? (string) $current['private_note']
        : '';

    if ($stored === $note) {
        return array(
            'ok'        => true,
            'unchanged' => true,
            'cleared'   => ($note === ''),
        );
    }

    if ($note === '') {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$state} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
                . 'SET st.private_note = NULL WHERE s.mr_ref = %s',
                $mr_ref
            )
        );
    } else {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$state} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
                . 'SET st.private_note = %s WHERE s.mr_ref = %s',
                $note,
                $mr_ref
            )
        );
    }

    if ($updated === false || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('note_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if ((int) $updated < 1) {
        return array(
            'ok'   => false,
            'code' => 'not_found',
        );
    }

    return array(
        'ok'      => true,
        'cleared' => ($note === ''),
    );
}

/**
 * Authorized private-note POST. Does not run when a letter is opened.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_private_note() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_private_note_' . $mr_ref);

    if (!isset($_POST['private_note']) || !is_string($_POST['private_note'])) {
        noelclark_v1_mail_room_admin_redirect_after_status(
            $mr_ref,
            array(
                'note_saved'  => 'error',
                'note_reason' => 'invalid',
            )
        );
    }

    $note = noelclark_v1_mail_room_admin_sanitize_private_note(wp_unslash($_POST['private_note']));

    if ($note === null) {
        noelclark_v1_mail_room_admin_redirect_after_status(
            $mr_ref,
            array(
                'note_saved'  => 'error',
                'note_reason' => 'length',
            )
        );
    }

    $result = noelclark_v1_mail_room_admin_update_private_note($mr_ref, $note);

    if (empty($result['ok'])) {
        $reason  = isset($result['code']) ? (string) $result['code'] : 'storage';
        $allowed = array('missing', 'not_found', 'storage');

        if (!in_array($reason, $allowed, true)) {
            $reason = 'storage';
        }

        if ($reason === 'not_found') {
            noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, array());
        }

        noelclark_v1_mail_room_admin_redirect_after_status(
            $mr_ref,
            array(
                'note_saved'  => 'error',
                'note_reason' => $reason,
            )
        );
    }

    if (!empty($result['unchanged'])) {
        $saved = 'same';
    } elseif (!empty($result['cleared'])) {
        $saved = 'cleared';
    } else {
        $saved = '1';
    }

    noelclark_v1_mail_room_admin_redirect_after_status(
        $mr_ref,
        array(
            'note_saved' => $saved,
        )
    );
}

/**
 * Logged-out note posts are refused.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_reject_private_note() {
    wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
}
add_action('admin_post_noelclark_v1_mail_room_save_private_note', 'noelclark_v1_mail_room_admin_handle_private_note');
add_action('admin_post_nopriv_noelclark_v1_mail_room_save_private_note', 'noelclark_v1_mail_room_admin_reject_private_note');

/**
 * Explicit decision outcomes. Handled is not one of them.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_decision_outcomes() {
    return array(
        'declined' => 'Declined',
        'granted'  => 'Granted',
    );
}

/**
 * Operative choices that do not exceed the original submission.
 *
 * @param string $original_choice Stored original sharing choice.
 * @param string $original_credit Stored original public credit.
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_operative_choices($original_choice, $original_credit) {
    $original_level = noelclark_v1_mail_room_admin_share_choice_level($original_choice, $original_credit);
    $original_rank  = noelclark_v1_mail_room_admin_share_level_rank($original_level);
    $treatments     = noelclark_v1_mail_room_admin_treatments();
    $allowed        = array();

    if ($original_rank < 0 || !isset($treatments['not_public'])) {
        return $allowed;
    }

    $allowed['not_public'] = $treatments['not_public']['label'];

    if ($original_rank >= 1 && isset($treatments['anonymous'])) {
        $allowed['anonymous'] = $treatments['anonymous']['label'];
    }

    if ((string) $original_choice === 'named_credit' && trim((string) $original_credit) !== '' && isset($treatments['named_credit'])) {
        $allowed['named_credit'] = $treatments['named_credit']['label'];
    }

    return $allowed;
}

/**
 * Lock one letter and read its request events.
 *
 * The caller commits or rolls back. A database error is already rolled back.
 *
 * @param string $mr_ref Normalized reference.
 * @return array{ok: true, row: array<string, mixed>, events: array<int, array<string, mixed>>, state: string, events_table: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_begin_request_write($mr_ref) {
    global $wpdb;

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_admin_log('request_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');
    $events      = noelclark_v1_mail_room_table('mr_events');
    $locked      = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT s.id AS mr_id, s.sharing_choice, s.public_credit, '
            . 'st.operative_treatment, st.withdrawal_request_status '
            . "FROM {$state} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . 'WHERE s.mr_ref = %s LIMIT 1 FOR UPDATE',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($locked === null && $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if (!is_array($locked) || !isset($locked['mr_id']) || (int) $locked['mr_id'] < 1) {
        $letter_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$submissions} WHERE mr_ref = %s LIMIT 1",
                $mr_ref
            )
        );
        $read_error = ($letter_exists === null) ? $wpdb->last_error : '';
        $wpdb->query('ROLLBACK');

        if ($letter_exists === null && $read_error !== '') {
            noelclark_v1_mail_room_admin_log('request_read_error');

            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }

        return array(
            'ok'   => false,
            'code' => ($letter_exists === null) ? 'not_found' : 'missing',
        );
    }

    $history = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT e.event_type, e.payload_json, e.created_at '
            . "FROM {$events} e INNER JOIN {$submissions} s ON s.id = e.mr_id "
            . 'WHERE s.mr_ref = %s AND e.event_type IN (%s, %s, %s, %s, %s, %s) '
            . 'ORDER BY e.created_at ASC, e.id ASC',
            $mr_ref,
            NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS
        ),
        ARRAY_A
    );

    if (!is_array($history) || $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    return array(
        'ok'           => true,
        'row'          => $locked,
        'events'       => $history,
        'state'        => $state,
        'events_table' => $events,
    );
}

/**
 * @param string               $table   Events table.
 * @param int                  $mr_id   Internal letter id. Not displayed.
 * @param string               $type    Event type.
 * @param array<string, string> $payload Scalar payload.
 * @param string               $now     GMT timestamp.
 * @return bool
 */
function noelclark_v1_mail_room_admin_insert_request_event($table, $mr_id, $type, $payload, $now) {
    $json = wp_json_encode($payload);

    if (!is_string($json) || $json === '') {
        return false;
    }

    $event_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $table,
        array(
            'mr_id'        => $mr_id,
            'event_type'   => $type,
            'payload_json' => $json,
            'created_at'   => $now,
        ),
        'event_ref',
        array('%d', '%s', '%s', '%s')
    );

    return $event_id !== false && (int) $event_id >= 1;
}

/**
 * @return bool
 */
function noelclark_v1_mail_room_admin_commit_request_write() {
    global $wpdb;

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');

        return false;
    }

    return true;
}

/**
 * @param string $mr_ref Normalized reference.
 * @param string $notice Notice key.
 * @param string $value  Notice value.
 * @param string $reason Optional error reason.
 * @return void
 */
function noelclark_v1_mail_room_admin_redirect_request($mr_ref, $notice, $value, $reason = '') {
    $args = array($notice => $value);

    if ($reason !== '') {
        $args[$notice . '_reason'] = $reason;
    }

    noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, $args);
}

/**
 * @param array{ok: true, row: array<string, mixed>, events: array<int, array<string, mixed>>, state: string, events_table: string}|array{ok: false, code: string} $begun Locked write.
 * @param string $mr_ref Normalized reference.
 * @param string $notice Notice key.
 * @return array{ok: true, row: array<string, mixed>, events: array<int, array<string, mixed>>, state: string, events_table: string}|null
 */
function noelclark_v1_mail_room_admin_request_write_or_redirect($begun, $mr_ref, $notice) {
    if (!empty($begun['ok'])) {
        return $begun;
    }

    $reason  = isset($begun['code']) ? (string) $begun['code'] : 'storage';
    $allowed = array('missing', 'not_found', 'storage');

    if (!in_array($reason, $allowed, true)) {
        $reason = 'storage';
    }

    if ($reason === 'not_found') {
        noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, array());
    }

    noelclark_v1_mail_room_admin_redirect_request($mr_ref, $notice, 'error', $reason);

    return null;
}

/**
 * Record a withdrawal request. Does not change operative permission.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_withdrawal_request() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_withdrawal_request_' . $mr_ref);

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_request_write($mr_ref),
        $mr_ref,
        'wreq'
    );

    if ($begun === null) {
        return;
    }

    $record = noelclark_v1_mail_room_admin_request_record($begun['events']);
    $open   = $record['withdrawal']['request'] !== null && $record['withdrawal']['decision'] === null;
    $status = isset($begun['row']['withdrawal_request_status']) ? (string) $begun['row']['withdrawal_request_status'] : '';

    if ($open || $status === NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'pending');
    }

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$begun['state']} st INNER JOIN " . noelclark_v1_mail_room_table('mr_submissions') . ' s ON s.id = st.mr_id '
            . 'SET st.withdrawal_request_status = %s '
            . 'WHERE s.mr_ref = %s AND st.withdrawal_request_status <> %s',
            NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING,
            $mr_ref,
            NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING
        )
    );

    if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'storage');
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED,
        array('requested' => 'withdrawal'),
        current_time('mysql', true)
    );

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', '1');
}

/**
 * Record an explicit withdrawal decision.
 *
 * A grant also sets operative treatment to not_public. A decline does not.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_withdrawal_decision() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_withdrawal_decision_' . $mr_ref);

    $outcome  = sanitize_key(noelclark_v1_mail_room_admin_post_string('outcome'));
    $outcomes = noelclark_v1_mail_room_admin_decision_outcomes();

    if (!isset($outcomes[$outcome])) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'invalid');
    }

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_request_write($mr_ref),
        $mr_ref,
        'wreq'
    );

    if ($begun === null) {
        return;
    }

    $record = noelclark_v1_mail_room_admin_request_record($begun['events']);
    $open   = $record['withdrawal']['request'] !== null && $record['withdrawal']['decision'] === null;
    $status = isset($begun['row']['withdrawal_request_status']) ? (string) $begun['row']['withdrawal_request_status'] : '';

    if (!$open || $status !== NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'no_request');
    }

    $current   = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';
    $granted   = ($outcome === 'granted');
    $now       = current_time('mysql', true);
    $submissions = noelclark_v1_mail_room_table('mr_submissions');

    if ($granted) {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$begun['state']} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
                . 'SET st.withdrawal_request_status = %s, st.operative_treatment = %s '
                . 'WHERE s.mr_ref = %s AND st.withdrawal_request_status = %s',
                NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_HANDLED,
                'not_public',
                $mr_ref,
                NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING
            )
        );
    } else {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$begun['state']} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
                . 'SET st.withdrawal_request_status = %s '
                . 'WHERE s.mr_ref = %s AND st.withdrawal_request_status = %s',
                NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_HANDLED,
                $mr_ref,
                NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING
            )
        );
    }

    if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'storage');
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED,
        array('outcome' => $outcome),
        $now
    );

    if ($saved && $granted && $current !== 'not_public') {
        $saved = noelclark_v1_mail_room_admin_insert_request_event(
            $begun['events_table'],
            (int) $begun['row']['mr_id'],
            NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED,
            array(
                'from' => $current,
                'to'   => 'not_public',
            ),
            $now
        );
    }

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'wreq', $granted ? 'granted' : 'declined');
}

/**
 * Record a permission-change request. Does not use withdrawal status.
 *
 * A request broader than the original consent, or identical to the current
 * permission, is rolled back and not recorded.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_permission_request() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_permission_request_' . $mr_ref);

    $requested  = sanitize_key(noelclark_v1_mail_room_admin_post_string('requested'));
    $treatments = noelclark_v1_mail_room_admin_treatments();

    if (!isset($treatments[$requested])) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'invalid');
    }

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_request_write($mr_ref),
        $mr_ref,
        'preq'
    );

    if ($begun === null) {
        return;
    }

    $record = noelclark_v1_mail_room_admin_request_record($begun['events']);

    if ($record['permission']['request'] !== null && $record['permission']['decision'] === null) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'open');
    }

    $original_credit = isset($begun['row']['public_credit']) && $begun['row']['public_credit'] !== null
        ? (string) $begun['row']['public_credit']
        : '';
    $verdict         = noelclark_v1_mail_room_permission_request_verdict(
        isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '',
        $original_credit,
        isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '',
        $requested
    );

    if ($verdict !== 'ok') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', $verdict === 'same' ? 'same' : 'broader');
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED,
        array('requested' => $requested),
        current_time('mysql', true)
    );

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', '1');
}

/**
 * Record an explicit permission-change decision.
 *
 * A valid grant sets operative treatment to the requested permission in the
 * same transaction as the decision and the history of that change. Original
 * consent is not written. A decline leaves operative treatment unchanged.
 * A grant broader than the original consent, or identical to the current
 * permission, is refused and the pending request is left unchanged.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_permission_decision() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_permission_decision_' . $mr_ref);

    $outcome  = sanitize_key(noelclark_v1_mail_room_admin_post_string('outcome'));
    $outcomes = noelclark_v1_mail_room_admin_decision_outcomes();

    if (!isset($outcomes[$outcome])) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'invalid');
    }

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_request_write($mr_ref),
        $mr_ref,
        'preq'
    );

    if ($begun === null) {
        return;
    }

    $record = noelclark_v1_mail_room_admin_request_record($begun['events']);

    if ($record['permission']['request'] === null || $record['permission']['decision'] !== null) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'no_request');
    }

    $pairs   = noelclark_v1_mail_room_admin_event_payload_pairs($record['permission']['request']['payload_json']);
    $asked   = is_array($pairs) && isset($pairs['requested']) ? (string) $pairs['requested'] : '';
    $credit  = isset($begun['row']['public_credit']) && $begun['row']['public_credit'] !== null
        ? (string) $begun['row']['public_credit']
        : '';
    $current = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';
    $effects = noelclark_v1_mail_room_permission_decision_effects(
        isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '',
        $credit,
        $current,
        $asked,
        $outcome
    );

    if (empty($effects['ok'])) {
        $wpdb->query('ROLLBACK');
        $reason  = isset($effects['reason']) ? (string) $effects['reason'] : 'grant_broader';
        $allowed = array('grant_broader', 'grant_same', 'invalid');

        if (!in_array($reason, $allowed, true)) {
            $reason = 'grant_broader';
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', $reason);
    }

    $now = current_time('mysql', true);

    if (!empty($effects['record_operative_change'])) {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$begun['state']} st INNER JOIN " . noelclark_v1_mail_room_table('mr_submissions') . ' s ON s.id = st.mr_id '
                . 'SET st.operative_treatment = %s WHERE s.mr_ref = %s',
                (string) $effects['to'],
                $mr_ref
            )
        );

        if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'storage');
        }
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED,
        array('outcome' => (string) $effects['outcome']),
        $now
    );

    if ($saved && !empty($effects['record_operative_change'])) {
        $saved = noelclark_v1_mail_room_admin_insert_request_event(
            $begun['events_table'],
            (int) $begun['row']['mr_id'],
            NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED,
            array(
                'from' => (string) $effects['from'],
                'to'   => (string) $effects['to'],
            ),
            $now
        );
    }

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);

    if ($outcome === 'declined') {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', 'declined');
    }

    $granted_notices = array(
        'not_public'   => 'granted_not_public',
        'anonymous'    => 'granted_anonymous',
        'named_credit' => 'granted_named_credit',
    );
    $granted_notice  = isset($granted_notices[(string) $effects['operative_treatment']])
        ? $granted_notices[(string) $effects['operative_treatment']]
        : 'granted_not_public';

    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'preq', $granted_notice);
}

/**
 * Change operative treatment without exceeding the original permission.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_operative_treatment() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_operative_treatment_' . $mr_ref);

    $choice = sanitize_key(noelclark_v1_mail_room_admin_post_string('operative_treatment'));
    $begun  = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_request_write($mr_ref),
        $mr_ref,
        'op'
    );

    if ($begun === null) {
        return;
    }

    $original_credit = isset($begun['row']['public_credit']) && $begun['row']['public_credit'] !== null
        ? (string) $begun['row']['public_credit']
        : '';
    $allowed         = noelclark_v1_mail_room_admin_operative_choices(
        isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '',
        $original_credit
    );

    if (!isset($allowed[$choice])) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', 'error', 'broader');
    }

    $current = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';

    if ($current === $choice) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', 'same');
    }

    $lock = noelclark_v1_mail_room_author_operative_save_block(
        $current,
        $choice,
        $original_credit,
        noelclark_v1_mail_room_admin_author_withdrawal_items(
            isset($begun['events']) && is_array($begun['events']) ? $begun['events'] : array(),
            $begun['row']
        )
    );

    if ($lock !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', 'error', $lock);
    }

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$begun['state']} st INNER JOIN " . noelclark_v1_mail_room_table('mr_submissions') . ' s ON s.id = st.mr_id '
            . 'SET st.operative_treatment = %s WHERE s.mr_ref = %s',
            $choice,
            $mr_ref
        )
    );

    if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', 'error', 'storage');
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED,
        array(
            'from' => $current,
            'to'   => $choice,
        ),
        current_time('mysql', true)
    );

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'op', '1');
}

/**
 * @return void
 */
function noelclark_v1_mail_room_admin_reject_request_write() {
    wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
}
add_action('admin_post_noelclark_v1_mail_room_record_withdrawal_request', 'noelclark_v1_mail_room_admin_handle_withdrawal_request');
add_action('admin_post_nopriv_noelclark_v1_mail_room_record_withdrawal_request', 'noelclark_v1_mail_room_admin_reject_request_write');
add_action('admin_post_noelclark_v1_mail_room_record_withdrawal_decision', 'noelclark_v1_mail_room_admin_handle_withdrawal_decision');
add_action('admin_post_nopriv_noelclark_v1_mail_room_record_withdrawal_decision', 'noelclark_v1_mail_room_admin_reject_request_write');
add_action('admin_post_noelclark_v1_mail_room_record_permission_request', 'noelclark_v1_mail_room_admin_handle_permission_request');
add_action('admin_post_nopriv_noelclark_v1_mail_room_record_permission_request', 'noelclark_v1_mail_room_admin_reject_request_write');
add_action('admin_post_noelclark_v1_mail_room_record_permission_decision', 'noelclark_v1_mail_room_admin_handle_permission_decision');
add_action('admin_post_nopriv_noelclark_v1_mail_room_record_permission_decision', 'noelclark_v1_mail_room_admin_reject_request_write');
add_action('admin_post_noelclark_v1_mail_room_set_operative_treatment', 'noelclark_v1_mail_room_admin_handle_operative_treatment');
add_action('admin_post_nopriv_noelclark_v1_mail_room_set_operative_treatment', 'noelclark_v1_mail_room_admin_reject_request_write');

/**
 * Confirmation or refusal after a status POST. Opening a letter has no notice.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_status_save_notice() {
    $saved = noelclark_v1_mail_room_admin_request_string('saved');

    if ($saved === '1') {
        noelclark_v1_mail_room_admin_notice('success', 'Editorial status saved.');

        return;
    }

    if ($saved === 'same') {
        noelclark_v1_mail_room_admin_notice('success', 'Editorial status is unchanged.');

        return;
    }

    if ($saved !== 'error') {
        return;
    }

    $reasons = array(
        'invalid' => 'That editorial status cannot be saved.',
        'missing' => 'This letter has no status record, so its editorial status was not changed.',
        'storage' => 'The editorial status could not be saved.',
    );
    $reason  = sanitize_key(noelclark_v1_mail_room_admin_request_string('reason'));
    $message = isset($reasons[$reason]) ? $reasons[$reason] : 'The editorial status could not be saved.';

    noelclark_v1_mail_room_admin_notice('error', $message);
}

/**
 * Confirmation or refusal after a private-note POST.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_note_save_notice() {
    $saved = noelclark_v1_mail_room_admin_request_string('note_saved');

    if ($saved === '1') {
        noelclark_v1_mail_room_admin_notice('success', 'Private note saved.');

        return;
    }

    if ($saved === 'cleared') {
        noelclark_v1_mail_room_admin_notice('success', 'Private note cleared.');

        return;
    }

    if ($saved === 'same') {
        noelclark_v1_mail_room_admin_notice('success', 'Private note is unchanged.');

        return;
    }

    if ($saved !== 'error') {
        return;
    }

    $reasons = array(
        'length'  => 'The private note is too long to save.',
        'missing' => 'This letter has no status record, so the private note was not saved.',
        'storage' => 'The private note could not be saved.',
        'invalid' => 'The private note could not be saved.',
    );
    $reason  = sanitize_key(noelclark_v1_mail_room_admin_request_string('note_reason'));
    $message = isset($reasons[$reason]) ? $reasons[$reason] : 'The private note could not be saved.';

    noelclark_v1_mail_room_admin_notice('error', $message);
}

/**
 * Confirmation or refusal after a request, decision, or operative-permission save.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_render_request_save_notice() {
    $messages = array(
        'wreq' => array(
            '1'        => array('success', 'Request recorded: withdraw their letter.'),
            'pending'  => array('success', 'A request to withdraw their letter is already pending.'),
            'granted'  => array('success', 'Granted. The current sharing permission is not for public sharing.'),
            'declined' => array('success', 'Declined. The current sharing permission is unchanged.'),
        ),
        'preq' => array(
            '1'                   => array('success', 'Request recorded: change sharing permission. The current sharing permission is unchanged.'),
            'open'                => array('success', 'A request to change sharing permission is already pending.'),
            'declined'            => array('success', 'Declined. The current sharing permission is unchanged.'),
            'granted_not_public'  => array('success', 'Granted. How we share it now is Keep it private.'),
            'granted_anonymous'   => array('success', 'Granted. How we share it now is Anonymously.'),
            'granted_named_credit' => array('success', 'Granted. How we share it now is With their name.'),
        ),
        'op'   => array(
            '1'    => array('success', 'Current sharing permission saved.'),
            'same' => array('success', 'Current sharing permission is unchanged.'),
        ),
        'areq' => array(
            'logged'    => array('success', 'We saved what they asked. The name we use now, and how we share it now, stay as they are.'),
            'done'      => array('success', 'We made the change. The name they sent, and how they allowed it to be shared, stay as they sent them.'),
            'not_doing' => array('success', 'We\'re not doing it. The name we use now, and how we share it now, stay as they are.'),
            'progress'  => array('success', 'We updated the request.'),
            'refused'   => array('success', 'We saved what they asked. We didn\'t make the change.'),
        ),
        'credit' => array(
            '1'    => array('success', 'We saved the public credit. The original credit is unchanged.'),
            'same' => array('success', 'The current public credit is unchanged.'),
        ),
        'cconf' => array(
            '1' => array('success', 'We recorded that confirmation. The current credit and the original consent are unchanged.'),
        ),
    );
    $reasons = array(
        'wreq' => array(
            'invalid'    => 'That decision cannot be saved.',
            'no_request' => 'There is no pending request to withdraw their letter.',
            'missing'    => 'This letter has no status record, so the request was not saved.',
            'storage'    => 'The request could not be saved.',
        ),
        'preq' => array(
            'invalid'       => 'That request cannot be saved.',
            'broader'       => 'That request is broader than the original consent, so it was not saved.',
            'same'          => 'That request matches the current sharing permission, so it was not saved.',
            'grant_broader' => 'That request is broader than the original consent, so it cannot be granted.',
            'grant_same'    => 'That request matches the current sharing permission, so it cannot be granted.',
            'no_request'    => 'There is no pending request to change sharing permission.',
            'missing'       => 'This letter has no status record, so the request was not saved.',
            'storage'       => 'The request could not be saved.',
        ),
        'op'   => array(
            'broader' => 'That sharing permission is broader than the original, so it was not saved.',
            'withdrawal' => 'We didn\'t save that. A completed withdrawal still stands, so we\'re not widening sharing from here.',
            'pause' => 'We didn\'t save that. A withdrawal or a request to reduce sharing is still open.',
            'unreadable' => 'We didn\'t save that. We couldn\'t read the sharing history, so we\'re not widening sharing from here.',
            'missing' => 'This letter has no status record, so the current sharing permission was not saved.',
            'storage' => 'The current sharing permission could not be saved.',
        ),
        'areq' => array(
            'invalid'         => 'We couldn\'t log that request.',
            'length'          => 'That request is too long to save.',
            'asked'           => 'We need the date they contacted me.',
            'name'            => 'We need the name they want.',
            'choice'          => 'We need the choice they asked for.',
            'storage'         => 'The request could not be saved.',
            'missing'         => 'We couldn\'t find that request.',
            'date'            => 'We need the date we handled it.',
            'date_logged'     => 'We logged the request. We still need the date we handled it, so it stays still to do.',
            'clarify'         => 'We can\'t mark this done yet. We can\'t tell exactly what this asks for, so we\'re leaving it for clarification.',
            'authorize'       => 'We can\'t mark this done yet. We don\'t have a recorded confirmation for that exact public credit.',
            'consent'         => 'We can\'t mark this done yet. Their original consent doesn\'t allow this.',
            'sharing'         => 'We can\'t mark this done yet. The current sharing permission still doesn\'t match.',
            'credit'          => 'We can\'t mark this done yet. The credit in use still doesn\'t match.',
            'notes'           => 'We can\'t mark this done yet. We need a note about what we actually did.',
            'revision'        => 'We can\'t mark this done yet. We don\'t have a way to revise the letter yet. The original stays as they sent it.',
            'logged_clarify'  => 'We logged the request. We can\'t mark it done yet because we can\'t tell exactly what it asks for.',
            'logged_authorize' => 'We logged the request. We can\'t mark it done yet because we don\'t have a recorded confirmation for that exact public credit.',
            'logged_consent'  => 'We logged the request. We can\'t mark it done yet because their original consent doesn\'t allow this.',
            'logged_sharing'  => 'We logged the request. We can\'t mark it done yet because the current sharing permission still doesn\'t match.',
            'logged_credit'   => 'We logged the request. We can\'t mark it done yet because the credit in use still doesn\'t match.',
            'logged_notes'    => 'We logged the request. We can\'t mark it done yet because we need a note about what we actually did.',
            'logged_revision' => 'We logged the request. We can\'t mark it done yet because we don\'t have a way to revise the letter yet.',
            'logged_invalid'  => 'We logged the request. We can\'t mark it done yet.',
        ),
        'credit' => array(
            'length'    => 'That credit is too long to save.',
            'email'     => 'We didn\'t save that. We\'re not using their private email as a credit.',
            'contact'   => 'We didn\'t save that. We\'re not pulling a name from their private contact.',
            'authorize' => 'We didn\'t save that broader credit. We don\'t have a recorded confirmation for that exact public credit.',
            'consent'   => 'We didn\'t save that. Their original consent doesn\'t allow a public name.',
            'missing'   => 'We didn\'t save that credit. We don\'t have a logged request for it.',
            'storage'   => 'The current public credit could not be saved.',
            'missing_state' => 'This letter has no status record, so the credit was not saved.',
            'closed'        => 'We didn\'t save that. Write down what they asked first.',
        ),
        'cconf' => array(
            'invalid'  => 'We couldn\'t record that confirmation.',
            'length'   => 'That confirmation is too long to save.',
            'date'     => 'We don\'t have a usable date for when they asked, so the confirmation was not saved.',
            'words'    => 'We don\'t have their words on this request, so the confirmation was not saved.',
            'attest'   => 'We need the confirmation that we read their words and they asked for exactly this credit.',
            'mismatch' => 'That credit doesn\'t match what this request asks for, so the confirmation was not saved.',
            'request'  => 'We couldn\'t match that confirmation to one logged request.',
            'user'     => 'We couldn\'t record who confirmed it, so the confirmation was not saved.',
            'narrow'   => 'We don\'t need a confirmation for this credit. Narrowing and the original credit stay available without one.',
            'clarify'  => 'We can\'t tell which public credit this request asks for, so the confirmation was not saved.',
            'email'    => 'We didn\'t record that. We\'re not using their private email as a credit.',
            'consent'  => 'We didn\'t record that. Their original consent doesn\'t allow a public name.',
            'conflict' => 'We didn\'t record that. A conflicting request is already on this letter, so this confirmation would not be valid.',
            'missing'  => 'This letter has no status record, so the confirmation was not saved.',
            'storage'  => 'The confirmation could not be saved.',
        ),
    );

    foreach ($messages as $key => $values) {
        $saved = noelclark_v1_mail_room_admin_request_string($key);

        if (isset($values[$saved])) {
            noelclark_v1_mail_room_admin_notice($values[$saved][0], $values[$saved][1]);
        }

        if ($saved !== 'error') {
            continue;
        }

        $reason  = sanitize_key(noelclark_v1_mail_room_admin_request_string($key . '_reason'));
        $message = isset($reasons[$key][$reason]) ? $reasons[$key][$reason] : 'The change could not be saved.';
        noelclark_v1_mail_room_admin_notice('error', $message);
    }
}

/**
 * Editable editorial status. Published is not a choice.
 *
 * @param array<string, mixed> $row Submission row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_status_editor($row, $parsed) {
    if (!isset($row['editorial_status']) || $row['editorial_status'] === null || $row['editorial_status'] === '') {
        return;
    }

    $current  = (string) $row['editorial_status'];
    $known    = noelclark_v1_mail_room_admin_editorial_statuses();
    $writable = noelclark_v1_mail_room_admin_writable_statuses();
    $label    = isset($known[$current]) ? $known[$current] : 'Unrecognized';
    $mr_ref   = (string) $row['mr_ref'];
    ?>
    <form class="nc-mr-admin__status-editor" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('noelclark_v1_mail_room_editorial_status_' . $mr_ref); ?>
        <input type="hidden" name="action" value="noelclark_v1_mail_room_set_editorial_status">
        <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
        <?php foreach (noelclark_v1_mail_room_admin_context_args($parsed['filters'], $parsed['page']) as $key => $value) : ?>
            <?php if ($value === '' || $value === null || ($key === 'paged' && (int) $value <= 1)) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <input type="hidden" name="return_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string) $value); ?>">
        <?php endforeach; ?>
        <p class="eyebrow" id="nc-mr-editorial-label">Editorial status</p>
        <details class="nc-mr-admin__status-menu">
            <summary aria-labelledby="nc-mr-editorial-label">
                <?php echo noelclark_v1_mail_room_admin_status_icon($current); ?>
                <span class="nc-mr-admin__status-current"><?php echo esc_html($label); ?></span>
                <span class="nc-mr-admin__status-chevron"><?php echo noelclark_v1_mail_room_admin_icon('chevron'); ?></span>
            </summary>
            <div class="nc-mr-admin__status-panel">
                <?php foreach ($writable as $stored => $option_label) : ?>
                    <button type="submit" class="nc-mr-admin__status-option<?php echo $current === $stored ? ' is-on' : ''; ?>" name="editorial_status" value="<?php echo esc_attr($stored); ?>">
                        <?php echo noelclark_v1_mail_room_admin_status_icon($stored); ?>
                        <span><?php echo esc_html($option_label); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </details>
    </form>
    <?php
}

/**
 * Letter detail. The verdict comes first, then the letter.
 *
 * @param string $mr_raw Raw mr query value.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_detail($mr_raw, $parsed) {
    $back_url = noelclark_v1_mail_room_admin_url(
        noelclark_v1_mail_room_admin_context_args($parsed['filters'], $parsed['page'])
    );
    ?>
    <div class="nc-mr-admin__canvas">
        <nav class="nc-mr-admin__back">
            <a href="<?php echo esc_url($back_url); ?>"><?php echo noelclark_v1_mail_room_admin_icon('back'); ?>All letters</a>
        </nav>
        <?php
        $mr_ref = noelclark_v1_mail_room_admin_normalize_ref($mr_raw);

        if ($mr_ref === null) {
            echo '<h1 class="disp nc-mr-admin__detail-name">' . esc_html('Letter') . '</h1>';
            noelclark_v1_mail_room_admin_notice('warning', 'Reference IDs are 32 characters (0–9, a–f).');
            echo '</div>';

            return;
        }

        $loaded = noelclark_v1_mail_room_admin_load_submission($mr_ref);

        if (empty($loaded['ok'])) {
            echo '<h1 class="disp nc-mr-admin__detail-name">' . esc_html('Letter') . '</h1>';

            if (isset($loaded['code']) && $loaded['code'] === 'not_found') {
                noelclark_v1_mail_room_admin_notice('warning', 'No letter found with that reference ID.');
            } else {
                noelclark_v1_mail_room_admin_notice('error', 'This letter could not be loaded.');
            }

            echo '</div>';

            return;
        }

        $row       = $loaded['row'];
        $responses = noelclark_v1_mail_room_admin_load_questionnaire_responses($mr_ref);
        $events    = noelclark_v1_mail_room_admin_load_events($mr_ref);

        if (empty($events['ok']) || !isset($events['rows']) || !is_array($events['rows'])) {
            $row['permission_events_unconfirmed'] = true;
        } else {
            $row['pending_permission_request'] = noelclark_v1_mail_room_permission_open_requested(
                noelclark_v1_mail_room_admin_permission_event_list($events['rows'])
            );
            $row['author_request_hold'] = noelclark_v1_mail_room_admin_author_hold_for_row($events['rows'], $row);
            $row['author_credit_broadening_valid'] = noelclark_v1_mail_room_admin_author_broadening_valid($events['rows'], $row);
            $row['author_withdrawal_standing'] = noelclark_v1_mail_room_admin_author_withdrawal_standing($events['rows'], $row);
        }

        $view      = noelclark_v1_mail_room_admin_letter_view($row, $responses);
        $present   = noelclark_v1_mail_room_admin_verdict_presentation($view);
        noelclark_v1_mail_room_admin_render_status_save_notice();
        noelclark_v1_mail_room_admin_render_note_save_notice();
        noelclark_v1_mail_room_admin_render_request_save_notice();
        ?>
        <header class="nc-mr-admin__detail-head">
            <div class="nc-mr-admin__detail-groups">
                <div class="nc-mr-admin__detail-status">
                    <?php if (!isset($row['editorial_status']) || $row['editorial_status'] === null || $row['editorial_status'] === '') : ?>
                        <p class="eyebrow">Editorial status</p>
                        <span class="pill flag flag-alert">Status record missing</span>
                    <?php else : ?>
                        <?php noelclark_v1_mail_room_admin_render_status_editor($row, $parsed); ?>
                    <?php endif; ?>
                </div>
                <div class="nc-mr-admin__detail-ref">
                    <p class="eyebrow">Reference ID</p>
                    <div class="nc-mr-admin__ref-row">
                        <span class="mono muted nc-mr-admin__ref-line"><?php echo esc_html((string) $row['mr_ref']); ?></span>
                        <?php echo noelclark_v1_mail_room_admin_copy_button((string) $row['mr_ref'], 'Copy reference'); ?>
                    </div>
                </div>
            </div>
            <?php if (noelclark_v1_mail_room_admin_is_withdrawal_pending($row) || noelclark_v1_mail_room_admin_alert_failed($row)) : ?>
                <div class="nc-mr-admin__detail-flags">
                    <?php noelclark_v1_mail_room_admin_render_flags($row); ?>
                </div>
            <?php endif; ?>
        </header>
        <div class="nc-mr-admin__detail-grid">
            <?php
            noelclark_v1_mail_room_admin_render_verdict($present);
            noelclark_v1_mail_room_admin_render_letter($row, $view);
            noelclark_v1_mail_room_admin_render_recipient($row);
            if (function_exists('noelclark_v1_mail_room_admin_render_publication')) {
                noelclark_v1_mail_room_admin_render_publication($row, $view, $parsed, $events);
            }
            noelclark_v1_mail_room_admin_render_next_steps($view);
            noelclark_v1_mail_room_admin_render_rule_folds($view);
            noelclark_v1_mail_room_admin_render_writer($row, $responses, $events, $view, $parsed);
            ?>
        </div>
    </div>
    <?php
}

/**
 * The one verdict for this letter.
 *
 * @param array<string, mixed> $present Presentation from the letter view.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_verdict($present) {
    ?>
    <section class="verdict-banner verdict-banner--<?php echo esc_attr($present['tone']); ?>" aria-label="Permission">
        <h2 class="verdict-title"><?php echo esc_html($present['title']); ?> <em><?php echo esc_html($present['condition']); ?></em></h2>
        <?php if ($present['line'] !== '') : ?>
            <p class="verdict-line"><?php echo esc_html($present['line']); ?></p>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * Permission explanation and the three rule lists, collapsed.
 *
 * @param array<string, mixed> $view Letter view.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_rule_folds($view) {
    $cards  = noelclark_v1_mail_room_admin_rule_cards($view);
    $blocks = array(
        array('We can', 'can', 'ok', 'check'),
        array('We can\'t', 'cant', 'no', 'x'),
        array('Always keep private', 'private', 'pv', 'lock'),
    );
    ?>
    <section class="nc-mr-admin__rules" aria-label="Sharing rules">
        <details class="card fold rule-fold">
            <summary>Can this be shared?</summary>
            <p class="fold-copy">“<?php echo esc_html((string) $view['explanation']); ?>”</p>
            <?php if ($view['notes'] !== array()) : ?>
                <ul class="fold-list">
                    <?php foreach ($view['notes'] as $note) : ?>
                        <li><?php echo esc_html($note); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </details>
        <?php foreach ($blocks as $block) : ?>
            <details class="card fold rule-fold">
                <summary><span class="eyebrow rule-label rule-label--<?php echo esc_attr($block[2]); ?>"><?php echo esc_html($block[0]); ?></span></summary>
                <ul class="rules <?php echo esc_attr($block[2]); ?>">
                    <?php foreach ($cards[$block[1]] as $line) : ?>
                        <li><?php echo noelclark_v1_mail_room_admin_icon($block[3]); ?><span><?php echo esc_html($line); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endforeach; ?>
    </section>
    <?php
}

/**
 * Next steps. The letter is already above these steps.
 *
 * @param array<string, mixed> $view Letter view.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_next_steps($view) {
    $steps = array(
        array('Read the letter', 'Above, in full.'),
        array('Check the rules below', 'Before we use any part of it.'),
    );

    if (strpos((string) $view['explanation'], 'withdrawal request is pending') !== false) {
        $steps[] = array('Pause new sharing', 'A withdrawal request is pending. This is not a decision, and the operative permission is unchanged.');
    } elseif (strpos((string) $view['explanation'], 'request to reduce sharing is pending') !== false) {
        $steps[] = array('Pause new sharing', 'A request to reduce sharing is pending. This is not a decision, and the current permission is unchanged.');
    } elseif (strpos((string) $view['explanation'], 'request to take it down is still open') !== false) {
        $steps[] = array('Pause new sharing', 'A request to take it down is still open. This is not a decision, and the current permission is unchanged.');
    } elseif (strpos((string) $view['explanation'], 'request to make it anonymous is still open') !== false) {
        $steps[] = array('Pause new sharing', 'A request to make it anonymous is still open. This is not a decision, and the current permission is unchanged.');
    } elseif (strpos((string) $view['explanation'], 'credit request is open') !== false) {
        $steps[] = array('Hold new shares', 'A credit request is open. We\'re not making a new share, and the current credit stays.');
    } elseif ($view['mode'] === 'no') {
        $steps[] = array('Stop there', 'We cannot publish this letter.');
    } elseif ($view['mode'] === 'anonymous') {
        $steps[] = array('Decide if we\'ll use it', 'It can be published anonymously. Permission isn\'t selection — that\'s still our call.');
    } elseif ($view['mode'] === 'named' && $view['credit'] !== '') {
        $steps[] = array('Decide if we\'ll use it', 'Permission isn\'t selection — that\'s still our call. If we publish it, credit as ' . $view['credit'] . '.');
    } else {
        $steps[] = array('Decide if we\'ll use it', 'Permission isn\'t selection — that\'s still our call.');
    }
    ?>
    <section class="nc-mr-admin__steps" aria-label="Our next steps">
        <div class="nc-mr-admin__steps-label"><p class="eyebrow">Our next steps</p></div>
        <div class="steps">
            <?php foreach ($steps as $index => $step) : ?>
                <div class="step">
                    <span class="num"><?php echo esc_html((string) ($index + 1)); ?></span>
                    <div class="step-copy">
                        <b><?php echo esc_html($step[0]); ?></b>
                        <span><?php echo esc_html($step[1]); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

/**
 * Writer's recipient wording. Private to this admin screen.
 *
 * @param string $choice Stored display choice.
 * @return string
 */
function noelclark_v1_mail_room_admin_recipient_choice_label($choice) {
    switch ((string) $choice) {
        case 'as_written':
            return 'Yes, show it as I wrote it';
        case 'alternate':
            return 'Show something different instead';
        case 'private':
            return 'No, keep it private';
        default:
            return '';
    }
}

/**
 * Private recipient record for one letter. NULL means the question was not collected.
 *
 * @param array<string, mixed> $row Submission row.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_recipient($row) {
    $original = (isset($row['recipient_original']) && $row['recipient_original'] !== null)
        ? (string) $row['recipient_original']
        : '';
    $choice = (isset($row['recipient_display_choice']) && $row['recipient_display_choice'] !== null)
        ? (string) $row['recipient_display_choice']
        : '';
    $wording = (isset($row['recipient_public_wording']) && $row['recipient_public_wording'] !== null)
        ? (string) $row['recipient_public_wording']
        : '';
    $operative = (isset($row['operative_recipient_display']) && $row['operative_recipient_display'] !== null)
        ? trim((string) $row['operative_recipient_display'])
        : '';
    $hold = (isset($row['protective_hold']) && $row['protective_hold'] !== null)
        ? trim((string) $row['protective_hold'])
        : '';
    $choice_label = noelclark_v1_mail_room_admin_recipient_choice_label($choice);
    $operative_label = noelclark_v1_mail_room_admin_recipient_choice_label($operative);

    if ($operative_label === '' && $operative !== '') {
        $operative_label = $operative;
    }
    ?>
    <section class="card nc-mr-admin__letter" aria-label="Private recipient">
        <div class="nc-mr-admin__letter-head">
            <h3 class="nc-mr-admin__letter-title">Recipient</h3>
        </div>
        <p class="muted">Private submission data. This is not an approved public recipient.</p>
        <?php if ($original === '' && $choice === '' && $wording === '') : ?>
            <p class="muted">Not collected</p>
        <?php else : ?>
            <dl class="nc-mr-admin__letter-meta">
                <div>
                    <dt>As written</dt>
                    <dd><?php echo $original !== '' ? esc_html($original) : noelclark_v1_mail_room_admin_muted_html('Not collected'); ?></dd>
                </div>
                <div>
                    <dt>If shared</dt>
                    <dd><?php echo $choice_label !== '' ? esc_html($choice_label) : noelclark_v1_mail_room_admin_muted_html('Not collected'); ?></dd>
                </div>
                <?php if ($choice === 'alternate' || $wording !== '') : ?>
                    <div>
                        <dt>Readers see</dt>
                        <dd><?php echo $wording !== '' ? esc_html($wording) : noelclark_v1_mail_room_admin_muted_html('Not collected'); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        <?php endif; ?>
        <?php if ($operative === '') : ?>
            <p class="muted">No approved public recipient.</p>
        <?php else : ?>
            <p class="muted"><?php echo esc_html('Approved public display: ' . $operative_label); ?></p>
        <?php endif; ?>
        <?php if ($hold !== '') : ?>
            <p class="muted"><?php echo esc_html('Protective hold: ' . $hold); ?></p>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * The submitted letter. The copy control copies the letter text only.
 *
 * @param array<string, mixed> $row  Submission row.
 * @param array<string, mixed> $view Letter view.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_letter($row, $view) {
    $letter = (string) $row['letter_body'];
    $parts  = noelclark_v1_mail_room_admin_eastern_parts($row['accepted_at']);
    $credit = (string) $view['credit'];
    ?>
    <article class="card nc-mr-admin__letter" aria-label="Submitted letter">
        <div class="nc-mr-admin__letter-head">
            <h3 class="nc-mr-admin__letter-title">The letter</h3>
            <?php if ($view['mode'] === 'named' && $credit !== '' && trim($letter) !== '') : ?>
                <?php echo noelclark_v1_mail_room_admin_copy_button($letter, 'Copy letter'); ?>
            <?php endif; ?>
        </div>
        <dl class="nc-mr-admin__letter-meta">
            <div>
                <dt>Received</dt>
                <dd>
                    <?php if ($parts !== null) : ?>
                        <?php echo esc_html($parts['date'] . ' · ' . $parts['time']); ?>
                    <?php else : ?>
                        <?php echo noelclark_v1_mail_room_admin_time_html($row['accepted_at']); ?>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt>Credit as</dt>
                <dd>
                    <?php if ($view['mode'] === 'named' && $credit !== '') : ?>
                        <span class="nc-mr-admin__credit-name"><?php echo esc_html($credit); ?></span>
                        <?php echo noelclark_v1_mail_room_admin_copy_button($credit, 'Copy name'); ?>
                    <?php elseif ($view['mode'] === 'anonymous') : ?>
                        <?php echo esc_html('Anonymous'); ?>
                    <?php else : ?>
                        <?php echo esc_html('Not authorized'); ?>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <?php if (trim($letter) === '') : ?>
            <p class="nc-mr-admin__letter-body"><?php echo noelclark_v1_mail_room_admin_muted_html('Empty letter'); ?></p>
        <?php else : ?>
            <div class="nc-mr-admin__letter-body"><?php echo esc_html($letter); ?></div>
        <?php endif; ?>
    </article>
    <?php
}

/**
 * Private editorial note. Shown only when the operational state row exists.
 *
 * @param array<string, mixed> $row Submission row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_private_note($row, $parsed) {
    $mr_ref = (string) $row['mr_ref'];

    if (!isset($row['editorial_status']) || $row['editorial_status'] === null || $row['editorial_status'] === '') {
        echo '<p class="eyebrow nc-mr-admin__note-label">Private note</p>';
        echo '<p class="muted">This letter has no status record, so a private note cannot be saved.</p>';

        return;
    }

    $note = isset($row['private_note']) && $row['private_note'] !== null ? (string) $row['private_note'] : '';
    ?>
    <form class="nc-mr-admin__note" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('noelclark_v1_mail_room_private_note_' . $mr_ref); ?>
        <input type="hidden" name="action" value="noelclark_v1_mail_room_save_private_note">
        <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
        <?php foreach (noelclark_v1_mail_room_admin_context_args($parsed['filters'], $parsed['page']) as $key => $value) : ?>
            <?php if ($value === '' || $value === null || ($key === 'paged' && (int) $value <= 1)) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <input type="hidden" name="return_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string) $value); ?>">
        <?php endforeach; ?>
        <label class="eyebrow nc-mr-admin__note-label" for="nc-mr-private-note">Private note</label>
        <textarea id="nc-mr-private-note" name="private_note" rows="5" maxlength="<?php echo esc_attr((string) NOELCLARK_V1_MAIL_ROOM_PRIVATE_NOTE_MAX); ?>"><?php echo esc_textarea($note); ?></textarea>
        <button type="submit" class="btn">Save note</button>
    </form>
    <?php
}

/**
 * Hidden fields that return the administrator to the same letter filters.
 *
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_return_inputs($parsed) {
    foreach (noelclark_v1_mail_room_admin_context_args($parsed['filters'], $parsed['page']) as $key => $value) {
        if ($value === '' || $value === null || ($key === 'paged' && (int) $value <= 1)) {
            continue;
        }

        echo '<input type="hidden" name="return_' . esc_attr($key) . '" value="' . esc_attr((string) $value) . '">';
    }
}

/**
 * Decision outcome for display. Granted is not labeled as withdrawn.
 *
 * @param mixed $payload_json Stored payload.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_decision_outcome_html($payload_json) {
    $pairs    = noelclark_v1_mail_room_admin_event_payload_pairs($payload_json);
    $outcomes = noelclark_v1_mail_room_admin_decision_outcomes();
    $outcome  = is_array($pairs) && isset($pairs['outcome']) ? $pairs['outcome'] : '';

    if (isset($outcomes[$outcome])) {
        return esc_html($outcomes[$outcome]);
    }

    if ($outcome === '') {
        return noelclark_v1_mail_room_admin_muted_html('Decision could not be read.');
    }

    return noelclark_v1_mail_room_admin_code_html($outcome);
}

/**
 * Granted or Declined from a decision event. Empty when it cannot be read.
 *
 * @param array<string, mixed> $event Decision event.
 * @return string
 */
function noelclark_v1_mail_room_admin_request_outcome_label($event) {
    $pairs    = noelclark_v1_mail_room_admin_event_payload_pairs($event['payload_json']);
    $outcomes = noelclark_v1_mail_room_admin_decision_outcomes();
    $outcome  = is_array($pairs) && isset($pairs['outcome']) ? $pairs['outcome'] : '';

    return isset($outcomes[$outcome]) ? $outcomes[$outcome] : '';
}

/**
 * Granted and Declined buttons for one open request.
 *
 * @param string $mr_ref Reference.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @param string $action      Admin-post action.
 * @param string $nonce       Nonce action without the reference.
 * @param bool   $allow_grant Whether Granted is offered. Declined stays available.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_decision_form($mr_ref, $parsed, $action, $nonce, $allow_grant = true) {
    echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field($nonce . $mr_ref);
    echo '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
    echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
    noelclark_v1_mail_room_admin_render_return_inputs($parsed);
    echo '<button type="submit" class="btn" name="outcome" value="declined">' . esc_html('Declined') . '</button>';

    if ($allow_grant) {
        echo '<button type="submit" class="btn ghost" name="outcome" value="granted">' . esc_html('Granted') . '</button>';
    }

    echo '</form>';
}

/**
 * Current sharing permission, with the existing save control when a change is allowed.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @param bool                 $has_state Whether a status record exists.
 * @param string               $original  Original sharing choice.
 * @param string               $credit    Original public credit.
 * @param string               $current   Stored operative treatment.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_current_permission($row, $parsed, $has_state, $original, $credit, $current) {
    $allowed    = $has_state ? noelclark_v1_mail_room_admin_operative_choices($original, $credit) : array();
    $can_change = false;

    foreach (array_keys($allowed) as $stored) {
        if ($stored !== $current) {
            $can_change = true;
            break;
        }
    }

    if (!$can_change) {
        echo esc_html(noelclark_v1_mail_room_admin_permission_label($current, $credit));

        return;
    }

    $mr_ref = (string) $row['mr_ref'];

    echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('noelclark_v1_mail_room_operative_treatment_' . $mr_ref);
    echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_set_operative_treatment">';
    echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
    noelclark_v1_mail_room_admin_render_return_inputs($parsed);
    echo '<select id="nc-mr-operative-treatment" name="operative_treatment" aria-label="' . esc_attr('How we share it now') . '">';

    if (!isset($allowed[$current])) {
        echo '<option value="" selected>' . esc_html(noelclark_v1_mail_room_admin_permission_label($current, $credit)) . '</option>';
    }

    foreach (array_keys($allowed) as $stored) {
        echo '<option value="' . esc_attr($stored) . '"' . selected($stored, $current, false) . '>'
            . esc_html(noelclark_v1_mail_room_admin_permission_label($stored, $credit))
            . '</option>';
    }

    echo '</select>';
    echo '<button type="submit" class="btn">' . esc_html('Save') . '</button>';
    echo '</form>';
}

/**
 * Original and current sharing permissions, closed until opened.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @param bool                 $has_state Whether a status record exists.
 * @param string               $original  Original sharing choice.
 * @param string               $credit    Original public credit.
 * @param string               $current   Stored operative treatment.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_sharing_permissions($row, $parsed, $has_state, $original, $credit, $current) {
    echo '<details class="fold nc-mr-admin__sharing-fold">';
    echo '<summary>' . esc_html('Sharing Permissions') . '</summary>';
    echo '<dl class="kv nc-mr-admin__permission-facts">';
    echo '<dt>' . esc_html('Original sharing permission') . '</dt>';
    echo '<dd>' . esc_html(noelclark_v1_mail_room_admin_permission_label($original, $credit)) . '</dd>';
    echo '<dt>' . esc_html('Current sharing permission') . '</dt><dd>';
    noelclark_v1_mail_room_admin_render_current_permission($row, $parsed, $has_state, $original, $credit, $current);
    echo '</dd></dl></details>';
}

/**
 * The latest request of each kind, without timestamps.
 *
 * @param array<int, array{ask: string, decision: string, note: string, form: string}> $items  Current requests.
 * @param string                                                                       $mr_ref Reference.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_request_summary($items, $mr_ref, $parsed) {
    echo '<h3 class="nc-mr-admin__request-question">' . esc_html('What did the writer request?') . '</h3>';

    if ($items === array()) {
        echo '<p class="nc-mr-admin__request-answer">' . esc_html('Nothing yet.') . '</p>';

        return;
    }

    echo '<ul class="nc-mr-admin__request-asks">';

    foreach ($items as $item) {
        echo '<li>' . esc_html($item['ask']) . '</li>';
    }

    echo '</ul>';
    echo '<h3 class="nc-mr-admin__request-question">' . esc_html('What was my decision?') . '</h3>';

    foreach ($items as $item) {
        echo '<div class="nc-mr-admin__decision">';

        if (count($items) > 1) {
            echo '<p class="nc-mr-admin__decision-ask">' . esc_html($item['ask']) . '</p>';
        }

        echo '<p class="nc-mr-admin__decision-state">' . esc_html($item['decision']) . '</p>';

        if ($item['note'] !== '') {
            echo '<p class="muted">' . esc_html($item['note']) . '</p>';
        }

        if ($item['form'] === 'withdrawal') {
            noelclark_v1_mail_room_admin_render_decision_form(
                $mr_ref,
                $parsed,
                'noelclark_v1_mail_room_record_withdrawal_decision',
                'noelclark_v1_mail_room_withdrawal_decision_'
            );
        } elseif ($item['form'] === 'permission' || $item['form'] === 'permission_decline') {
            noelclark_v1_mail_room_admin_render_decision_form(
                $mr_ref,
                $parsed,
                'noelclark_v1_mail_room_record_permission_decision',
                'noelclark_v1_mail_room_permission_decision_',
                $item['form'] === 'permission'
            );
        }

        echo '</div>';
    }
}

/**
 * Record a request the writer made after submitting the letter.
 *
 * @param array<string, mixed> $row              Submission row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @param bool                 $withdrawal_open  Whether a withdrawal request is open.
 * @param bool                 $permission_open  Whether a permission-change request is open.
 * @param string               $status           Stored withdrawal status.
 * @param string               $credit           Original public credit.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_new_request($row, $parsed, $withdrawal_open, $permission_open, $status, $credit) {
    $mr_ref          = (string) $row['mr_ref'];
    $original        = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $current         = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $show_withdrawal = !$withdrawal_open && $status !== NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING;
    $show_permission = !$permission_open;
    $permission_choices = array();

    if ($show_permission) {
        foreach (array_keys(noelclark_v1_mail_room_admin_treatments()) as $stored) {
            if (noelclark_v1_mail_room_permission_request_verdict($original, $credit, $current, $stored) === 'ok') {
                $permission_choices[] = $stored;
            }
        }
    }

    echo '<div class="nc-mr-admin__new-request">';
    echo '<input type="checkbox" id="nc-mr-new-request" class="nc-mr-admin__new-request-toggle">';
    echo '<label class="btn" for="nc-mr-new-request">' . esc_html('Record a New Request') . '</label>';
    echo '<p class="muted nc-mr-admin__request-help">' . esc_html('Use this when a writer contacts you after submitting their letter and asks to change their original instructions.') . '</p>';
    echo '<div class="nc-mr-admin__new-request-forms">';

    if (!$show_withdrawal && !$show_permission) {
        echo '<p class="muted">' . esc_html('A request is already waiting for your decision.') . '</p>';
    } elseif ($show_permission && $permission_choices === array() && !$show_withdrawal) {
        echo '<p class="muted">' . esc_html('There is no sharing-permission change to record.') . '</p>';
    }

    if ($show_withdrawal) {
        echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('noelclark_v1_mail_room_withdrawal_request_' . $mr_ref);
        echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_record_withdrawal_request">';
        echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
        noelclark_v1_mail_room_admin_render_return_inputs($parsed);
        echo '<button type="submit" class="btn">' . esc_html('Withdraw their letter') . '</button>';
        echo '</form>';
    }

    if ($permission_choices !== array()) {
        echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('noelclark_v1_mail_room_permission_request_' . $mr_ref);
        echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_record_permission_request">';
        echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
        noelclark_v1_mail_room_admin_render_return_inputs($parsed);
        echo '<label class="nc-mr-admin__request-choice" for="nc-mr-permission-request">' . esc_html('Change sharing permission') . '</label>';
        echo '<select id="nc-mr-permission-request" name="requested">';

        foreach ($permission_choices as $stored) {
            echo '<option value="' . esc_attr($stored) . '">'
                . esc_html(noelclark_v1_mail_room_admin_permission_label($stored, $credit))
                . '</option>';
        }

        echo '</select>';
        echo '<button type="submit" class="btn">' . esc_html('Record this request') . '</button>';
        echo '</form>';
    }

    echo '</div></div>';
}

/**
 * Writer card. Private contact, questionnaire, and the editorial note.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @param array<string, mixed> $events    Event loader result.
 * @param array<string, mixed> $view      Letter view.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_writer($row, $responses, $events, $view, $parsed) {
    ?>
    <aside class="nc-mr-admin__side">
            <div class="card nc-mr-admin__writer">
                <div class="nc-mr-admin__writer-head">
                    <p class="eyebrow">Writer · private</p>
                    <span class="pill tag"><?php echo noelclark_v1_mail_room_admin_icon('eye'); ?>Just us</span>
                </div>
                <p class="muted nc-mr-admin__writer-note">Private contact only. This is not the public credit.</p>
                <dl class="kv">
                    <dt>Contact name</dt>
                    <dd><?php echo $row['participant_ref'] === null ? noelclark_v1_mail_room_admin_muted_html('Contact record missing') : esc_html((string) $row['display_name']); ?></dd>
                    <dt>Email</dt>
                    <dd><?php echo $row['participant_ref'] === null ? noelclark_v1_mail_room_admin_muted_html('Contact record missing') : esc_html((string) $row['email']); ?></dd>
                </dl>
                <details class="fold">
                    <summary>Questionnaire answers <span class="muted fold-note">Private</span></summary>
                    <?php noelclark_v1_mail_room_admin_render_questionnaires($responses, true); ?>
                </details>
                <?php noelclark_v1_mail_room_admin_render_private_note($row, $parsed); ?>
                <details class="fold">
                    <summary>Full sharing &amp; editing terms <span class="muted fold-note">Mail Room Submission Terms</span></summary>
                    <p class="muted fold-copy">Permission does not require publication (Terms §4). The limits below cite the Mail Room Submission Terms that apply to this letter.</p>
                    <h4 class="fold-label">What can be changed</h4>
                    <ul class="fold-list">
                        <?php foreach ($view['guidance']['change'] as $line) : ?>
                            <li><?php echo esc_html($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <h4 class="fold-label">What must stay private</h4>
                    <ul class="fold-list">
                        <?php foreach ($view['guidance']['private'] as $line) : ?>
                            <li><?php echo esc_html($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($view['notes'] !== array()) : ?>
                        <h4 class="fold-label">Record notes</h4>
                        <ul class="fold-list">
                            <?php foreach ($view['notes'] as $note) : ?>
                                <li><?php echo esc_html($note); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </details>
                <details class="fold">
                    <summary>Submission details</summary>
                    <?php noelclark_v1_mail_room_admin_render_submission_details($row, $responses); ?>
                </details>
                <details class="fold">
                    <summary>Email alert details</summary>
                    <?php noelclark_v1_mail_room_admin_render_notification_facts($row); ?>
                </details>
                <?php
                noelclark_v1_mail_room_admin_render_author_sharing($row, $parsed);
                noelclark_v1_mail_room_admin_render_requests($row, $events, $parsed);
                ?>
                <details class="fold">
                    <summary>History</summary>
                    <?php noelclark_v1_mail_room_admin_render_events($events, true); ?>
                </details>
            </div>
    </aside>
    <?php
}

/**
 * Changes the writer asked for, and the decision on each.
 *
 * @param array<string, mixed> $row    Submission row.
 * @param array<string, mixed> $events Event loader result.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_requests($row, $events, $parsed) {
    noelclark_v1_mail_room_admin_render_author_log($row, $events, $parsed);
}

/**
 * Age confirmation plus the technical record, inside one fold.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_submission_details($row, $responses) {
    $meaning = noelclark_v1_mail_room_admin_status_meaning($row['editorial_status']);
    echo '<dl class="nc-mr-admin__facts-list">';
    noelclark_v1_mail_room_admin_fact(
        '18 or older',
        esc_html(((int) $row['age_attested']) ? 'Confirmed by the sender' : 'Not confirmed')
    );
    noelclark_v1_mail_room_admin_fact('Terms agreed', noelclark_v1_mail_room_admin_time_html($row['terms_accepted_at']));
    noelclark_v1_mail_room_admin_fact('Terms version', noelclark_v1_mail_room_admin_terms_version_html($row['legal_document_version']));

    if ($meaning !== '') {
        noelclark_v1_mail_room_admin_fact('Status', esc_html($meaning));
    }

    noelclark_v1_mail_room_admin_render_technical_facts($row, $responses);
    echo '</dl>';
}

/**
 * Detail fact row.
 *
 * @param string $label Plain-text label.
 * @param string $html  Safe HTML value.
 * @return void
 */
function noelclark_v1_mail_room_admin_fact($label, $html) {
    printf(
        '<div class="nc-mr-admin__fact"><dt>%1$s</dt><dd>%2$s</dd></div>',
        esc_html($label),
        $html
    );
}

/**
 * Small explanatory line under a fact value.
 *
 * @param string $text Plain text.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_help_html($text) {
    return '<span class="nc-mr-admin__help">' . esc_html($text) . '</span>';
}

/**
 * Verdict labels for CAN THIS BE SHARED?
 *
 * Named credit with a stored public name displays as full permission.
 * The assessment items still record the credit requirement as attribution.
 *
 * @return array<string, string> Verdict key => label.
 */
function noelclark_v1_mail_room_admin_share_verdicts() {
    return array(
        'no'         => 'NO',
        'restricted' => 'YES, WITH RESTRICTIONS',
        'yes'        => 'YES',
    );
}

/**
 * How much public use a stored choice actually grants.
 *
 * named_credit without a public credit grants nothing. An unrecognized
 * choice grants nothing that can be confirmed.
 *
 * @param mixed $choice Stored sharing choice.
 * @param mixed $credit Stored public credit.
 * @return string none|invalid_named|anonymous|named|unknown
 */
function noelclark_v1_mail_room_admin_share_choice_level($choice, $credit) {
    $treatments = noelclark_v1_mail_room_admin_treatments();
    $choice     = (string) $choice;

    if (!isset($treatments[$choice])) {
        return 'unknown';
    }

    if ($choice === 'not_public') {
        return 'none';
    }

    if ($choice === 'anonymous') {
        return 'anonymous';
    }

    if ($choice === 'named_credit') {
        return trim((string) $credit) === '' ? 'invalid_named' : 'named';
    }

    return 'unknown';
}

/**
 * Rank for comparing two stored choices. Higher is a broader grant.
 *
 * @param string $level Choice level.
 * @return int
 */
function noelclark_v1_mail_room_admin_share_level_rank($level) {
    if ($level === 'named') {
        return 2;
    }

    if ($level === 'anonymous') {
        return 1;
    }

    if ($level === 'none' || $level === 'invalid_named') {
        return 0;
    }

    return -1;
}

/**
 * One assessment row. Verdict is never YES in the builders below.
 *
 * @param string   $key      Stable key.
 * @param string   $title    Row title.
 * @param string   $verdict  no|restricted|yes
 * @param string   $summary  Lead sentence.
 * @param string[] $details  Limits or reasons.
 * @param string   $credit   Public credit, only when that exact string is authorized.
 * @return array{key: string, title: string, verdict: string, summary: string, details: string[], credit: string}
 */
function noelclark_v1_mail_room_admin_share_item($key, $title, $verdict, $summary, $details, $credit = '') {
    return array(
        'key'     => $key,
        'title'   => $title,
        'verdict' => $verdict,
        'summary' => $summary,
        'details' => $details,
        'credit'  => $credit,
    );
}

/**
 * Questionnaire answers stay private. A stored consent version is not a
 * public-use grant.
 *
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @return array{key: string, title: string, verdict: string, summary: string, details: string[], credit: string}
 */
function noelclark_v1_mail_room_admin_share_questionnaire_item($responses) {
    $title = 'Questionnaire answers';

    if (!is_array($responses) || empty($responses['ok']) || !isset($responses['rows']) || !is_array($responses['rows'])) {
        return noelclark_v1_mail_room_admin_share_item(
            'questionnaire',
            $title,
            'no',
            'No. Questionnaire answers could not be confirmed, so none are authorized for public sharing.',
            array(
                'No separate permission to quote or attribute questionnaire answers is on file.',
            )
        );
    }

    if ($responses['rows'] === array()) {
        return noelclark_v1_mail_room_admin_share_item(
            'questionnaire',
            $title,
            'no',
            'No. No questionnaire answers are linked to this letter.',
            array(
                'Nothing here authorizes public quotation or attribution of questionnaire answers.',
            )
        );
    }

    $consent_recorded = false;

    foreach ($responses['rows'] as $response) {
        if (is_array($response) && isset($response['legal_document_version']) && trim((string) $response['legal_document_version']) !== '') {
            $consent_recorded = true;
            break;
        }
    }

    $details = array(
        'The standard questionnaire record does not authorize public quotation or identifiable attribution.',
        'Letter sharing permission does not change this.',
    );

    if ($consent_recorded) {
        $details[] = 'A questionnaire consent version is recorded. That record is not permission to share the answers.';
    } else {
        $details[] = 'No separate permission to share these answers is stored.';
    }

    return noelclark_v1_mail_room_admin_share_item(
        'questionnaire',
        $title,
        'no',
        'No. Answers on file are for private review only.',
        $details
    );
}

/**
 * Private contact, third-party details, and artifacts. Letter permission
 * does not authorize any of them.
 *
 * @return array{key: string, title: string, verdict: string, summary: string, details: string[], credit: string}
 */
function noelclark_v1_mail_room_admin_share_other_item() {
    return noelclark_v1_mail_room_admin_share_item(
        'other',
        'Other consent-sensitive information',
        'no',
        'No. Private contact information, other people’s identifying details, and artifacts are not authorized.',
        array(
            'The private contact name and email stay private (Terms §7, §12). They are never a substitute for a public credit.',
            'Private or identifying details about other people mentioned in the letter are not authorized for publication (Terms §9).',
            'Scans, photographs, recordings, and other artifacts need their own permission. None is recorded with this letter (Terms §6).',
        )
    );
}

/**
 * Letter and attribution assessments for one effective stored level.
 *
 * @param string $level  Effective level.
 * @param string $choice Effective stored choice.
 * @param string $credit Effective public credit.
 * @param string $original_credit Original public credit, for a historical note.
 * @return array<int, array{key: string, title: string, verdict: string, summary: string, details: string[], credit: string}>
 */
function noelclark_v1_mail_room_admin_share_letter_items($level, $choice, $credit, $original_credit) {
    $letter_limits = array(
        'Some or all of the letter may be used, including a shortened excerpt (Terms §3, §5).',
        'Light edits may fix typos, formatting, and length. They must not change the meaning or attribute words the sender did not write (Terms §5).',
        'Remove private or identifying details before anything is shared (Terms §5, §9).',
    );

    if ($level === 'anonymous') {
        return array(
            noelclark_v1_mail_room_admin_share_item(
                'letter',
                'Letter text',
                'restricted',
                'Yes, with restrictions. The letter may be considered only if the sender is not identified.',
                array_merge(
                    $letter_limits,
                    array('Remove anything that could identify the sender (Terms §3).')
                )
            ),
            noelclark_v1_mail_room_admin_share_item(
                'attribution',
                'Attribution',
                'no',
                'No. This permission does not authorize a name or any public credit.',
                array(
                    'Do not attach a name, initials, or pseudonym (Terms §3).',
                    'Do not use the private contact name or email.',
                )
            ),
        );
    }

    if ($level === 'named') {
        $details = array(
            'Do not use any other name (Terms §3, §12).',
            'Do not use the private contact name or email.',
        );
        $original_credit = trim((string) $original_credit);

        if ($original_credit !== '' && $original_credit !== trim((string) $credit)) {
            $details[] = 'The credit originally submitted was: ' . $original_credit . '. That earlier credit is not an additional authorized name.';
        }

        return array(
            noelclark_v1_mail_room_admin_share_item(
                'letter',
                'Letter text',
                'restricted',
                'Yes, with restrictions. The letter may be considered only with the recorded public credit.',
                $letter_limits
            ),
            noelclark_v1_mail_room_admin_share_item(
                'attribution',
                'Attribution',
                'restricted',
                'Yes, with restrictions. Only this public credit may be used.',
                $details,
                trim((string) $credit)
            ),
        );
    }

    if ($choice === 'named_credit' || $level === 'invalid_named') {
        return array(
            noelclark_v1_mail_room_admin_share_item(
                'letter',
                'Letter text',
                'no',
                'No. With their name is recorded, but there is no name to use.',
                array(
                    'The letter stays private until there is a name to use.',
                    'This is not the same as sharing it anonymously.',
                    'Don\'t use their private contact name.',
                )
            ),
            noelclark_v1_mail_room_admin_share_item(
                'attribution',
                'Attribution',
                'no',
                'No. No public credit is recorded.',
                array(
                    'The private contact name and email are not a public credit (Terms §3, §12).',
                )
            ),
        );
    }

    $not_public_details = array(
        'The letter may be read and reviewed privately.',
        'Do not publish it in whole, in part, or as a quotation (Terms §3, §5).',
    );

    if ($choice === 'not_public' || $level === 'none') {
        $not_public_details[] = 'Independent work on a similar subject must not reproduce these words (Terms §8).';
    } else {
        $not_public_details = array(
            'The sharing permission could not be confirmed, so the letter stays private.',
        );
    }

    return array(
        noelclark_v1_mail_room_admin_share_item(
            'letter',
            'Letter text',
            'no',
            'No. These words are not authorized for public sharing.',
            $not_public_details
        ),
        noelclark_v1_mail_room_admin_share_item(
            'attribution',
            'Attribution',
            'no',
            'No. Public attribution is not authorized.',
            array(
                'Do not attach a name or public credit.',
                'Do not use the private contact name or email.',
            )
        ),
    );
}

/**
 * Four permission assessments derived from stored choices.
 *
 * The current sharing record cannot outrun the original submission:
 * a broader current value is not a separate grant.
 * Editorial status is ignored. Selected and Published do not add permission.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @return array{notes: string[], items: array<int, array{key: string, title: string, verdict: string, summary: string, details: string[], credit: string}>}
 */
function noelclark_v1_mail_room_admin_share_assessments($row, $responses) {
    $questionnaire = noelclark_v1_mail_room_admin_share_questionnaire_item($responses);
    $other         = noelclark_v1_mail_room_admin_share_other_item();
    $original      = noelclark_v1_mail_room_admin_share_choice_level($row['sharing_choice'], $row['public_credit']);
    $notes         = array();

    if ($row['editorial_status'] === null) {
        $notes[] = 'The current sharing record is missing, so nothing is permitted until it is confirmed.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('unknown', '', '', '');

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    $withdrawal = $row['withdrawal_request_status'];

    if ((string) $withdrawal === NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING) {
        $notes[] = 'A withdrawal request is pending. New public sharing is paused. This is not a decision, and the original and operative permissions are unchanged.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('none', 'not_public', '', '');
        $letter[0]['summary'] = 'No. A withdrawal request is pending, so new public sharing is paused.';
        $letter[0]['details'] = array(
            'The request has not been granted or declined.',
            'The original sharing choice and the operative permission are unchanged.',
        );

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    if (!empty($row['permission_events_unconfirmed'])) {
        $notes[] = 'Permission requests could not be confirmed, so nothing is permitted.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('unknown', '', '', '');

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    if (isset($row['author_request_hold']) && $row['author_request_hold'] === 'takedown') {
        $notes[] = 'A request to take it down is still open, so new public sharing is paused. This is not a decision, and the current permission is unchanged.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('none', 'not_public', '', '');
        $letter[0]['summary'] = 'No. A request to take it down is still open, so new public sharing is paused.';
        $letter[0]['details'] = array(
            'The request is still to do.',
            'The original sharing choice and the current permission are unchanged.',
        );

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    $pending_requested = isset($row['pending_permission_request']) ? (string) $row['pending_permission_request'] : '';
    $original_credit   = isset($row['public_credit']) && $row['public_credit'] !== null ? (string) $row['public_credit'] : '';
    $current_treatment = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $current_credit    = isset($row['operative_public_credit']) && $row['operative_public_credit'] !== null
        ? (string) $row['operative_public_credit']
        : '';

    if (
        $pending_requested !== ''
        && noelclark_v1_mail_room_permission_request_reduces(
            isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '',
            $original_credit,
            $current_treatment,
            $current_credit,
            $pending_requested
        )
    ) {
        $notes[] = 'A request to reduce sharing is pending. New public sharing is paused. This is not a decision, and the original and current permissions are unchanged.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('none', 'not_public', '', '');
        $letter[0]['summary'] = 'No. A request to reduce sharing is pending, so new public sharing is paused.';
        $letter[0]['details'] = array(
            'The request has not been granted or declined.',
            'The original sharing choice and the current permission are unchanged.',
        );

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    if (isset($row['author_request_hold']) && $row['author_request_hold'] === 'anonymous') {
        $notes[] = 'A request to make it anonymous is still open, so new public sharing is paused. This is not a decision, and the current permission is unchanged.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('none', 'not_public', '', '');
        $letter[0]['summary'] = 'No. A request to make it anonymous is still open, so new public sharing is paused.';
        $letter[0]['details'] = array(
            'The request is still to do.',
            'The original sharing choice and the current permission are unchanged.',
        );

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    $operative = noelclark_v1_mail_room_admin_share_choice_level($row['operative_treatment'], $row['operative_public_credit']);

    if ($operative === 'unknown' || $original === 'unknown') {
        $notes[] = 'A stored sharing choice could not be confirmed, so nothing is permitted.';
        $letter  = noelclark_v1_mail_room_admin_share_letter_items('unknown', '', '', '');

        return array(
            'notes' => $notes,
            'items' => array($letter[0], $letter[1], $questionnaire, $other),
        );
    }

    $effective_choice = (string) $row['operative_treatment'];
    $effective_credit = (string) $row['operative_public_credit'];
    $effective_level  = $operative;
    $original_rank    = noelclark_v1_mail_room_admin_share_level_rank($original);
    $operative_rank   = noelclark_v1_mail_room_admin_share_level_rank($operative);

    if ($operative_rank > $original_rank) {
        $notes[] = 'The current sharing permission is broader than the original choice. No separate grant is recorded, so this answer follows the original choice.';
        $effective_choice = (string) $row['sharing_choice'];
        $effective_credit = (string) $row['public_credit'];
        $effective_level  = $original;
    } elseif ($operative_rank < $original_rank) {
        $notes[] = 'The current sharing permission is narrower than the original choice. This answer follows the current sharing permission.';
    }

    $withdrawal_standing = isset($row['author_withdrawal_standing']) ? (string) $row['author_withdrawal_standing'] : '';

    if ($withdrawal_standing !== '' && $withdrawal_standing !== 'clear' && noelclark_v1_mail_room_admin_share_level_rank($effective_level) > 0) {
        if ($withdrawal_standing === 'completed') {
            $notes[] = 'A completed withdrawal stands, so this answer stays not for public sharing.';
        } else {
            $notes[] = 'A withdrawal or a request to reduce sharing is still open, so this answer stays not for public sharing.';
        }

        $effective_choice = 'not_public';
        $effective_credit = '';
        $effective_level  = 'none';
    }

    // Public letter pages, archives, and sharing features must use this fallback.
    // Call noelclark_v1_mail_room_admin_author_broadening_valid() first, then
    // noelclark_v1_mail_room_admin_share_assessments(). The stored operative
    // credit is not authorization on its own.
    $credit_relation = noelclark_v1_mail_room_author_credit_relation(
        trim((string) $effective_credit),
        trim((string) $row['public_credit']),
        trim((string) $row['public_credit'])
    );

    if (($credit_relation === 'broader' || $credit_relation === 'different') && (!isset($row['author_credit_broadening_valid']) || $row['author_credit_broadening_valid'] !== true)) {
        $notes[] = "The broader public credit doesn't have a valid confirmation, so this answer uses the original credit.";
        $effective_credit = (string) $row['public_credit'];
    }

    $letter = noelclark_v1_mail_room_admin_share_letter_items(
        $effective_level,
        $effective_choice,
        $effective_credit,
        (string) $row['public_credit']
    );

    if (isset($row['author_request_hold']) && $row['author_request_hold'] === 'credit') {
        $notes[] = 'A credit request is open, so we\'re not making a new share. The current credit stays.';
        $letter[0]['summary'] .= ' A credit request is open, so we\'re not making a new share. The current credit stays.';
        $letter[0]['details'][] = 'The current permission and the current credit are unchanged.';
    }

    return array(
        'notes' => $notes,
        'items' => array($letter[0], $letter[1], $questionnaire, $other),
    );
}

/**
 * One plain-English explanation for the letter verdict.
 *
 * The verdict itself is shown separately. Questionnaire and private-contact
 * limits stay out of this sentence.
 *
 * @param array{verdict: string, summary: string, details: string[]} $letter      Letter assessment.
 * @param array{verdict: string}                                     $attribution Attribution assessment.
 * @return string
 */
function noelclark_v1_mail_room_admin_share_explanation($letter, $attribution) {
    $summary = (string) $letter['summary'];

    if (strpos($summary, 'withdrawal request is pending') !== false) {
        return 'A withdrawal request is pending. New public sharing is paused. This is not a decision, and the original and operative permissions are unchanged.';
    }

    if (strpos($summary, 'request to reduce sharing is pending') !== false) {
        return 'A request to reduce sharing is pending. New public sharing is paused. This is not a decision, and the original and current permissions are unchanged.';
    }

    if (strpos($summary, 'request to take it down is still open') !== false) {
        return 'A request to take it down is still open, so new public sharing is paused. This is not a decision, and the current permission is unchanged.';
    }

    if (strpos($summary, 'request to make it anonymous is still open') !== false) {
        return 'A request to make it anonymous is still open, so new public sharing is paused. This is not a decision, and the current permission is unchanged.';
    }

    if (strpos($summary, 'credit request is open') !== false) {
        return 'A credit request is open, so we\'re not making a new share. The current credit stays. This is not a change to the current permission.';
    }

    if (strpos($summary, 'no public credit is on file') !== false) {
        return 'The writer chose to be named, but no public credit is on file. The private contact name is not a substitute, and this is not anonymous permission.';
    }

    if ($letter['verdict'] === 'restricted' && $attribution['verdict'] === 'no') {
        return 'The writer has given permission to share all or part of this letter publicly, but their identity must remain private. No name or identifying details may be included.';
    }

    if ($letter['verdict'] === 'restricted' && $attribution['verdict'] === 'restricted') {
        return 'The writer has given permission to share all or part of this letter publicly, as long as their chosen credit name is used.';
    }

    if ($letter['verdict'] === 'yes') {
        return 'The letter is explicitly authorized for public sharing, with no further limit recorded.';
    }

    foreach ($letter['details'] as $detail) {
        if (strpos($detail, 'could not be confirmed') !== false) {
            return 'A stored sharing choice could not be confirmed, so nothing is permitted.';
        }
    }

    return 'The writer has not given permission to share this letter publicly. It may only be read and reviewed privately.';
}

/**
 * What may be edited, and what must stay private, for the current verdict.
 *
 * Wording only. The verdict still comes from the stored assessments.
 *
 * @param array{verdict: string, summary: string, details: string[]} $letter      Letter assessment.
 * @param array{verdict: string, details: string[]}                  $attribution Attribution assessment.
 * @return array{change: string[], private: string[]}
 */
function noelclark_v1_mail_room_admin_share_guidance($letter, $attribution) {
    $summary = (string) $letter['summary'];
    $private_common = array(
        'Private contact information is not authorized for publication (Terms §7, §12).',
        'Questionnaire answers are not authorized for publication under the current consent.',
        'Identifying details about other people are not authorized for publication (Terms §9).',
        'Scans, photographs, recordings, and other artifacts need their own permission. None is recorded with this letter (Terms §6).',
    );
    $edits = array(
        'The letter may be used in whole or as a shortened excerpt (Terms §3, §5).',
        'Light edits may fix typos, formatting, and length. Edits must not change the meaning or attribute words the writer did not write (Terms §5).',
    );

    if (strpos($summary, 'withdrawal request is pending') !== false) {
        return array(
            'change'  => array('Nothing new may be shared publicly while this withdrawal request is pending. The request is not a decision.'),
            'private' => array(
                'The original sharing choice and the operative permission are unchanged.',
                $private_common[0],
                $private_common[1],
            ),
        );
    }

    if (strpos($summary, 'request to reduce sharing is pending') !== false) {
        return array(
            'change'  => array('Nothing new may be shared publicly while this request to reduce sharing is pending. The request is not a decision.'),
            'private' => array(
                'The original sharing choice and the current permission are unchanged.',
                $private_common[0],
                $private_common[1],
            ),
        );
    }

    if (strpos($summary, 'request to take it down is still open') !== false || strpos($summary, 'request to make it anonymous is still open') !== false) {
        return array(
            'change'  => array('Nothing new may be shared publicly while this request is still open. The request is not a decision.'),
            'private' => array(
                'The original sharing choice and the current permission are unchanged.',
                $private_common[0],
                $private_common[1],
            ),
        );
    }

    if (strpos($summary, 'credit request is open') !== false) {
        return array(
            'change'  => array('We\'re not making a new share while this credit request is open. The current credit stays.'),
            'private' => array(
                'The current permission and the current credit are unchanged.',
                $private_common[0],
                $private_common[1],
            ),
        );
    }

    if (strpos($summary, 'no public credit is on file') !== false) {
        return array(
            'change'  => array('Nothing may be prepared for public sharing while no credit name is on file.'),
            'private' => array(
                'The letter is not authorized for public sharing.',
                'The private contact name is not a public credit, and this is not anonymous permission (Terms §3, §12).',
                $private_common[1],
                $private_common[3],
            ),
        );
    }

    if ($letter['verdict'] === 'restricted' && $attribution['verdict'] === 'no') {
        return array(
            'change'  => array_merge(
                $edits,
                array('Details that could identify the writer may be removed before anything is shared (Terms §3, §5).')
            ),
            'private' => array_merge(
                array('No name, initials, or pseudonym may be attached (Terms §3).'),
                $private_common
            ),
        );
    }

    if ($letter['verdict'] === 'restricted' && $attribution['verdict'] === 'restricted') {
        $private = array(
            'No name other than the writer\'s chosen credit name may be used (Terms §3, §12).',
        );

        foreach ($attribution['details'] as $detail) {
            if (strpos($detail, 'originally submitted') !== false) {
                $private[] = $detail;
            }
        }

        return array(
            'change'  => array_merge(
                $edits,
                array('Private or identifying details about other people may be removed before anything is shared (Terms §5, §9).')
            ),
            'private' => array_merge($private, $private_common),
        );
    }

    if ($letter['verdict'] === 'yes') {
        return array(
            'change'  => array('No further limit on the letter text is recorded. This still does not select or publish it (Terms §4).'),
            'private' => array($private_common[0], $private_common[1], $private_common[3]),
        );
    }

    foreach ($letter['details'] as $detail) {
        if (strpos($detail, 'could not be confirmed') !== false) {
            return array(
                'change'  => array('Nothing may be changed for public sharing until the sharing permission is confirmed.'),
                'private' => array(
                    'The letter stays private until the sharing permission is confirmed.',
                    $private_common[0],
                    $private_common[1],
                ),
            );
        }
    }

    $private = array(
        'The words are not published in whole, in part, or as a quotation (Terms §3, §5).',
        'No name or public credit may be attached.',
    );

    foreach ($letter['details'] as $detail) {
        if (strpos($detail, 'Independent work') !== false) {
            $private[] = $detail;
        }
    }

    return array(
        'change'  => array('Nothing in this letter may be changed for public sharing.'),
        'private' => array_merge($private, array($private_common[0], $private_common[1], $private_common[3])),
    );
}

/**
 * Terms version in plain English; the exact version lives in Technical details.
 *
 * @param mixed $version Stored legal_document_version.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_terms_version_html($version) {
    if ($version === null || (string) $version === '') {
        return noelclark_v1_mail_room_admin_muted_html('Not recorded');
    }

    if (defined('NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION') && (string) $version === NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION) {
        return esc_html('Current Mail Room Submission Terms');
    }

    return esc_html('An earlier version (see Technical details)');
}

/**
 * Withdrawal request label.
 *
 * @param mixed $stored Stored withdrawal_request_status.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_withdrawal_html($stored) {
    $labels = noelclark_v1_mail_room_admin_withdrawal_statuses();

    if (isset($labels[(string) $stored])) {
        return esc_html($labels[(string) $stored]);
    }

    return noelclark_v1_mail_room_admin_muted_html('Unrecognized') . ' '
        . noelclark_v1_mail_room_admin_code_html((string) $stored);
}

/**
 * Email alert facts: recipient, status, attempts, and retry timing.
 *
 * @param array<string, mixed> $row Submission row.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_notification_facts($row) {
    $recipient = noelclark_v1_mail_room_notify_recipient();

    echo '<dl class="nc-mr-admin__facts-list">';
    noelclark_v1_mail_room_admin_fact(
        'Alerts go to',
        $recipient !== ''
            ? esc_html($recipient)
            : noelclark_v1_mail_room_admin_muted_html('No valid alert address is configured')
    );

    if ($row['notify_status'] === null) {
        noelclark_v1_mail_room_admin_fact(
            'Status',
            noelclark_v1_mail_room_admin_muted_html('No alert recorded yet')
                . noelclark_v1_mail_room_admin_help_html('The site’s background check normally queues missing alerts within a few minutes.')
        );
        echo '</dl>';

        return;
    }

    $statuses = noelclark_v1_mail_room_admin_notify_statuses();
    $stored   = (string) $row['notify_status'];
    $failed   = ($stored === NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED);

    if (!isset($statuses[$stored])) {
        $status_html = noelclark_v1_mail_room_admin_muted_html('Unrecognized status') . ' '
            . noelclark_v1_mail_room_admin_code_html($stored);
    } else {
        $status_html = ($failed
                ? '<span class="nc-mr-admin__badge nc-mr-admin__badge--alert">' . esc_html($statuses[$stored]['label']) . '</span>'
                : esc_html($statuses[$stored]['label']))
            . noelclark_v1_mail_room_admin_help_html($statuses[$stored]['meaning']);
    }

    noelclark_v1_mail_room_admin_fact('Status', $status_html);
    noelclark_v1_mail_room_admin_fact(
        'Send attempts',
        esc_html(number_format_i18n((int) $row['notify_attempts']))
            . noelclark_v1_mail_room_admin_help_html('Each try to hand the alert to the mail system. 1 is normal; more means earlier tries failed and were retried.')
    );
    noelclark_v1_mail_room_admin_fact('Last attempt', noelclark_v1_mail_room_admin_time_html($row['notify_last_attempt_at']));

    if (!$failed) {
        echo '</dl>';

        return;
    }

    $errors = noelclark_v1_mail_room_admin_notify_errors();
    $code   = (string) $row['notify_last_error_code'];

    if ($code !== '') {
        noelclark_v1_mail_room_admin_fact(
            'Problem',
            isset($errors[$code])
                ? esc_html($errors[$code])
                : noelclark_v1_mail_room_admin_muted_html('Unrecognized problem') . ' ' . noelclark_v1_mail_room_admin_code_html($code)
        );
    }

    $last = is_string($row['notify_last_attempt_at']) ? strtotime($row['notify_last_attempt_at'] . ' UTC') : false;

    if ($last !== false) {
        $next = $last + noelclark_v1_mail_room_notify_backoff_seconds((int) $row['notify_attempts']);

        noelclark_v1_mail_room_admin_fact(
            'Earliest another try',
            esc_html('Not before ') . noelclark_v1_mail_room_admin_time_html(gmdate('Y-m-d H:i:s', $next))
        );
    }

    echo '</dl>';
}

/**
 * Associated questionnaire responses, rendered from their frozen snapshots.
 *
 * @param array<string, mixed> $responses Loader result.
 * @param bool                 $embedded  Skip the outer section when already inside a fold.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_questionnaires($responses, $embedded = false) {
    if (!$embedded) {
        echo '<section class="nc-mr-admin__section">';
        echo '<h2 class="nc-mr-admin__section-title">' . esc_html('Optional questionnaire') . '</h2>';
    }

    if (empty($responses['ok'])) {
        noelclark_v1_mail_room_admin_notice('error', 'Questionnaire responses could not be loaded.');

        if (!$embedded) {
            echo '</section>';
        }

        return;
    }

    if ($responses['rows'] === array()) {
        echo '<p class="nc-mr-admin__empty-line">' . esc_html('No optional questionnaire answers are linked to this letter.') . '</p>';

        if (!$embedded) {
            echo '</section>';
        }

        return;
    }

    foreach ($responses['rows'] as $response) {
        $view  = noelclark_v1_mail_room_admin_questionnaire_view($response);
        $title = ($response['title'] !== null && (string) $response['title'] !== '') ? (string) $response['title'] : 'Questionnaire';
        ?>
        <article class="nc-mr-admin__q">
            <header class="nc-mr-admin__q-head">
                <h3 class="nc-mr-admin__q-title"><?php echo esc_html($title); ?></h3>
                <p class="nc-mr-admin__q-meta">
                    <span>Answered <?php echo noelclark_v1_mail_room_admin_time_html($response['accepted_at']); ?></span>
                </p>
            </header>
            <?php
            if (!$view['answers_ok']) {
                noelclark_v1_mail_room_admin_notice('warning', 'Stored answers could not be read.');
            } elseif ($view['snapshot_ok']) {
                echo '<ol class="nc-mr-admin__q-list">';

                foreach ($view['items'] as $item) {
                    echo '<li class="nc-mr-admin__q-item">';
                    echo '<p class="nc-mr-admin__q-prompt">';
                    echo $item['prompt'] !== ''
                        ? esc_html($item['prompt'])
                        : noelclark_v1_mail_room_admin_muted_html('Question') . ' ' . noelclark_v1_mail_room_admin_code_html($item['question_id']);
                    echo '</p>';
                    echo '<p class="nc-mr-admin__q-answer">';

                    if ($item['state'] === 'answered') {
                        echo esc_html($item['answer_label']);
                    } elseif ($item['state'] === 'unrecognized') {
                        echo noelclark_v1_mail_room_admin_muted_html('Unrecognized response') . ' '
                            . noelclark_v1_mail_room_admin_code_html($item['answer_id']);
                    } else {
                        echo noelclark_v1_mail_room_admin_muted_html('Not answered');
                    }

                    echo '</p></li>';
                }

                echo '</ol>';

                if ($view['extra'] !== array()) {
                    echo '<p class="nc-mr-admin__q-extra-title">' . esc_html('Stored answers not in this version') . '</p>';
                    noelclark_v1_mail_room_admin_render_pairs($view['extra']);
                }
            } else {
                noelclark_v1_mail_room_admin_notice('warning', 'The questions for this version could not be loaded, so stored answer codes are shown instead.');

                if ($view['stored'] === array()) {
                    echo '<p class="nc-mr-admin__empty-line">' . esc_html('No answers stored.') . '</p>';
                } else {
                    noelclark_v1_mail_room_admin_render_pairs($view['stored']);
                }
            }
            ?>
        </article>
        <?php
    }

    if (!$embedded) {
        echo '</section>';
    }
}

/**
 * Escaped key/value list of stored codes.
 *
 * @param array<string, string> $pairs Pairs.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_pairs($pairs) {
    echo '<dl class="nc-mr-admin__pairs">';

    foreach ($pairs as $key => $value) {
        printf(
            '<div class="nc-mr-admin__fact"><dt>%1$s</dt><dd>%2$s</dd></div>',
            noelclark_v1_mail_room_admin_code_html((string) $key),
            $value !== '' ? noelclark_v1_mail_room_admin_code_html((string) $value) : noelclark_v1_mail_room_admin_muted_html('Empty')
        );
    }

    echo '</dl>';
}

/**
 * One stored editorial status as its existing label.
 *
 * @param string $stored Stored status.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_stored_status_html($stored) {
    $statuses = noelclark_v1_mail_room_admin_editorial_statuses();

    if (isset($statuses[$stored])) {
        return esc_html($statuses[$stored]);
    }

    return noelclark_v1_mail_room_admin_code_html($stored);
}

/**
 * Previous and new editorial status for one history row.
 *
 * @param mixed $payload_json Stored payload_json.
 * @return string Safe HTML.
 */
function noelclark_v1_mail_room_admin_status_change_html($payload_json) {
    $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($payload_json);

    if (!is_array($pairs) || !isset($pairs['from'], $pairs['to']) || $pairs['from'] === '' || $pairs['to'] === '') {
        return noelclark_v1_mail_room_admin_muted_html('Status change could not be read.');
    }

    return noelclark_v1_mail_room_admin_stored_status_html($pairs['from'])
        . esc_html(' → ')
        . noelclark_v1_mail_room_admin_stored_status_html($pairs['to']);
}

/**
 * History detail for a request, decision, or operative-permission event.
 *
 * @param string $type         Event type.
 * @param mixed  $payload_json Stored payload.
 * @return string Safe HTML, or empty when this event uses another renderer.
 */
function noelclark_v1_mail_room_admin_request_event_detail_html($type, $payload_json) {
    $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($payload_json);

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED) {
        return esc_html('Withdraw their letter');
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED || $type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED) {
        return noelclark_v1_mail_room_admin_decision_outcome_html($payload_json);
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED) {
        $requested = is_array($pairs) && isset($pairs['requested']) ? $pairs['requested'] : '';

        return $requested !== ''
            ? noelclark_v1_mail_room_admin_treatment_html($requested)
            : noelclark_v1_mail_room_admin_muted_html('Request could not be read.');
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED) {
        $from = is_array($pairs) && isset($pairs['from']) ? $pairs['from'] : '';
        $to   = is_array($pairs) && isset($pairs['to']) ? $pairs['to'] : '';

        if ($from === '' || $to === '') {
            return noelclark_v1_mail_room_admin_muted_html('Permission change could not be read.');
        }

        return noelclark_v1_mail_room_admin_treatment_html($from)
            . esc_html(' → ')
            . noelclark_v1_mail_room_admin_treatment_html($to);
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_CREDIT_CHANGED) {
        $from = is_array($pairs) && isset($pairs['from']) ? (string) $pairs['from'] : '';
        $to   = is_array($pairs) && isset($pairs['to']) ? (string) $pairs['to'] : '';

        if ($to === '') {
            return noelclark_v1_mail_room_admin_muted_html('Credit change could not be read.');
        }

        return esc_html(($from !== '' ? $from : 'None') . ' → ' . $to);
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED) {
        $credit      = is_array($pairs) && isset($pairs['credit']) ? (string) $pairs['credit'] : '';
        $status      = is_array($pairs) && isset($pairs['status']) ? (string) $pairs['status'] : '';
        $dated       = is_array($pairs) && isset($pairs['confirmed_on']) ? (string) $pairs['confirmed_on'] : '';
        $asked       = is_array($pairs) && isset($pairs['asked_on']) ? (string) $pairs['asked_on'] : '';
        $user_id     = is_array($pairs) && isset($pairs['user_id']) ? (string) $pairs['user_id'] : '';
        $evidence    = is_array($pairs) && isset($pairs['evidence']) ? (string) $pairs['evidence'] : '';
        $attestation = is_array($pairs) && isset($pairs['attestation']) ? (string) $pairs['attestation'] : '';

        if ($status !== 'confirmed' || $credit === '') {
            return noelclark_v1_mail_room_admin_muted_html('Credit confirmation could not be read.');
        }

        $line = 'We confirmed ' . $credit . '.';

        if ($asked !== '') {
            $line .= ' They asked ' . $asked . '.';
        }

        if ($dated !== '') {
            $line .= ' Recorded ' . $dated . '.';
        }

        if ($user_id !== '') {
            $line .= ' User ' . $user_id . '.';
        }

        $detail = esc_html($line);

        if ($attestation !== '') {
            $detail .= '<br>' . esc_html($attestation);
        }

        if ($evidence !== '') {
            $detail .= '<br>' . esc_html($evidence);
        }

        return $detail;
    }

    if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED || $type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS) {
        $types = noelclark_v1_mail_room_admin_author_types();
        $request_type = is_array($pairs) && isset($pairs['request_type']) ? (string) $pairs['request_type'] : '';
        $label = isset($types[$request_type]) ? $types[$request_type] : 'Request';
        $want = is_array($pairs) && isset($pairs['want']) ? (string) $pairs['want'] : '';
        $progress = is_array($pairs) && isset($pairs['progress']) ? (string) $pairs['progress'] : '';
        $progress_labels = noelclark_v1_mail_room_admin_author_progress_labels();

        if ($progress !== '' && isset($progress_labels[$progress])) {
            return esc_html($label . ' · ' . $progress_labels[$progress]);
        }

        return esc_html($want !== '' ? $label . ': ' . $want : $label);
    }

    return '';
}

/**
 * Append-only activity history, oldest first.
 *
 * @param array<string, mixed> $events   Loader result.
 * @param bool                 $embedded Skip the outer section when already inside a fold.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_events($events, $embedded = false) {
    if (!$embedded) {
        echo '<section class="nc-mr-admin__section">';
        echo '<h2 class="nc-mr-admin__section-title">' . esc_html('History') . '</h2>';
    }

    if (empty($events['ok'])) {
        noelclark_v1_mail_room_admin_notice('error', 'History could not be loaded.');

        if (!$embedded) {
            echo '</section>';
        }

        return;
    }

    if ($events['rows'] === array()) {
        echo '<p class="nc-mr-admin__empty-line">' . esc_html('No activity recorded.') . '</p>';

        if (!$embedded) {
            echo '</section>';
        }

        return;
    }

    $labels = noelclark_v1_mail_room_admin_event_labels();

    echo '<ol class="nc-mr-admin__events">';

    foreach ($events['rows'] as $event) {
        $type  = (string) $event['event_type'];
        $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($event['payload_json']);

        echo '<li class="nc-mr-admin__event">';
        echo '<span class="nc-mr-admin__event-time">' . noelclark_v1_mail_room_admin_time_html($event['created_at']) . '</span>';
        echo '<span class="nc-mr-admin__event-label">';
        echo isset($labels[$type])
            ? esc_html($labels[$type])
            : noelclark_v1_mail_room_admin_muted_html('Other recorded activity') . ' ' . noelclark_v1_mail_room_admin_code_html($type);
        echo '</span>';

        $request_detail = noelclark_v1_mail_room_admin_request_event_detail_html($type, $event['payload_json']);

        if ($request_detail !== '') {
            echo '<span class="nc-mr-admin__event-detail">' . $request_detail . '</span>';
        } elseif ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_EDITORIAL_STATUS_CHANGED) {
            echo '<span class="nc-mr-admin__event-detail">'
                . noelclark_v1_mail_room_admin_status_change_html($event['payload_json'])
                . '</span>';
        } elseif (is_array($pairs) && $pairs !== array()) {
            echo '<span class="nc-mr-admin__event-detail">';

            $parts = array();

            foreach ($pairs as $key => $value) {
                if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_ACCEPTED && $key === 'sharing_choice') {
                    $parts[] = esc_html('Sender chose') . ' '
                        . noelclark_v1_mail_room_admin_treatment_html($value);
                } else {
                    $parts[] = noelclark_v1_mail_room_admin_code_html($key) . ' '
                        . ($value !== '' ? esc_html($value) : noelclark_v1_mail_room_admin_muted_html('Empty'));
                }
            }

            echo implode('<br>', $parts);
            echo '</span>';
        }

        echo '</li>';
    }

    echo '</ol>';

    if (!$embedded) {
        echo '</section>';
    }
}

/**
 * Technical record rows. The caller supplies the surrounding list.
 *
 * @param array<string, mixed> $row       Submission row.
 * @param array<string, mixed> $responses Questionnaire loader result.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_technical_facts($row, $responses) {
    $state_missing = ($row['editorial_status'] === null);

    noelclark_v1_mail_room_admin_fact('Reference ID', noelclark_v1_mail_room_admin_code_html((string) $row['mr_ref']));
    noelclark_v1_mail_room_admin_fact(
        'Participant ID',
        $row['participant_ref'] === null
            ? noelclark_v1_mail_room_admin_muted_html('Contact record missing')
            : noelclark_v1_mail_room_admin_code_html((string) $row['participant_ref'])
    );
    noelclark_v1_mail_room_admin_fact(
        'Terms version',
        ($row['legal_document_version'] !== null && (string) $row['legal_document_version'] !== '')
            ? noelclark_v1_mail_room_admin_code_html((string) $row['legal_document_version'])
            : noelclark_v1_mail_room_admin_muted_html('Not recorded')
    );
    noelclark_v1_mail_room_admin_fact(
        'Terms text on file',
        ((int) $row['legal_on_record'])
            ? esc_html('Yes')
            : noelclark_v1_mail_room_admin_muted_html('Not found')
    );
    noelclark_v1_mail_room_admin_fact(
        'Status record updated',
        $state_missing
            ? noelclark_v1_mail_room_admin_muted_html('Status record missing')
            : noelclark_v1_mail_room_admin_time_html($row['state_updated_at'])
    );
    noelclark_v1_mail_room_admin_fact(
        'Withdrawal request',
        $state_missing
            ? noelclark_v1_mail_room_admin_muted_html('Status record missing')
            : noelclark_v1_mail_room_admin_withdrawal_html($row['withdrawal_request_status'])
    );

    if (!empty($responses['ok'])) {
        foreach ($responses['rows'] as $response) {
            noelclark_v1_mail_room_admin_fact(
                'Questionnaire ID',
                noelclark_v1_mail_room_admin_code_html((string) $response['q_ref'])
                    . noelclark_v1_mail_room_admin_help_html(
                        'Version ' . (($response['version_label'] !== null && (string) $response['version_label'] !== '') ? (string) $response['version_label'] : 'not found')
                    )
            );
        }
    }
}
