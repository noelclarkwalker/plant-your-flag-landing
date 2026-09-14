<?php
/**
 * Mail Room acceptance notification queue — Slice 3.
 *
 * Operational bell only. Not the source of accept. Never runs wp_mail
 * inside the letter-acceptance transaction.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NOELCLARK_V1_MAIL_ROOM_ALERT_RECIPIENT')) {
    define('NOELCLARK_V1_MAIL_ROOM_ALERT_RECIPIENT', 'mailroomalerts@gmail.com');
}

/** @var string Job status: waiting for first send attempt. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING = 'pending';

/** @var string Job status: claimed by a worker. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING = 'processing';

/** @var string Job status: transport handoff succeeded. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_SENT = 'sent';

/** @var string Job status: last handoff failed; eligible after backoff. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED = 'failed';

/** @var int Stale-processing window in seconds. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_STALE_SECONDS = 600;

/** @var int Maximum failed-job backoff in minutes. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_BACKOFF_CAP_MINUTES = 60;

/** @var int Missing-job reconciliation batch size. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_RECONCILE_LIMIT = 10;

/** @var string WP-Cron hook for the safety tick. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK = 'noelclark_v1_mail_room_notify_tick';

/** @var string Custom WP-Cron interval key. */
const NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_INTERVAL = 'noelclark_v1_mail_room_notify_interval';

/**
 * Privacy-safe operational log. No letter or writer PII.
 *
 * @param string   $class  Stable class (deadlock, lock_wait, …).
 * @param int|null $job_id Internal job id if already known.
 * @return void
 */
function noelclark_v1_mail_room_notify_log($class, $job_id = null) {
    $message = 'mail_room_notify_claim_aborted ' . $class;

    if ($job_id !== null && (int) $job_id > 0) {
        $message .= ' job=' . (int) $job_id;
    }

    error_log($message);
}

/**
 * Classify a wpdb error without requiring a specific errno format.
 *
 * @param string $error $wpdb->last_error.
 * @return string
 */
function noelclark_v1_mail_room_notify_classify_db_error($error) {
    $error = strtolower((string) $error);

    if ($error === '') {
        return 'claim_aborted';
    }

    if (strpos($error, 'deadlock') !== false) {
        return 'deadlock';
    }

    if (strpos($error, 'lock wait') !== false) {
        return 'lock_wait';
    }

    return 'claim_aborted';
}

/**
 * Best-effort ROLLBACK.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_rollback() {
    global $wpdb;

    $wpdb->query('ROLLBACK');
}

/**
 * Failed-job backoff in minutes. Caps before exponentiation.
 *
 * attempt 1 -> 1; 2 -> 2; 3 -> 4; 4 -> 8; 5 -> 16; 6 -> 32; 7+ -> 60.
 *
 * @param int $attempts Attempt count from the selected row.
 * @return int
 */
function noelclark_v1_mail_room_notify_backoff_minutes($attempts) {
    $attempts = (int) $attempts;

    if ($attempts >= 7) {
        return NOELCLARK_V1_MAIL_ROOM_NOTIFY_BACKOFF_CAP_MINUTES;
    }

    if ($attempts < 1) {
        return 1;
    }

    return (int) pow(2, $attempts - 1);
}

/**
 * Failed-job backoff in seconds from the attempts value on that generation.
 *
 * @param int $attempts Attempt count from the selected row.
 * @return int
 */
function noelclark_v1_mail_room_notify_backoff_seconds($attempts) {
    return noelclark_v1_mail_room_notify_backoff_minutes($attempts) * 60;
}

/**
 * GMT datetime string offset from now.
 *
 * @param int $seconds_ago Seconds in the past.
 * @return string
 */
function noelclark_v1_mail_room_notify_gmt_ago($seconds_ago) {
    return gmdate('Y-m-d H:i:s', time() - (int) $seconds_ago);
}

/**
 * Configured operational alert recipient.
 *
 * @return string Empty when invalid.
 */
function noelclark_v1_mail_room_notify_recipient() {
    $recipient = NOELCLARK_V1_MAIL_ROOM_ALERT_RECIPIENT;

    if (!is_string($recipient)) {
        return '';
    }

    $recipient = trim($recipient);

    if ($recipient === '' || !is_email($recipient)) {
        return '';
    }

    return $recipient;
}

