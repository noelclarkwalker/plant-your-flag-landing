<?php
/**
 * Mail Room public-version controls on the private letter page.
 *
 * Draft, approval, and unpublish write nc_mr_publications and append history.
 * They do not write the original submission. The preview is this admin screen.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A real calendar date, or an empty string. Invalid input is null.
 *
 * @param string $raw Posted date.
 * @return string|null
 */
function noelclark_v1_mail_room_admin_publication_date($raw) {
    $raw = trim((string) $raw);

    if ($raw === '') {
        return '';
    }

    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $parts)) {
        return null;
    }

    if (!checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
        return null;
    }

    return $raw;
}

/**
 * Publication row for one letter, plus the original text for the copy action.
 *
 * @param string $mr_ref Normalized reference.
 * @return array{ok: true, mr_id: int, letter_body: string, state: array<string, mixed>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_publication_load($mr_ref) {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        return array(
            'ok'   => false,
            'code' => 'forbidden',
        );
    }

    $submissions  = noelclark_v1_mail_room_table('mr_submissions');
    $publications = noelclark_v1_mail_room_table('mr_publications');
    $row          = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT s.id AS mr_id, s.letter_body, p.public_slug, p.draft_title, p.draft_body, '
            . 'p.draft_published_on, p.live_title, p.live_body, p.live_published_on, '
            . 'p.live_approval_mode, p.visibility, p.audience, p.unpublished_reason, '
            . 'p.permission_hold_reason, p.approved_at '
            . "FROM {$submissions} s "
            . "LEFT JOIN {$publications} p ON p.mr_id = s.id "
            . 'WHERE s.mr_ref = %s LIMIT 1',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($row === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('publication_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if (!is_array($row) || !isset($row['mr_id'])) {
        return array(
            'ok'   => false,
            'code' => 'not_found',
        );
    }

    $slug = isset($row['public_slug']) && $row['public_slug'] !== null
        ? (string) $row['public_slug']
        : '';
    $state = noelclark_v1_mail_room_publication_blank($slug);

    if ($slug !== '') {
        $fields = array(
            'draft_title',
            'draft_body',
            'draft_published_on',
            'live_title',
            'live_body',
            'live_published_on',
            'live_approval_mode',
            'visibility',
            'audience',
            'unpublished_reason',
            'permission_hold_reason',
        );

        foreach ($fields as $field) {
            if (isset($row[$field]) && $row[$field] !== null) {
                $state[$field] = (string) $row[$field];
            }
        }
    }

    return array(
        'ok'          => true,
        'mr_id'       => (int) $row['mr_id'],
        'letter_body' => isset($row['letter_body']) ? (string) $row['letter_body'] : '',
        'state'       => $state,
    );
}

/**
 * Current letter view, including the same permission attachments as the letter page.
 *
 * @param string $mr_ref Normalized reference.
 * @return array{ok: true, view: array<string, mixed>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_publication_current_view($mr_ref) {
    $loaded = noelclark_v1_mail_room_admin_load_submission($mr_ref);

    if (empty($loaded['ok']) || !isset($loaded['row']) || !is_array($loaded['row'])) {
        return array(
            'ok'   => false,
            'code' => isset($loaded['code']) ? (string) $loaded['code'] : 'storage',
        );
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

    if (empty($responses['ok'])) {
        $responses = array(
            'ok'   => true,
            'rows' => array(),
        );
    }

    return array(
        'ok'   => true,
        'view' => noelclark_v1_mail_room_admin_letter_view($row, $responses),
    );
}

/**
 * @param string $date Validated date or empty.
 * @return string NULL or a prepare placeholder.
 */
function noelclark_v1_mail_room_admin_publication_date_sql($date) {
    return $date === '' ? 'NULL' : '%s';
}

