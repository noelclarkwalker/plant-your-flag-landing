<?php
/**
 * Mail Room private admin read layer — Admin Slice 1.
 *
 * Read-only. SELECT statements only. No hooks, no REST, no AJAX, no writes.
 * Every loader requires NOELCLARK_V1_MAIL_ROOM_CAPABILITY and returns nothing
 * without it. Internal AUTO_INCREMENT ids never leave SQL: detail loaders are
 * keyed by mr_ref.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Inbox rows per page. */
const NOELCLARK_V1_MAIL_ROOM_ADMIN_PER_PAGE = 20;

/** @var int Inbox excerpt length in characters. */
const NOELCLARK_V1_MAIL_ROOM_ADMIN_EXCERPT_CHARS = 180;

/** @var int Characters of letter body fetched for inbox excerpts. */
const NOELCLARK_V1_MAIL_ROOM_ADMIN_EXCERPT_FETCH = 400;

/**
 * Whether the current user may read the private Mail Room.
 *
 * @return bool
 */
function noelclark_v1_mail_room_admin_can_read() {
    return current_user_can(NOELCLARK_V1_MAIL_ROOM_CAPABILITY);
}

/**
 * Privacy-safe operational log. No letter, refs, or writer PII.
 *
 * @param string $class Stable class.
 * @return void
 */
function noelclark_v1_mail_room_admin_log($class) {
    error_log('mail_room_admin ' . $class);
}

/**
 * Canonical editorial statuses: stored value => interface label.
 *
 * Stored values are unchanged; only the labels are presentational.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_editorial_statuses() {
    return array(
        NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW => 'Unreviewed',
        'reviewed'                                  => 'Reviewed',
        'selected'                                  => 'Selected',
        'not_selected'                              => 'Not selected',
        'published'                                 => 'Published',
    );
}

/**
 * Editorial status meanings (Mail Room Backend Product Authority §15).
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_editorial_status_meanings() {
    return array(
        NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW => 'Received but not yet reviewed.',
        'reviewed'                                  => 'Reviewed and under editorial consideration.',
        'selected'                                  => 'Chosen for potential Mail Room or publication work. Selected does not mean published.',
        'not_selected'                              => 'Not selected at this time. This is not a permanent rejection.',
        'published'                                 => 'A separate public Mail Room piece has actually been published from this letter.',
    );
}

/**
 * Statuses worth offering as views and filters.
 *
 * Unreviewed is always offered. Other statuses appear only once a letter
 * holds them (or when already selected), so unbuilt workflow steps such as
 * Published are not advertised.
 *
 * @param array<string, int> $counts  Letter counts by stored status.
 * @param string             $current Currently filtered status.
 * @return array<string, string> Stored value => label.
 */
function noelclark_v1_mail_room_admin_visible_statuses($counts, $current = '') {
    $visible = array();

    foreach (noelclark_v1_mail_room_admin_editorial_statuses() as $stored => $label) {
        if (
            $stored === NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW
            || $stored === $current
            || (isset($counts[$stored]) && (int) $counts[$stored] > 0)
        ) {
            $visible[$stored] = $label;
        }
    }

    return $visible;
}

/**
 * Sharing choices: stored value => sender-facing label + meaning under the
 * approved Mail Room Submission Terms.
 *
 * @return array<string, array{label: string, meaning: string}>
 */
function noelclark_v1_mail_room_admin_treatments() {
    $labels = noelclark_v1_mail_room_sharing_labels();

    return array(
        'not_public'   => array(
            'label'   => $labels['not_public'],
            'meaning' => 'Not authorized for public sharing. The letter may be received and reviewed, but its words will not be published as a Mail Room letter (Terms §3, §5). Independent original work is a separate matter and never authorizes publishing its words (§8).',
        ),
        'anonymous'    => array(
            'label'   => $labels['anonymous'],
            'meaning' => 'May be considered for public sharing without publicly identifying the sender. If selected, some or all of the letter may appear without the sender’s name or other identifying information (Terms §3). Permission does not require publication (§4).',
        ),
        'named_credit' => array(
            'label'   => $labels['named_credit'],
            'meaning' => 'May be considered for public sharing with the public credit the sender provided (Terms §3). Only that credit may be used, never the private name or email (§3, §12). Permission does not require publication (§4).',
        ),
    );
}