/**
 * Transport handoff. wp_mail true means handoff only, not inbox delivery.
 *
 * @param string $subject Plain-text subject.
 * @param string $body    Plain-text body.
 * @return bool
 */
function noelclark_v1_mail_room_alert_handoff($subject, $body) {
    $recipient = noelclark_v1_mail_room_notify_recipient();

    if ($recipient === '') {
        return false;
    }

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
    );

    return (bool) wp_mail($recipient, $subject, $body, $headers);
}

/**
 * Ensure exactly one notification job exists for an accepted MR.
 *
 * Duplicate UNIQUE(mr_id) is a safe no-op. Does not send mail.
 *
 * @param int $mr_id Internal accepted submission id.
 * @return bool true when a job exists or was created.
 */
function noelclark_v1_mail_room_ensure_notification_job($mr_id) {
    global $wpdb;

    $mr_id = (int) $mr_id;

    if ($mr_id < 1) {
        return false;
    }

    $table = noelclark_v1_mail_room_table('notification_jobs');
    $now   = current_time('mysql', true);

    $inserted = $wpdb->insert(
        $table,
        array(
            'mr_id'      => $mr_id,
            'status'     => NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING,
            'attempts'   => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ),
        array('%d', '%s', '%d', '%s', '%s')
    );

    if ($inserted !== false) {
        noelclark_v1_mail_room_notify_kick();

        return true;
    }

    if (noelclark_v1_mail_room_is_duplicate_key_error((string) $wpdb->last_error)) {
        noelclark_v1_mail_room_notify_kick();

        return true;
    }

    noelclark_v1_mail_room_notify_log('ensure_failed');
    noelclark_v1_mail_room_notify_kick();

    return false;
}

/**
 * Create missing jobs for accepted letters that have none.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_reconcile_missing_jobs() {
    global $wpdb;

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $jobs        = noelclark_v1_mail_room_table('notification_jobs');
    $limit       = (int) NOELCLARK_V1_MAIL_ROOM_NOTIFY_RECONCILE_LIMIT;

    $sql = "SELECT s.id FROM {$submissions} s "
        . "LEFT JOIN {$jobs} j ON j.mr_id = s.id "
        . "WHERE j.mr_id IS NULL "
        . "ORDER BY s.id ASC "
        . "LIMIT {$limit}";

    $ids = $wpdb->get_col($sql);

    if (!is_array($ids)) {
        noelclark_v1_mail_room_notify_log(
            noelclark_v1_mail_room_notify_classify_db_error((string) $wpdb->last_error)
        );

        return;
    }

    foreach ($ids as $mr_id) {
        noelclark_v1_mail_room_ensure_notification_job((int) $mr_id);
    }
}

/**
 * Claim one eligible job. Returns ownership only after confirmed COMMIT.
 *
 * @return array{ok: true, job_id: int, mr_id: int, claimed_attempts: int}|array{ok: false}
 */