/**
 * Insert the first publication row. The identifier is generated once.
 *
 * @param int                  $mr_id Internal letter id.
 * @param array<string, mixed> $state Publication state. Its slug is replaced.
 * @param string               $now   GMT timestamp.
 * @return array{ok: true, state: array<string, mixed>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_publication_insert($mr_id, $state, $now) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_publications');
    $draft_date = noelclark_v1_mail_room_publication_text($state['draft_published_on']);
    $live_date  = noelclark_v1_mail_room_publication_text($state['live_published_on']);

    for ($attempt = 0; $attempt < NOELCLARK_V1_MAIL_ROOM_REF_INSERT_ATTEMPTS; $attempt++) {
        $slug = noelclark_v1_mail_room_publication_new_slug('');

        if ($slug === '') {
            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }

        $date_sql = array();
        $values   = array(
            (int) $mr_id,
            $slug,
            (string) $state['draft_title'],
            (string) $state['draft_body'],
        );

        if ($draft_date === '') {
            $date_sql[] = 'NULL';
        } else {
            $date_sql[] = '%s';
            $values[]   = $draft_date;
        }

        $values[] = (string) $state['live_title'];
        $values[] = (string) $state['live_body'];

        if ($live_date === '') {
            $date_sql[] = 'NULL';
        } else {
            $date_sql[] = '%s';
            $values[]   = $live_date;
        }

        $values[] = (string) $state['live_approval_mode'];
        $values[] = (string) $state['visibility'];
        $values[] = 'everyone';
        $values[] = (string) $state['unpublished_reason'];
        $values[] = (string) $state['permission_hold_reason'];
        $values[] = $now;
        $wpdb->last_error = '';
        $inserted = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (mr_id, public_slug, draft_title, draft_body, draft_published_on, "
                . 'live_title, live_body, live_published_on, live_approval_mode, visibility, audience, '
                . 'unpublished_reason, permission_hold_reason, updated_at) VALUES ('
                . '%d, %s, %s, %s, ' . $date_sql[0] . ', %s, %s, ' . $date_sql[1]
                . ', %s, %s, %s, %s, %s, %s)',
                ...$values
            )
        );

        if ($inserted !== false) {
            $state['public_slug'] = $slug;
            $state['audience']    = 'everyone';

            return array(
                'ok'    => true,
                'state' => $state,
            );
        }

        if (!noelclark_v1_mail_room_is_duplicate_key_error((string) $wpdb->last_error)) {
            noelclark_v1_mail_room_admin_log('publication_write_error');

            return array(
                'ok'   => false,
                'code' => 'storage',
            );
        }
    }

    noelclark_v1_mail_room_admin_log('publication_write_error');

    return array(
        'ok'   => false,
        'code' => 'storage',
    );
}

/**
 * Save draft columns only.
 *
 * @param int                  $mr_id Internal letter id.
 * @param array<string, mixed> $state Publication state with a slug.
 * @param string               $now   GMT timestamp.
 * @return bool
 */
function noelclark_v1_mail_room_admin_publication_update_draft($mr_id, $state, $now) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_publications');
    $date  = noelclark_v1_mail_room_publication_text($state['draft_published_on']);
    $sql   = "UPDATE {$table} SET draft_title = %s, draft_body = %s, draft_published_on = "
        . noelclark_v1_mail_room_admin_publication_date_sql($date)
        . ', audience = %s, updated_at = %s WHERE mr_id = %d';
    $values = array(
        (string) $state['draft_title'],
        (string) $state['draft_body'],
    );

    if ($date !== '') {
        $values[] = $date;
    }

    $values[] = 'everyone';
    $values[] = $now;
    $values[] = (int) $mr_id;
    $updated  = $wpdb->query($wpdb->prepare($sql, ...$values));

    return $updated !== false && $wpdb->last_error === '';
}

/**
 * Store the approved copy. Draft columns match that approved copy.
 *
 * @param int                  $mr_id Internal letter id.
 * @param array<string, mixed> $state Approved state.
 * @param string               $now   GMT timestamp.
 * @return bool
 */