/**
 * Known withdrawal-request stored values => interface label.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_withdrawal_statuses() {
    return array(
        NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_NONE    => 'None',
        NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING => 'Pending',
        NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_HANDLED => 'Handled',
    );
}

/**
 * Alert job stored status => label + plain-English explanation.
 *
 * @return array<string, array{label: string, meaning: string}>
 */
function noelclark_v1_mail_room_admin_notify_statuses() {
    return array(
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING    => array(
            'label'   => 'Waiting to send',
            'meaning' => 'The alert is queued and will be sent by the site’s next background check.',
        ),
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING => array(
            'label'   => 'Sending',
            'meaning' => 'A send attempt is in progress. If it stays unfinished for 10 minutes, it can be tried again by the site’s background check.',
        ),
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_SENT       => array(
            'label'   => 'Handed to the mail system',
            'meaning' => 'WordPress passed the alert to the mail system. This does not confirm it reached the inbox.',
        ),
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED     => array(
            'label'   => 'Last try failed',
            'meaning' => 'The notification can be tried again. The site’s background check looks for these after a wait of 1 minute, then 2, 4, 8, 16, and 32 minutes, then about once an hour. This letter does not have its own scheduled send time.',
        ),
    );
}

/**
 * Alert error codes => plain-English explanation.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_notify_errors() {
    return array(
        'mail_handoff_error'     => 'The mail system did not accept the message.',
        'recipient_unavailable'  => 'No valid alert address is configured.',
        'submission_unavailable' => 'The letter record could not be read when sending.',
    );
}

/**
 * Known event types => interface label.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_event_labels() {
    return array(
        NOELCLARK_V1_MAIL_ROOM_EVENT_ACCEPTED                      => 'Letter received',
        NOELCLARK_V1_MAIL_ROOM_EVENT_EDITORIAL_STATUS_CHANGED    => 'Editorial status changed',
        NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED        => 'Asked to withdraw their letter',
        NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED          => 'Decision on withdrawing their letter',
        NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED => 'Asked to change sharing permission',
        NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED   => 'Decision on changing sharing permission',
        NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED => 'Current sharing permission changed',
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED       => 'Request logged',
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS     => 'Request progress recorded',
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED     => 'Public credit confirmation recorded',
        NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_CREDIT_CHANGED    => 'Current public credit changed',
    );
}

/**
 * Normalize a pasted reference ID. Tolerates case and whitespace only.
 *
 * @param mixed $raw Caller input.
 * @return string|null Lowercase hex ref, or null when not a valid ref shape.
 */
function noelclark_v1_mail_room_admin_normalize_ref($raw) {
    if (!is_string($raw)) {
        return null;
    }

    $ref = strtolower((string) preg_replace('/\s+/', '', $raw));
    $len = (int) NOELCLARK_V1_MAIL_ROOM_REF_LENGTH;

    if (!preg_match('/^[0-9a-f]{' . $len . '}$/', $ref)) {
        return null;
    }

    return $ref;
}

/**
 * Prepare SQL only when it carries placeholders.
 *
 * @param string  $sql  SQL with trusted table names and %-placeholders.
 * @param mixed[] $args Placeholder values.
 * @return string
 */
function noelclark_v1_mail_room_admin_prepare($sql, $args) {
    global $wpdb;

    if (empty($args)) {
        return $sql;
    }

    return $wpdb->prepare($sql, $args);
}

/**
 * WHERE fragment + args for validated inbox filters.
 *
 * Filters must already be allowlisted by the caller.
 *
 * @param array{status: string, share: string, ref: string} $filters Validated filters.
 * @return array{0: string, 1: mixed[]}
 */
function noelclark_v1_mail_room_admin_inbox_where($filters) {
    $where = '';
    $args  = array();

    if ($filters['status'] !== '') {
        $where .= ' AND st.editorial_status = %s';
        $args[] = $filters['status'];
    }

    if ($filters['ref'] !== '') {
        $where .= ' AND s.mr_ref = %s';
        $args[] = $filters['ref'];
    }

    return array($where, $args);
}