function noelclark_v1_mail_room_notify_claim_one() {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('notification_jobs');
    $now   = current_time('mysql', true);
    $stale = noelclark_v1_mail_room_notify_gmt_ago(NOELCLARK_V1_MAIL_ROOM_NOTIFY_STALE_SECONDS);

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_notify_log('start_failed');

        return array('ok' => false);
    }

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, mr_id, status, attempts, last_attempt_at FROM {$table} "
            . 'WHERE status = %s '
            . 'OR (status = %s AND last_attempt_at IS NOT NULL AND last_attempt_at <= %s) '
            . 'OR (status = %s AND (last_attempt_at IS NULL OR DATE_ADD(last_attempt_at, INTERVAL '
            . 'CASE WHEN CAST(attempts AS SIGNED) >= 7 THEN %d '
            . 'WHEN CAST(attempts AS SIGNED) < 1 THEN 1 '
            . 'ELSE POW(2, CAST(attempts AS SIGNED) - 1) END MINUTE) <= %s)) '
            . 'ORDER BY id ASC LIMIT 1 FOR UPDATE',
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
            $stale,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_BACKOFF_CAP_MINUTES,
            $now
        )
    );

    if ($row === null && $wpdb->last_error !== '') {
        $class = noelclark_v1_mail_room_notify_classify_db_error((string) $wpdb->last_error);
        noelclark_v1_mail_room_notify_rollback();
        noelclark_v1_mail_room_notify_log($class);

        return array('ok' => false);
    }

    if (!$row) {
        $committed = $wpdb->query('COMMIT');

        if ($committed === false) {
            noelclark_v1_mail_room_notify_rollback();
            noelclark_v1_mail_room_notify_log('commit_failed');
        }

        return array('ok' => false);
    }

    $job_id            = (int) $row->id;
    $mr_id             = (int) $row->mr_id;
    $status            = (string) $row->status;
    $old_attempts      = (int) $row->attempts;
    $claimed_attempts  = $old_attempts + 1;
    $last_attempt_at   = $row->last_attempt_at;

    if ($status === NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING) {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, attempts = %d, last_attempt_at = %s, updated_at = %s "
                . 'WHERE id = %d AND attempts = %d AND status = %s',
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $claimed_attempts,
                $now,
                $now,
                $job_id,
                $old_attempts,
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PENDING
            )
        );
    } elseif ($status === NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING) {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, attempts = %d, last_attempt_at = %s, updated_at = %s "
                . 'WHERE id = %d AND attempts = %d AND status = %s AND last_attempt_at IS NOT NULL AND last_attempt_at <= %s',
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $claimed_attempts,
                $now,
                $now,
                $job_id,
                $old_attempts,
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $stale
            )
        );
    } elseif ($status === NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED) {
        $failed_cutoff = noelclark_v1_mail_room_notify_gmt_ago(
            noelclark_v1_mail_room_notify_backoff_seconds($old_attempts)
        );

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, attempts = %d, last_attempt_at = %s, updated_at = %s "
                . 'WHERE id = %d AND attempts = %d AND status = %s AND (last_attempt_at IS NULL OR last_attempt_at <= %s)',
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $claimed_attempts,
                $now,
                $now,
                $job_id,
                $old_attempts,
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED,
                $failed_cutoff
            )
        );
    } else {
        noelclark_v1_mail_room_notify_rollback();

        return array('ok' => false);
    }

    if ($updated === false) {
        $class = noelclark_v1_mail_room_notify_classify_db_error((string) $wpdb->last_error);
        noelclark_v1_mail_room_notify_rollback();
        noelclark_v1_mail_room_notify_log($class, $job_id);

        return array('ok' => false);
    }

    if ((int) $updated !== 1) {
        noelclark_v1_mail_room_notify_rollback();

        return array('ok' => false);
    }

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        noelclark_v1_mail_room_notify_rollback();
        noelclark_v1_mail_room_notify_log('commit_failed', $job_id);

        return array('ok' => false);
    }

    unset($last_attempt_at);

    return array(
        'ok'                => true,
        'job_id'            => $job_id,
        'mr_id'             => $mr_id,
        'claimed_attempts'  => $claimed_attempts,
    );
}

/**
 * Persist sent/failed for this claim generation only.
 *
 * @param int         $job_id            Claimed job id.
 * @param int         $claimed_attempts  Generation owned by this worker.
 * @param string      $status            sent or failed.
 * @param string|null $error_code        Operational error code when failed.
 * @return void
 */
function noelclark_v1_mail_room_notify_persist_outcome($job_id, $claimed_attempts, $status, $error_code = null) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('notification_jobs');
    $now   = current_time('mysql', true);

    if ($error_code === null) {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, last_error_code = NULL, updated_at = %s "
                . 'WHERE id = %d AND status = %s AND attempts = %d',
                $status,
                $now,
                $job_id,
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $claimed_attempts
            )
        );
    } else {
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET status = %s, last_error_code = %s, updated_at = %s "
                . 'WHERE id = %d AND status = %s AND attempts = %d',
                $status,
                $error_code,
                $now,
                $job_id,
                NOELCLARK_V1_MAIL_ROOM_NOTIFY_PROCESSING,
                $claimed_attempts
            )
        );
    }

    if ($updated === false) {
        noelclark_v1_mail_room_notify_log('outcome_persist_failed', $job_id);

        return;
    }

    if ((int) $updated === 1) {
        return;
    }
}

/**
 * Load privacy-minimal fields for an accepted letter.
 *
 * @param int $mr_id Internal submission id.
 * @return object|null
 */