function noelclark_v1_mail_room_admin_publication_update_approval($mr_id, $state, $now) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_publications');
    $date  = noelclark_v1_mail_room_publication_text($state['live_published_on']);
    $sql   = "UPDATE {$table} SET draft_title = %s, draft_body = %s, draft_published_on = "
        . noelclark_v1_mail_room_admin_publication_date_sql($date)
        . ', live_title = %s, live_body = %s, live_published_on = '
        . noelclark_v1_mail_room_admin_publication_date_sql($date)
        . ', live_approval_mode = %s, visibility = %s, audience = %s, unpublished_reason = %s, '
        . 'permission_hold_reason = %s, approved_at = %s, updated_at = %s WHERE mr_id = %d';
    $values = array(
        (string) $state['draft_title'],
        (string) $state['draft_body'],
    );

    if ($date !== '') {
        $values[] = $date;
    }

    $values[] = (string) $state['live_title'];
    $values[] = (string) $state['live_body'];

    if ($date !== '') {
        $values[] = $date;
    }

    $values[] = (string) $state['live_approval_mode'];
    $values[] = (string) $state['visibility'];
    $values[] = 'everyone';
    $values[] = '';
    $values[] = '';
    $values[] = $now;
    $values[] = $now;
    $values[] = (int) $mr_id;
    $updated  = $wpdb->query($wpdb->prepare($sql, ...$values));

    return $updated !== false && $wpdb->last_error === '';
}

/**
 * Record unpublish without changing the approved text.
 *
 * @param int    $mr_id  Internal letter id.
 * @param string $reason Why it was unpublished.
 * @param string $now    GMT timestamp.
 * @return bool
 */
function noelclark_v1_mail_room_admin_publication_update_unpublish($mr_id, $reason, $now) {
    global $wpdb;

    $table   = noelclark_v1_mail_room_table('mr_publications');
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table} SET visibility = %s, unpublished_reason = %s, audience = %s, updated_at = %s WHERE mr_id = %d",
            'unpublished',
            $reason,
            'everyone',
            $now,
            (int) $mr_id
        )
    );

    return $updated !== false && $wpdb->last_error === '';
}

/**
 * @param int                  $mr_id   Internal letter id.
 * @param string               $type    Event type.
 * @param array<string, mixed> $payload Event payload.
 * @param string               $now     GMT timestamp.
 * @return bool
 */