/**
 * One page of inbox rows, newest accepted first.
 *
 * Pass $unpaged when the caller must apply the derived share answer. That
 * answer is computed in PHP from the same rules as the letter page. The
 * credit columns are included so each row can show its condition.
 *
 * @param array{status: string, share: string, ref: string} $filters Validated filters.
 * @param int  $page    Requested 1-based page. Ignored when $unpaged is true.
 * @param bool $unpaged Fetch every matching row, with no LIMIT.
 * @return array{ok: true, rows: array<int, array<string, mixed>>, total: int, page: int, total_pages: int}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_query_inbox($filters, $page, $unpaged = false) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');
    $jobs        = noelclark_v1_mail_room_table('notification_jobs');
    $responses   = noelclark_v1_mail_room_table('q_responses');

    list($where, $args) = noelclark_v1_mail_room_admin_inbox_where($filters);

    $total = $wpdb->get_var(
        noelclark_v1_mail_room_admin_prepare(
            "SELECT COUNT(*) FROM {$submissions} s "
            . "LEFT JOIN {$state} st ON st.mr_id = s.id "
            . "WHERE 1=1{$where}",
            $args
        )
    );

    if ($total === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('inbox_count_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $total       = (int) $total;
    $per_page    = (int) NOELCLARK_V1_MAIL_ROOM_ADMIN_PER_PAGE;
    $total_pages = max(1, (int) ceil($total / $per_page));
    $page        = min(max(1, (int) $page), $total_pages);
    $offset      = ($page - 1) * $per_page;

    $list_args = array_merge(array((int) NOELCLARK_V1_MAIL_ROOM_ADMIN_EXCERPT_FETCH), $args);
    $limit_sql = '';

    if (!$unpaged) {
        $list_args[] = $per_page;
        $list_args[] = $offset;
        $limit_sql   = ' LIMIT %d OFFSET %d';
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.mr_ref, s.accepted_at, s.sharing_choice, s.public_credit, LEFT(s.letter_body, %d) AS excerpt, "
            . 'st.editorial_status, st.operative_treatment, st.operative_public_credit, st.withdrawal_request_status, '
            . 'j.status AS notify_status, '
            . "(SELECT COUNT(*) FROM {$responses} q WHERE q.associated_mr_id = s.id) AS q_count "
            . "FROM {$submissions} s "
            . "LEFT JOIN {$state} st ON st.mr_id = s.id "
            . "LEFT JOIN {$jobs} j ON j.mr_id = s.id "
            . "WHERE 1=1{$where} "
            . 'ORDER BY s.accepted_at DESC, s.id DESC'
            . $limit_sql,
            $list_args
        ),
        ARRAY_A
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('inbox_list_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'          => true,
        'rows'        => $rows,
        'total'       => $total,
        'page'        => $page,
        'total_pages' => $total_pages,
    );
}

/**
 * Letter counts per stored editorial status, plus the overall total.
 *
 * @return array{ok: true, all: int, counts: array<string, int>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_status_counts() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $state       = noelclark_v1_mail_room_table('mr_state');

    $all = $wpdb->get_var("SELECT COUNT(*) FROM {$submissions}");

    if ($all === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('status_count_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $rows = $wpdb->get_results(
        "SELECT st.editorial_status, COUNT(*) AS total FROM {$state} st "
        . "INNER JOIN {$submissions} s ON s.id = st.mr_id "
        . 'GROUP BY st.editorial_status',
        ARRAY_A
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('status_count_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $counts = array();

    foreach ($rows as $row) {
        $counts[(string) $row['editorial_status']] = (int) $row['total'];
    }

    return array(
        'ok'     => true,
        'all'    => (int) $all,
        'counts' => $counts,
    );
}

/**
 * Full private record for one letter, keyed by mr_ref.
 *
 * @param string $mr_ref Normalized ref.
 * @return array{ok: true, row: array<string, mixed>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_load_submission($mr_ref) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $submissions  = noelclark_v1_mail_room_table('mr_submissions');
    $participants = noelclark_v1_mail_room_table('participants');
    $state        = noelclark_v1_mail_room_table('mr_state');
    $jobs         = noelclark_v1_mail_room_table('notification_jobs');
    $legal        = noelclark_v1_mail_room_table('legal_documents');

    $row = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT s.mr_ref, s.letter_body, s.sharing_choice, s.public_credit, s.age_attested, '
            . 's.legal_document_version, s.terms_accepted_at, s.accepted_at, '
            . 's.recipient_original, s.recipient_display_choice, s.recipient_public_wording, '
            . 'ld.id IS NOT NULL AS legal_on_record, '
            . 'p.participant_ref, p.display_name, p.email, '
            . 'st.editorial_status, st.operative_treatment, st.operative_public_credit, '
            . 'st.operative_recipient_display, st.operative_recipient_wording, st.protective_hold, '
            . 'st.withdrawal_request_status, st.private_note, st.updated_at AS state_updated_at, '
            . 'j.status AS notify_status, j.attempts AS notify_attempts, '
            . 'j.last_attempt_at AS notify_last_attempt_at, j.last_error_code AS notify_last_error_code '
            . "FROM {$submissions} s "
            . "LEFT JOIN {$participants} p ON p.id = s.accepted_participant_id "
            . "LEFT JOIN {$state} st ON st.mr_id = s.id "
            . "LEFT JOIN {$jobs} j ON j.mr_id = s.id "
            . "LEFT JOIN {$legal} ld ON ld.id = s.legal_document_id "
            . 'WHERE s.mr_ref = %s LIMIT 1',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($row === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('detail_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (!is_array($row) || (string) $row['mr_ref'] !== $mr_ref) {
        return array(
            'ok'   => false,
            'code' => 'not_found',
        );
    }

    return array(
        'ok'  => true,
        'row' => $row,
    );
}

/**
 * Questionnaire responses associated with one letter, with their frozen version snapshot.
 *
 * @param string $mr_ref Normalized ref.
 * @return array{ok: true, rows: array<int, array<string, mixed>>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_load_questionnaire_responses($mr_ref) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $responses      = noelclark_v1_mail_room_table('q_responses');
    $submissions    = noelclark_v1_mail_room_table('mr_submissions');
    $versions       = noelclark_v1_mail_room_table('questionnaire_versions');
    $questionnaires = noelclark_v1_mail_room_table('questionnaires');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT q.q_ref, q.answers_json, q.accepted_at, q.legal_document_version, '
            . 'v.version_label, v.questions_json, qn.title '
            . "FROM {$responses} q "
            . "INNER JOIN {$submissions} s ON s.id = q.associated_mr_id "
            . "LEFT JOIN {$versions} v ON v.id = q.questionnaire_version_id "
            . "LEFT JOIN {$questionnaires} qn ON qn.id = v.questionnaire_id "
            . 'WHERE s.mr_ref = %s '
            . 'ORDER BY q.accepted_at ASC, q.id ASC',
            $mr_ref
        ),
        ARRAY_A
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('questionnaire_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'   => true,
        'rows' => $rows,
    );
}

/**
 * Append-only event history for one letter, oldest first.
 *
 * @param string $mr_ref Normalized ref.
 * @return array{ok: true, rows: array<int, array<string, mixed>>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_load_events($mr_ref) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $events      = noelclark_v1_mail_room_table('mr_events');
    $submissions = noelclark_v1_mail_room_table('mr_submissions');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT e.event_type, e.payload_json, e.created_at '
            . "FROM {$events} e "
            . "INNER JOIN {$submissions} s ON s.id = e.mr_id "
            . 'WHERE s.mr_ref = %s '
            . 'ORDER BY e.created_at ASC, e.id ASC',
            $mr_ref
        ),
        ARRAY_A
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('events_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'   => true,
        'rows' => $rows,
    );
}

/**
 * Permission request and decision events for these letters, oldest first.
 *
 * @param string[] $mr_refs Normalized references.
 * @return array{ok: true, by_ref: array<string, array<int, array<string, mixed>>>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_load_permission_events($mr_refs) {
    global $wpdb;

    $by_ref = array();

    if (!is_array($mr_refs) || $mr_refs === array()) {
        return array(
            'ok'     => true,
            'by_ref' => $by_ref,
        );
    }

    $refs = array();

    foreach ($mr_refs as $mr_ref) {
        if (is_string($mr_ref) && $mr_ref !== '') {
            $refs[] = $mr_ref;
        }
    }

    if ($refs === array()) {
        return array(
            'ok'     => true,
            'by_ref' => $by_ref,
        );
    }

    $events      = noelclark_v1_mail_room_table('mr_events');
    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $placeholders = implode(', ', array_fill(0, count($refs), '%s'));
    $sql          = 'SELECT s.mr_ref, e.event_type, e.payload_json '
        . "FROM {$events} e INNER JOIN {$submissions} s ON s.id = e.mr_id "
        . "WHERE s.mr_ref IN ({$placeholders}) AND e.event_type IN (%s, %s) "
        . 'ORDER BY e.created_at ASC, e.id ASC';
    $args         = array_merge(
        $refs,
        array(
            NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED,
        )
    );
    $prepared = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args));
    $rows     = $wpdb->get_results($prepared, ARRAY_A);

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('events_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    foreach ($rows as $event_row) {
        if (!is_array($event_row) || !isset($event_row['mr_ref'])) {
            continue;
        }

        $ref = (string) $event_row['mr_ref'];

        if (!isset($by_ref[$ref])) {
            $by_ref[$ref] = array();
        }

        $by_ref[$ref][] = $event_row;
    }

    return array(
        'ok'     => true,
        'by_ref' => $by_ref,
    );
}

/**
 * Author-request events for the letters on this inbox page.
 *
 * @param string[] $mr_refs Normalized references.
 * @return array{ok: true, by_ref: array<string, array<int, array<string, mixed>>>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_load_author_request_events($mr_refs) {
    global $wpdb;

    $by_ref = array();

    if (!is_array($mr_refs) || $mr_refs === array()) {
        return array(
            'ok'     => true,
            'by_ref' => $by_ref,
        );
    }

    $refs = array();

    foreach ($mr_refs as $mr_ref) {
        if (is_string($mr_ref) && $mr_ref !== '') {
            $refs[] = $mr_ref;
        }
    }

    if ($refs === array()) {
        return array(
            'ok'     => true,
            'by_ref' => $by_ref,
        );
    }

    $events       = noelclark_v1_mail_room_table('mr_events');
    $submissions  = noelclark_v1_mail_room_table('mr_submissions');
    $placeholders = implode(', ', array_fill(0, count($refs), '%s'));
    $sql          = 'SELECT s.mr_ref, e.event_type, e.payload_json, e.created_at '
        . "FROM {$events} e INNER JOIN {$submissions} s ON s.id = e.mr_id "
        . "WHERE s.mr_ref IN ({$placeholders}) AND e.event_type IN (%s, %s, %s, %s, %s) "
        . 'ORDER BY e.created_at ASC, e.id ASC';
    $args         = array_merge(
        $refs,
        array(
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED,
        )
    );
    $prepared = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args));
    $rows     = $wpdb->get_results($prepared, ARRAY_A);

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('events_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    foreach ($rows as $event_row) {
        if (!is_array($event_row) || !isset($event_row['mr_ref'])) {
            continue;
        }

        $ref = (string) $event_row['mr_ref'];

        if (!isset($by_ref[$ref])) {
            $by_ref[$ref] = array();
        }

        $by_ref[$ref][] = $event_row;
    }

    return array(
        'ok'     => true,
        'by_ref' => $by_ref,
    );
}

/**
 * Latest withdrawal request and latest permission-change request, each with
 * the decision that follows it.
 *
 * Events must already be oldest first. A new request of one kind starts a
 * new pair and leaves the earlier decision in the history list. A decision
 * attaches only to the open request of the same kind. A second open request
 * of that kind is refused by the writer, so the pair stays unambiguous.
 *
 * @param array<int, array<string, mixed>> $rows Event rows.
 * @return array{withdrawal: array{request: array<string, mixed>|null, decision: array<string, mixed>|null}, permission: array{request: array<string, mixed>|null, decision: array<string, mixed>|null}}
 */
