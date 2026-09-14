<?php
/**
 * Mail Room post-accept association credentials — Slice 4.
 *
 * Short-lived hashed secrets that bind an optional questionnaire write to an
 * already-accepted letter. Not authentication. Never runs inside the letter
 * acceptance transaction.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Association window in minutes from original accepted_at. */
const NOELCLARK_V1_MAIL_ROOM_ASSOCIATION_TTL_MINUTES = 120;

/**
 * Privacy-safe operational log. No token material or writer PII.
 *
 * @param string $class Stable class.
 * @return void
 */
function noelclark_v1_mail_room_association_log($class) {
    error_log('mail_room_association ' . $class);
}

/**
 * Best-effort ROLLBACK.
 *
 * @return void
 */
function noelclark_v1_mail_room_association_rollback() {
    global $wpdb;

    $wpdb->query('ROLLBACK');
}

/**
 * Generate one high-entropy raw association credential.
 *
 * @return string 64-character lowercase hex.
 */
function noelclark_v1_mail_room_association_generate_raw() {
    return bin2hex(random_bytes(32));
}

/**
 * Stored hash of a raw association credential. Never log the input or result.
 *
 * @param string $raw Raw credential.
 * @return string 64-character hex HMAC-SHA256.
 */
function noelclark_v1_mail_room_association_hash($raw) {
    return hash_hmac('sha256', $raw, wp_salt('auth'));
}

/**
 * Fixed expiry from original accepted_at. Never extends.
 *
 * @param string $accepted_at GMT datetime.
 * @return string|false GMT datetime or false when accepted_at is unusable.
 */
function noelclark_v1_mail_room_association_expires_at($accepted_at) {
    if (!is_string($accepted_at) || $accepted_at === '') {
        return false;
    }

    try {
        $accepted = new DateTimeImmutable($accepted_at, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return false;
    }

    $minutes = (int) NOELCLARK_V1_MAIL_ROOM_ASSOCIATION_TTL_MINUTES;

    return $accepted->modify('+' . $minutes . ' minutes')->format('Y-m-d H:i:s');
}

/**
 * Issue or rotate an association credential after confirmed letter COMMIT.
 *
 * Serializes on the accepted MR row. Failure returns null and does not
 * change letter-accept success.
 *
 * @param int $mr_id Internal accepted submission id.
 * @return string|null Raw credential after confirmed COMMIT; null when none.
 */
function noelclark_v1_mail_room_ensure_association_token($mr_id) {
    global $wpdb;

    $mr_id = (int) $mr_id;

    if ($mr_id < 1) {
        return null;
    }

    $submissions = noelclark_v1_mail_room_table('mr_submissions');
    $tokens      = noelclark_v1_mail_room_table('mr_association_tokens');
    $now         = current_time('mysql', true);

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_association_log('start_failed');

        return null;
    }

    $mr = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, accepted_at FROM {$submissions} WHERE id = %d FOR UPDATE",
            $mr_id
        )
    );

    if ($mr === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('storage_error');

        return null;
    }

    if (!$mr || (int) $mr->id !== $mr_id) {
        noelclark_v1_mail_room_association_rollback();

        return null;
    }

    $expires_at = noelclark_v1_mail_room_association_expires_at((string) $mr->accepted_at);

    if ($expires_at === false) {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('storage_error');

        return null;
    }

    if ($now >= $expires_at) {
        $committed = $wpdb->query('COMMIT');

        if ($committed === false) {
            noelclark_v1_mail_room_association_rollback();
            noelclark_v1_mail_room_association_log('commit_failed');
        }

        return null;
    }

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, consumed_at, revoked_at FROM {$tokens} WHERE mr_id = %d FOR UPDATE",
            $mr_id
        )
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('storage_error');

        return null;
    }

    foreach ($rows as $row) {
        if ($row->consumed_at !== null && (string) $row->consumed_at !== '') {
            $committed = $wpdb->query('COMMIT');

            if ($committed === false) {
                noelclark_v1_mail_room_association_rollback();
                noelclark_v1_mail_room_association_log('commit_failed');
            }

            return null;
        }
    }

    $revoked = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$tokens} SET revoked_at = %s "
            . 'WHERE mr_id = %d AND consumed_at IS NULL AND revoked_at IS NULL',
            $now,
            $mr_id
        )
    );

    if ($revoked === false) {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('storage_error');

        return null;
    }

    $raw  = noelclark_v1_mail_room_association_generate_raw();
    $hash = noelclark_v1_mail_room_association_hash($raw);

    $inserted = $wpdb->insert(
        $tokens,
        array(
            'mr_id'      => $mr_id,
            'token_hash' => $hash,
            'expires_at' => $expires_at,
            'created_at' => $now,
        ),
        array('%d', '%s', '%s', '%s')
    );

    if ($inserted === false) {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('storage_error');

        return null;
    }

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        noelclark_v1_mail_room_association_rollback();
        noelclark_v1_mail_room_association_log('commit_failed');

        return null;
    }

    return $raw;
}

/**
 * Load a token row by stored hash and lock it.
 *
 * @param string $hash HMAC-SHA256 hex.
 * @return array{ok: true, row: object}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_association_lock_hash($hash) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_association_tokens');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, mr_id, expires_at, consumed_at, revoked_at FROM {$table} "
            . 'WHERE token_hash = %s FOR UPDATE',
            $hash
        )
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($rows) !== 1) {
        return array(
            'ok'   => false,
            'code' => 'association_invalid',
        );
    }

    return array(
        'ok'  => true,
        'row' => $rows[0],
    );
}

/**
 * Classify a locked token row for questionnaire acceptance.
 *
 * Consumed is distinguished before revoked/expired so a post-COMMIT retry
 * is not reported as a generic invalid secret.
 *
 * @param object $row Locked token row.
 * @param string $now GMT datetime.
 * @return string|null Failure code, or null when the credential is usable.
 */
function noelclark_v1_mail_room_association_token_failure($row, $now) {
    if ($row->consumed_at !== null && (string) $row->consumed_at !== '') {
        return 'association_consumed';
    }

    if ($row->revoked_at !== null && (string) $row->revoked_at !== '') {
        return 'association_revoked';
    }

    if ((string) $row->expires_at === '' || $now >= (string) $row->expires_at) {
        return 'association_expired';
    }

    return null;
}

/**
 * Consume a locked valid token. Caller must hold the row lock.
 *
 * @param int    $token_id Locked token id.
 * @param string $now      GMT datetime.
 * @return bool
 */
function noelclark_v1_mail_room_association_consume($token_id, $now) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_association_tokens');

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$table} SET consumed_at = %s "
            . 'WHERE id = %d AND consumed_at IS NULL AND revoked_at IS NULL',
            $now,
            (int) $token_id
        )
    );

    return $updated !== false && (int) $updated === 1;
}