function noelclark_v1_mail_room_admin_publication_insert_history($mr_id, $type, $payload, $now) {
    $json = wp_json_encode($payload);

    if (!is_string($json) || $json === '') {
        return false;
    }

    $event_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        noelclark_v1_mail_room_table('mr_events'),
        array(
            'mr_id'        => (int) $mr_id,
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
 * @param string $mr_ref Normalized reference.
 * @param string $value  Notice value.
 * @param string $reason Optional reason.
 * @return void
 */
function noelclark_v1_mail_room_admin_publication_redirect($mr_ref, $value, $reason = '') {
    $notice = array('pub' => $value);

    if ($reason !== '') {
        $notice['pub_reason'] = $reason;
    }

    noelclark_v1_mail_room_admin_redirect_after_status($mr_ref, $notice);
}

/**
 * @param string $mr_ref Normalized reference.
 * @return array{ok: true, mr_id: int, letter_body: string, state: array<string, mixed>}|null
 */
function noelclark_v1_mail_room_admin_publication_begin($mr_ref) {
    $loaded = noelclark_v1_mail_room_admin_publication_load($mr_ref);

    if (empty($loaded['ok'])) {
        $code = isset($loaded['code']) ? (string) $loaded['code'] : 'storage';
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', $code === 'not_found' ? 'not_found' : 'storage');

        return null;
    }

    return $loaded;
}

/**
 * Save the working draft. The approved copy is not written.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_publication_draft() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_publication_draft_' . $mr_ref);

    $date = noelclark_v1_mail_room_admin_publication_date(noelclark_v1_mail_room_admin_post_string('draft_published_on'));

    if ($date === null) {
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'invalid_date');
    }

    $loaded = noelclark_v1_mail_room_admin_publication_begin($mr_ref);

    if ($loaded === null) {
        return;
    }

    $next = noelclark_v1_mail_room_publication_save_draft(
        $loaded['state'],
        sanitize_text_field(noelclark_v1_mail_room_admin_post_string('draft_title')),
        sanitize_textarea_field(noelclark_v1_mail_room_admin_post_string('draft_body')),
        $date
    );
    $now = current_time('mysql', true);

    if (noelclark_v1_mail_room_publication_text($loaded['state']['public_slug']) === '') {
        $inserted = noelclark_v1_mail_room_admin_publication_insert((int) $loaded['mr_id'], $next, $now);

        if (empty($inserted['ok'])) {
            noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
        }

        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'draft');
    }

    if (!noelclark_v1_mail_room_admin_publication_update_draft((int) $loaded['mr_id'], $next, $now)) {
        noelclark_v1_mail_room_admin_log('publication_write_error');
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
    }

    noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'draft');
}

/**
 * Copy the original letter into the draft. The submission row is only read.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_publication_copy() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_publication_copy_' . $mr_ref);
    $loaded = noelclark_v1_mail_room_admin_publication_begin($mr_ref);

    if ($loaded === null) {
        return;
    }

    $next = noelclark_v1_mail_room_publication_copy_original_into_draft($loaded['state'], $loaded['letter_body']);
    $now  = current_time('mysql', true);

    if (noelclark_v1_mail_room_publication_text($loaded['state']['public_slug']) === '') {
        $inserted = noelclark_v1_mail_room_admin_publication_insert((int) $loaded['mr_id'], $next, $now);

        if (empty($inserted['ok'])) {
            noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
        }

        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'copied');
    }

    if (!noelclark_v1_mail_room_admin_publication_update_draft((int) $loaded['mr_id'], $next, $now)) {
        noelclark_v1_mail_room_admin_log('publication_write_error');
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
    }

    noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'copied');
}

/**
 * Approve the saved draft when the current letter view allows it.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_publication_approve() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_publication_approve_' . $mr_ref);
    $loaded = noelclark_v1_mail_room_admin_publication_begin($mr_ref);

    if ($loaded === null) {
        return;
    }

    $current = noelclark_v1_mail_room_admin_publication_current_view($mr_ref);

    if (empty($current['ok'])) {
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
    }

    $permission = noelclark_v1_mail_room_publication_permission_from_letter_view($current['view']);
    $decision   = noelclark_v1_mail_room_publication_approve(
        $loaded['state'],
        $permission['mode'],
        $permission['credit'],
        (string) get_current_user_id()
    );

    if (empty($decision['ok'])) {
        $code = isset($decision['code']) ? (string) $decision['code'] : 'permission';
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', $code);
    }

    $now   = current_time('mysql', true);
    $state = $decision['publication'];
    $wpdb->query('START TRANSACTION');

    if (noelclark_v1_mail_room_publication_text($loaded['state']['public_slug']) === '') {
        $inserted = noelclark_v1_mail_room_admin_publication_insert((int) $loaded['mr_id'], $state, $now);

        if (empty($inserted['ok'])) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
        }

        $state = $inserted['state'];
        $decision['payload']['public_slug'] = $state['public_slug'];
    }

    $saved = noelclark_v1_mail_room_admin_publication_update_approval((int) $loaded['mr_id'], $state, $now)
        && noelclark_v1_mail_room_admin_publication_insert_history(
            (int) $loaded['mr_id'],
            (string) $decision['event_type'],
            $decision['payload'],
            $now
        );

    if (!$saved || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('publication_write_error');
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
    }

    noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'approved');
}

/**
 * Unpublish the approved copy and record who, why, and when.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_publication_unpublish() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_publication_unpublish_' . $mr_ref);
    $loaded = noelclark_v1_mail_room_admin_publication_begin($mr_ref);

    if ($loaded === null) {
        return;
    }

    $decision = noelclark_v1_mail_room_publication_unpublish(
        $loaded['state'],
        sanitize_textarea_field(noelclark_v1_mail_room_admin_post_string('unpublish_reason')),
        (string) get_current_user_id()
    );

    if (empty($decision['ok'])) {
        $code = isset($decision['code']) ? (string) $decision['code'] : 'empty_reason';
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', $code);
    }

    $now = current_time('mysql', true);
    $wpdb->query('START TRANSACTION');
    $saved = noelclark_v1_mail_room_admin_publication_update_unpublish(
        (int) $loaded['mr_id'],
        (string) $decision['publication']['unpublished_reason'],
        $now
    ) && noelclark_v1_mail_room_admin_publication_insert_history(
        (int) $loaded['mr_id'],
        (string) $decision['event_type'],
        $decision['payload'],
        $now
    );

    if (!$saved || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('publication_write_error');
        noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'error', 'storage');
    }

    noelclark_v1_mail_room_admin_publication_redirect($mr_ref, 'unpublished');
}

/**
 * Logged-out publication posts are refused.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_reject_publication() {
    wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
}
add_action('admin_post_noelclark_v1_mail_room_save_publication_draft', 'noelclark_v1_mail_room_admin_handle_publication_draft');
add_action('admin_post_nopriv_noelclark_v1_mail_room_save_publication_draft', 'noelclark_v1_mail_room_admin_reject_publication');
add_action('admin_post_noelclark_v1_mail_room_copy_publication_original', 'noelclark_v1_mail_room_admin_handle_publication_copy');
add_action('admin_post_nopriv_noelclark_v1_mail_room_copy_publication_original', 'noelclark_v1_mail_room_admin_reject_publication');
add_action('admin_post_noelclark_v1_mail_room_approve_publication', 'noelclark_v1_mail_room_admin_handle_publication_approve');
add_action('admin_post_nopriv_noelclark_v1_mail_room_approve_publication', 'noelclark_v1_mail_room_admin_reject_publication');
add_action('admin_post_noelclark_v1_mail_room_unpublish_publication', 'noelclark_v1_mail_room_admin_handle_publication_unpublish');
add_action('admin_post_nopriv_noelclark_v1_mail_room_unpublish_publication', 'noelclark_v1_mail_room_admin_reject_publication');

/**
 * After a permission save has committed, record a hide when the letter view requires one.
 *
 * A failed history write does not undo the permission save.
 *
 * @param string $mr_ref Normalized reference.
 * @return void
 */
function noelclark_v1_mail_room_publication_sync_after_permission($mr_ref) {
    try {
        noelclark_v1_mail_room_publication_sync_after_permission_write($mr_ref);
    } catch (Throwable $e) {
        noelclark_v1_mail_room_admin_log('publication_hold_log_failed');
    }
}

/**
 * @param string $mr_ref Normalized reference.
 * @return void
 */
function noelclark_v1_mail_room_publication_sync_after_permission_write($mr_ref) {
    global $wpdb;

    $loaded = noelclark_v1_mail_room_admin_publication_load($mr_ref);

    if (empty($loaded['ok']) || (string) $loaded['state']['visibility'] !== 'approved') {
        return;
    }

    $current = noelclark_v1_mail_room_admin_publication_current_view($mr_ref);

    if (empty($current['ok'])) {
        noelclark_v1_mail_room_admin_log('publication_hold_log_failed');

        return;
    }

    $permission = noelclark_v1_mail_room_publication_permission_from_letter_view($current['view']);
    $applied    = noelclark_v1_mail_room_publication_apply_permission(
        $loaded['state'],
        $permission['mode'],
        $permission['credit']
    );
    $reason = (string) $applied['publication']['permission_hold_reason'];
    $table  = noelclark_v1_mail_room_table('mr_publications');
    $now    = current_time('mysql', true);
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table} SET permission_hold_reason = %s, updated_at = %s WHERE mr_id = %d AND visibility = %s",
            $reason,
            $now,
            (int) $loaded['mr_id'],
            'approved'
        )
    );

    if ($updated === false || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_admin_log('publication_hold_log_failed');

        return;
    }

    if (empty($applied['record_hold'])) {
        return;
    }

    $saved = noelclark_v1_mail_room_admin_publication_insert_history(
        (int) $loaded['mr_id'],
        (string) $applied['event_type'],
        array(
            'user_id'     => (string) get_current_user_id(),
            'reason'      => (string) $applied['reason'],
            'public_slug' => (string) $loaded['state']['public_slug'],
        ),
        $now
    );

    if (!$saved) {
        noelclark_v1_mail_room_admin_log('publication_hold_log_failed');
    }
}