function noelclark_v1_mail_room_admin_request_record($rows) {
    $record = array(
        'withdrawal' => array(
            'request'  => null,
            'decision' => null,
        ),
        'permission' => array(
            'request'  => null,
            'decision' => null,
        ),
    );

    foreach ($rows as $event) {
        if (!is_array($event) || !isset($event['event_type'])) {
            continue;
        }

        $type = (string) $event['event_type'];

        if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED) {
            $record['withdrawal'] = array(
                'request'  => $event,
                'decision' => null,
            );
            continue;
        }

        if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED && $record['withdrawal']['request'] !== null && $record['withdrawal']['decision'] === null) {
            $record['withdrawal']['decision'] = $event;
            continue;
        }

        if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED) {
            $record['permission'] = array(
                'request'  => $event,
                'decision' => null,
            );
            continue;
        }

        if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED && $record['permission']['request'] !== null && $record['permission']['decision'] === null) {
            $record['permission']['decision'] = $event;
        }
    }

    return $record;
}

/**
 * Resolve stored answers against their frozen questions_json snapshot.
 *
 * Snapshot shape: JSON list of {id, prompt, responses: [{id, label}, ...]}.
 *
 * @param array<string, mixed> $row Questionnaire response row.
 * @return array{
 *   answers_ok: bool,
 *   snapshot_ok: bool,
 *   items: array<int, array{question_id: string, prompt: string, state: string, answer_id: string, answer_label: string}>,
 *   extra: array<string, string>,
 *   stored: array<string, string>
 * }
 */