function noelclark_v1_mail_room_notify_load_alert_fields($mr_id) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_submissions');

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT mr_ref, accepted_at, sharing_choice FROM {$table} WHERE id = %d",
            $mr_id
        )
    );
}

/**
 * Compose the operational alert. No letter or writer PII.
 *
 * @param object $row Submission alert fields.
 * @return array{subject: string, body: string}
 */
function noelclark_v1_mail_room_notify_compose($row) {
    $subject = 'Mail Room: letter accepted';

    $body = implode(
        "\n",
        array(
            'A Mail Room letter was accepted.',
            '',
            'Reference: ' . (string) $row->mr_ref,
            'Accepted at (GMT): ' . (string) $row->accepted_at,
            'Sharing choice: ' . (string) $row->sharing_choice,
        )
    );

    return array(
        'subject' => $subject,
        'body'    => $body,
    );
}

/**
 * Claim at most one job and attempt transport handoff.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_process_one() {
    $claimed = noelclark_v1_mail_room_notify_claim_one();

    if (empty($claimed['ok'])) {
        return;
    }

    $job_id           = (int) $claimed['job_id'];
    $mr_id            = (int) $claimed['mr_id'];
    $claimed_attempts = (int) $claimed['claimed_attempts'];

    $row = noelclark_v1_mail_room_notify_load_alert_fields($mr_id);

    if (
        !$row
        || !is_string($row->mr_ref)
        || $row->mr_ref === ''
        || !is_string($row->accepted_at)
        || !is_string($row->sharing_choice)
    ) {
        noelclark_v1_mail_room_notify_persist_outcome(
            $job_id,
            $claimed_attempts,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED,
            'submission_unavailable'
        );

        return;
    }

    if (noelclark_v1_mail_room_notify_recipient() === '') {
        noelclark_v1_mail_room_notify_persist_outcome(
            $job_id,
            $claimed_attempts,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED,
            'recipient_unavailable'
        );

        return;
    }

    $message = noelclark_v1_mail_room_notify_compose($row);
    $sent    = noelclark_v1_mail_room_alert_handoff($message['subject'], $message['body']);

    if ($sent) {
        noelclark_v1_mail_room_notify_persist_outcome(
            $job_id,
            $claimed_attempts,
            NOELCLARK_V1_MAIL_ROOM_NOTIFY_SENT,
            null
        );

        return;
    }

    noelclark_v1_mail_room_notify_persist_outcome(
        $job_id,
        $claimed_attempts,
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_FAILED,
        'mail_handoff_error'
    );
}

/**
 * Safety tick: reconcile missing jobs, then at most one send.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_run_tick() {
    noelclark_v1_mail_room_notify_reconcile_missing_jobs();
    noelclark_v1_mail_room_notify_process_one();
}

/**
 * Custom WP-Cron interval for the safety tick.
 *
 * @param array<string, array<string, mixed>> $schedules Existing schedules.
 * @return array<string, array<string, mixed>>
 */
function noelclark_v1_mail_room_notify_cron_schedules($schedules) {
    if (!is_array($schedules)) {
        $schedules = array();
    }

    $schedules[NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_INTERVAL] = array(
        'interval' => 300,
        'display'  => 'NoelClark Mail Room notify safety tick',
    );

    return $schedules;
}

/**
 * Schedule the recurring safety tick if absent.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_maybe_schedule() {
    if (wp_next_scheduled(NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK)) {
        return;
    }

    wp_schedule_event(
        time() + 60,
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_INTERVAL,
        NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK
    );
}

/**
 * Ask WP-Cron to run a tick soon without draining on this request.
 *
 * @return void
 */
function noelclark_v1_mail_room_notify_kick() {
    $next = wp_next_scheduled(NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK);

    if ($next && $next <= time() + 60) {
        return;
    }

    wp_schedule_single_event(time() + 30, NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK);
}

add_filter('cron_schedules', 'noelclark_v1_mail_room_notify_cron_schedules');
add_action('init', 'noelclark_v1_mail_room_notify_maybe_schedule', 20);
add_action(NOELCLARK_V1_MAIL_ROOM_NOTIFY_CRON_HOOK, 'noelclark_v1_mail_room_notify_run_tick');
