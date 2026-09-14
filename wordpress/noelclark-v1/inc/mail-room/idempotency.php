<?php
/**
 * Mail Room letter-accept idempotency — Slice 2.
 *
 * Scope mr_letter: one client key produces at most one accepted letter.
 * No public transport lives here.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Idempotency scope for Mail Room letter acceptance. */
const NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_SCOPE = 'mr_letter';

/**
 * Whether a DB error is a UNIQUE/duplicate-key conflict.
 *
 * @param string $error $wpdb->last_error.
 * @return bool
 */
function noelclark_v1_mail_room_is_duplicate_key_error($error) {
    return (bool) preg_match('/duplicate/i', $error);
}

/**
 * Load the idempotency row for this letter-accept key, locking it.
 *
 * @param string $idempotency_key Validated key.
 * @return object|null
 */
function noelclark_v1_mail_room_idempotency_lock($idempotency_key) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('idempotency');

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, mr_id, q_id FROM {$table} WHERE scope = %s AND idempotency_key = %s FOR UPDATE",
            NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_SCOPE,
            $idempotency_key
        )
    );
}

/**
 * Claim a new idempotency row inside an open transaction.
 *
 * @param string $idempotency_key Validated key.
 * @param string $created_at      GMT datetime.
 * @return true|string true on insert, 'duplicate' on unique conflict, 'error' otherwise.
 */
function noelclark_v1_mail_room_idempotency_claim($idempotency_key, $created_at) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('idempotency');

    $inserted = $wpdb->insert(
        $table,
        array(
            'scope'           => NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_SCOPE,
            'idempotency_key' => $idempotency_key,
            'created_at'      => $created_at,
        ),
        array('%s', '%s', '%s')
    );

    if ($inserted !== false) {
        return true;
    }

    if (noelclark_v1_mail_room_is_duplicate_key_error((string) $wpdb->last_error)) {
        return 'duplicate';
    }

    return 'error';
}

/**
 * Bind an accepted Mail Room id onto the claimed idempotency row.
 *
 * @param string $idempotency_key Validated key.
 * @param int    $mr_id           Internal submission id.
 * @return bool
 */
function noelclark_v1_mail_room_idempotency_bind($idempotency_key, $mr_id) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('idempotency');

    $updated = $wpdb->update(
        $table,
        array(
            'mr_id' => $mr_id,
        ),
        array(
            'scope'           => NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_SCOPE,
            'idempotency_key' => $idempotency_key,
        ),
        array('%d'),
        array('%s', '%s')
    );

    return $updated !== false && (int) $updated > 0;
}