/**
 * Latest matching history row, if the letter's events loaded.
 *
 * @param array<string, mixed> $events Event loader result.
 * @param string               $type   Event type.
 * @return array<string, mixed>|null
 */
function noelclark_v1_mail_room_admin_publication_latest_event($events, $type) {
    if (empty($events['ok']) || !isset($events['rows']) || !is_array($events['rows'])) {
        return null;
    }

    $latest = null;

    foreach ($events['rows'] as $event) {
        if (is_array($event) && isset($event['event_type']) && (string) $event['event_type'] === $type) {
            $latest = $event;
        }
    }

    return $latest;
}

/**
 * @return void
 */
function noelclark_v1_mail_room_admin_render_publication_notice() {
    $saved = noelclark_v1_mail_room_admin_request_string('pub');

    if ($saved === 'draft') {
        noelclark_v1_mail_room_admin_notice('success', 'Draft saved. The approved version is unchanged.');

        return;
    }

    if ($saved === 'copied') {
        noelclark_v1_mail_room_admin_notice('success', 'The original letter was copied into the draft. The original submission is unchanged.');

        return;
    }

    if ($saved === 'approved') {
        noelclark_v1_mail_room_admin_notice('success', 'Approved and published. Readers will see this version when the public page is available.');

        return;
    }

    if ($saved === 'unpublished') {
        noelclark_v1_mail_room_admin_notice('success', 'Unpublished. The reason, you, and the time are in the publication history.');

        return;
    }

    if ($saved !== 'error') {
        return;
    }

    $reasons = array(
        'empty_title'  => 'Add a public title before approving.',
        'empty_body'   => 'Add the public letter text before approving.',
        'permission'   => 'Current writer permission does not allow publication.',
        'empty_reason' => 'Unpublish needs a reason.',
        'not_published'=> 'There is no approved version to unpublish.',
        'invalid_date' => 'Use a real publication date, or leave it blank.',
        'storage'      => 'The public version could not be saved.',
        'not_found'    => 'This letter could not be loaded.',
    );
    $reason  = sanitize_key(noelclark_v1_mail_room_admin_request_string('pub_reason'));
    $message = isset($reasons[$reason]) ? $reasons[$reason] : $reasons['storage'];

    noelclark_v1_mail_room_admin_notice('error', $message);
}