function noelclark_v1_mail_room_admin_questionnaire_view($row) {
    $view = array(
        'answers_ok'  => false,
        'snapshot_ok' => false,
        'items'       => array(),
        'extra'       => array(),
        'stored'      => array(),
    );

    $answers = json_decode(isset($row['answers_json']) ? (string) $row['answers_json'] : '', true);

    if (!is_array($answers)) {
        return $view;
    }

    $view['answers_ok'] = true;

    foreach ($answers as $question_id => $value) {
        $view['stored'][(string) $question_id] = is_scalar($value) ? (string) $value : '';
    }

    $questions = json_decode(isset($row['questions_json']) ? (string) $row['questions_json'] : '', true);

    if (
        !is_array($questions)
        || $questions === array()
        || array_keys($questions) !== range(0, count($questions) - 1)
    ) {
        return $view;
    }

    $remaining = $view['stored'];

    foreach ($questions as $question) {
        if (!is_array($question) || !isset($question['id']) || !is_string($question['id']) || $question['id'] === '') {
            continue;
        }

        $question_id = $question['id'];
        $labels      = array();

        if (isset($question['responses']) && is_array($question['responses'])) {
            foreach ($question['responses'] as $response) {
                if (
                    is_array($response)
                    && isset($response['id'], $response['label'])
                    && is_string($response['id'])
                    && is_string($response['label'])
                ) {
                    $labels[$response['id']] = $response['label'];
                }
            }
        }

        $item = array(
            'question_id'  => $question_id,
            'prompt'       => (isset($question['prompt']) && is_string($question['prompt'])) ? $question['prompt'] : '',
            'state'        => 'unanswered',
            'answer_id'    => '',
            'answer_label' => '',
        );

        if (isset($remaining[$question_id]) && $remaining[$question_id] !== '') {
            $item['answer_id'] = $remaining[$question_id];

            if (isset($labels[$item['answer_id']])) {
                $item['state']        = 'answered';
                $item['answer_label'] = $labels[$item['answer_id']];
            } else {
                $item['state'] = 'unrecognized';
            }
        }

        unset($remaining[$question_id]);
        $view['items'][] = $item;
    }

    if ($view['items'] === array()) {
        return $view;
    }

    $view['snapshot_ok'] = true;
    $view['extra']       = $remaining;

    return $view;
}

/**
 * Scalar key/value pairs from an event payload for display.
 *
 * @param mixed $payload_json Stored payload_json.
 * @return array<string, string>|null Null when the payload is absent or unreadable.
 */
function noelclark_v1_mail_room_admin_event_payload_pairs($payload_json) {
    if (!is_string($payload_json) || $payload_json === '') {
        return null;
    }

    $decoded = json_decode($payload_json, true);

    if (!is_array($decoded)) {
        return null;
    }

    $pairs = array();

    foreach ($decoded as $key => $value) {
        if (is_bool($value)) {
            $pairs[(string) $key] = $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            $pairs[(string) $key] = (string) $value;
        } elseif ($value === null) {
            $pairs[(string) $key] = '';
        } else {
            $pairs[(string) $key] = '[structured value]';
        }
    }

    return $pairs;
}