/**
 * Admin-only preview of the saved draft. It has no public address of its own.
 *
 * @param string $title    Draft title.
 * @param string $body     Draft letter text.
 * @param string $date     Draft publication date.
 * @param string $byline   Byline the draft would use, or empty when it would not be public.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_publication_preview($title, $body, $date, $byline) {
    ?>
    <div class="nc-mr-pub__preview" aria-label="Draft preview">
        <p class="eyebrow">Preview</p>
        <p class="nc-mr-pub__preview-note">This is the saved draft on this admin page. Readers do not see this preview.</p>
        <?php if ($byline === '') : ?>
            <p class="nc-mr-pub__preview-note">Readers would not see this draft with the current permission.</p>
        <?php else : ?>
            <p class="nc-mr-pub__byline"><?php echo esc_html($byline); ?></p>
        <?php endif; ?>
        <h3 class="nc-mr-pub__preview-title"><?php echo esc_html($title !== '' ? $title : 'Untitled'); ?></h3>
        <p class="nc-mr-pub__date"><?php echo esc_html($date !== '' ? $date : 'No publication date'); ?></p>
        <?php if (trim($body) === '') : ?>
            <p class="nc-mr-pub__preview-body"><?php echo esc_html('No public letter text yet.'); ?></p>
        <?php else : ?>
            <div class="nc-mr-pub__preview-body"><?php echo esc_html($body); ?></div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Public version section. Placed after the original letter.
 *
 * @param array<string, mixed> $row    Submission row.
 * @param array<string, mixed> $view   Letter view already shown on this page.
 * @param array<string, mixed> $parsed Parsed admin request.
 * @param array<string, mixed> $events Letter events.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_publication($row, $view, $parsed, $events) {
    $mr_ref = (string) $row['mr_ref'];
    $loaded = noelclark_v1_mail_room_admin_publication_load($mr_ref);

    if (empty($loaded['ok'])) {
        echo '<section class="card nc-mr-admin__publication" aria-label="Public version">';
        echo '<h3 class="nc-mr-admin__letter-title">Public version</h3>';
        noelclark_v1_mail_room_admin_notice('error', 'The public version could not be loaded.');
        echo '</section>';

        return;
    }

    $state      = $loaded['state'];
    $permission = noelclark_v1_mail_room_admin_inbox_permission($view);
    $label      = noelclark_v1_mail_room_publication_status_label($state, (string) $view['mode'], (string) $view['credit']);
    $byline     = noelclark_v1_mail_room_publication_preview_byline((string) $view['mode'], (string) $view['credit']);
    $can_approve = $byline !== '';
    $slug       = noelclark_v1_mail_room_publication_text($state['public_slug']);
    $removed    = noelclark_v1_mail_room_admin_publication_latest_event($events, NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_UNPUBLISHED);
    $removed_at = is_array($removed) && isset($removed['created_at'])
        ? noelclark_v1_mail_room_admin_eastern_parts($removed['created_at'])
        : null;
    ?>
    <section class="card nc-mr-admin__publication" aria-label="Public version">
        <?php noelclark_v1_mail_room_admin_render_publication_notice(); ?>
        <div class="nc-mr-admin__letter-head">
            <h3 class="nc-mr-admin__letter-title">Public version</h3>
            <span class="pill nc-mr-pub__status"><?php echo esc_html($label); ?></span>
        </div>
        <p class="nc-mr-pub__permission"><?php echo esc_html((string) $permission['label']); ?></p>
        <?php if ($label === 'Published' && (string) $state['live_approval_mode'] === 'anonymous' && (string) $view['mode'] === 'named') : ?>
            <p class="nc-mr-pub__permission">The public version stays Anonymous until you approve credited publication.</p>
        <?php endif; ?>
        <?php if ($label === 'Hidden because of writer permission' && (string) $state['live_approval_mode'] === 'named' && (string) $view['mode'] === 'anonymous') : ?>
            <p class="nc-mr-pub__permission">Hidden until you approve an anonymous-safe version.</p>
        <?php endif; ?>
        <?php if ($slug !== '') : ?>
            <p class="nc-mr-pub__address">Public address <span class="mono"><?php echo esc_html('/mail-room/l/' . $slug); ?></span></p>
        <?php endif; ?>
        <?php if ((string) $state['visibility'] === 'unpublished' && (string) $state['unpublished_reason'] !== '') : ?>
            <p class="nc-mr-pub__permission">
                <?php echo esc_html('Unpublish reason: ' . (string) $state['unpublished_reason']); ?>
                <?php if ($removed_at !== null) : ?>
                    <?php echo esc_html(' Recorded ' . $removed_at['date'] . ' · ' . $removed_at['time'] . '.'); ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <form class="nc-mr-pub__draft" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('noelclark_v1_mail_room_publication_draft_' . $mr_ref); ?>
            <input type="hidden" name="action" value="noelclark_v1_mail_room_save_publication_draft">
            <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
            <?php noelclark_v1_mail_room_admin_render_return_inputs($parsed); ?>
            <label class="eyebrow" for="nc-mr-pub-title">Public title</label>
            <input id="nc-mr-pub-title" class="nc-mr-pub__title" type="text" name="draft_title" value="<?php echo esc_attr((string) $state['draft_title']); ?>">
            <label class="eyebrow" for="nc-mr-pub-body">Public letter text</label>
            <textarea id="nc-mr-pub-body" class="nc-mr-pub__body" name="draft_body" rows="12"><?php echo esc_textarea((string) $state['draft_body']); ?></textarea>
            <label class="eyebrow" for="nc-mr-pub-date">Publication date</label>
            <input id="nc-mr-pub-date" class="nc-mr-pub__date-input" type="date" name="draft_published_on" value="<?php echo esc_attr((string) $state['draft_published_on']); ?>">
            <button type="submit" class="btn">Save draft</button>
        </form>
        <form class="nc-mr-pub__copy" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('noelclark_v1_mail_room_publication_copy_' . $mr_ref); ?>
            <input type="hidden" name="action" value="noelclark_v1_mail_room_copy_publication_original">
            <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
            <?php noelclark_v1_mail_room_admin_render_return_inputs($parsed); ?>
            <button type="submit" class="btn">Copy original into draft</button>
            <p class="nc-mr-pub__preview-note">This replaces the draft text with the original letter. The original submission stays unchanged, and the approved version stays as it is until you approve.</p>
        </form>
        <?php
        noelclark_v1_mail_room_admin_render_publication_preview(
            (string) $state['draft_title'],
            (string) $state['draft_body'],
            (string) $state['draft_published_on'],
            $byline
        );
        ?>
        <?php if ($can_approve) : ?>
            <form class="nc-mr-pub__approve" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('noelclark_v1_mail_room_publication_approve_' . $mr_ref); ?>
                <input type="hidden" name="action" value="noelclark_v1_mail_room_approve_publication">
                <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
                <?php noelclark_v1_mail_room_admin_render_return_inputs($parsed); ?>
                <button type="submit" class="btn">Approve and publish</button>
                <p class="nc-mr-pub__preview-note">This approves the saved draft. Save the draft first if you have just edited it.</p>
            </form>
        <?php else : ?>
            <p class="nc-mr-pub__permission">Approve and publish is unavailable until the writer’s current permission allows it.</p>
        <?php endif; ?>
        <?php if ((string) $state['visibility'] === 'approved') : ?>
            <form class="nc-mr-pub__unpublish" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('noelclark_v1_mail_room_publication_unpublish_' . $mr_ref); ?>
                <input type="hidden" name="action" value="noelclark_v1_mail_room_unpublish_publication">
                <input type="hidden" name="mr" value="<?php echo esc_attr($mr_ref); ?>">
                <?php noelclark_v1_mail_room_admin_render_return_inputs($parsed); ?>
                <label class="eyebrow" for="nc-mr-pub-reason">Reason for unpublishing</label>
                <textarea id="nc-mr-pub-reason" class="nc-mr-pub__reason" name="unpublish_reason" rows="3"></textarea>
                <button type="submit" class="btn">Unpublish</button>
            </form>
        <?php endif; ?>
    </section>
    <?php
}
