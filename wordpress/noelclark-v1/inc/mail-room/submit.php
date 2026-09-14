<?php
/**
 * Mail Room letter-accept service — Slice 2.
 *
 * Internal, unhooked, transactional. No REST, AJAX, SEND, admin, or WP-CLI.
 *
 * A real acceptance requires an exact immutable mail_room_submission_terms
 * snapshot. This file never seeds legal copy.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Legal-document type for Mail Room Submission Terms. */
const NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE = 'mail_room_submission_terms';

/** @var string Initial editorial status stored value. */
const NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW = 'new';

/** @var string Initial withdrawal-request status stored value. */
const NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_NONE = 'none';

/** @var string Append-only event recorded at acceptance. */
const NOELCLARK_V1_MAIL_ROOM_EVENT_ACCEPTED = 'accepted';

/** @var int Opaque-ref insert retries on UNIQUE collision. */
const NOELCLARK_V1_MAIL_ROOM_REF_INSERT_ATTEMPTS = 5;

/**
 * Resolve the exact Mail Room Submission Terms snapshot.
 *
 * Never selects “latest.” Zero or many rows is a failure.
 *
 * @param string $version_label Trimmed caller version label.
 * @return array{ok: true, id: int, version_label: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_resolve_submission_terms($version_label) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('legal_documents');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, version_label FROM {$table} WHERE doc_type = %s AND version_label = %s",
            NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE,
            $version_label
        )
    );

    if (!is_array($rows) || count($rows) !== 1) {
        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    $id = (int) $rows[0]->id;

    if ($id < 1 || (string) $rows[0]->version_label !== $version_label) {
        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    return array(
        'ok'            => true,
        'id'            => $id,
        'version_label' => (string) $rows[0]->version_label,
    );
}

/**
 * Accepted-letter result for a completed idempotency row.
 *
 * @param int $mr_id Internal submission id.
 * @return array{ok: true, replay: true, mr_ref: string, accepted_at: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_replay_accepted_result($mr_id) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_submissions');

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT mr_ref, accepted_at FROM {$table} WHERE id = %d",
            $mr_id
        )
    );

    if (!$row || $row->mr_ref === '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'          => true,
        'replay'      => true,
        'mr_ref'      => (string) $row->mr_ref,
        'accepted_at' => (string) $row->accepted_at,
    );
}

/**
 * Insert a row that includes a generated opaque ref, retrying collisions.
 *
 * @param string               $table    Prefixed table name.
 * @param array<string, mixed> $data     Column values excluding the ref column.
 * @param string               $ref_column Ref column name.
 * @param string[]             $formats  Formats for $data columns only.
 * @return int|false New id or false.
 */
function noelclark_v1_mail_room_insert_with_opaque_ref($table, $data, $ref_column, $formats) {
    global $wpdb;

    for ($attempt = 0; $attempt < NOELCLARK_V1_MAIL_ROOM_REF_INSERT_ATTEMPTS; $attempt++) {
        $row      = $data;
        $row_fmt  = $formats;
        $row[$ref_column] = noelclark_v1_mail_room_generate_opaque_ref();
        $row_fmt[]        = '%s';

        $inserted = $wpdb->insert($table, $row, $row_fmt);

        if ($inserted !== false) {
            return (int) $wpdb->insert_id;
        }

        if (!noelclark_v1_mail_room_is_duplicate_key_error((string) $wpdb->last_error)) {
            return false;
        }
    }

    return false;
}

/**
 * Persist one new accepted letter inside an already-open transaction.
 *
 * @param array<string, mixed> $payload Validated payload.
 * @param array{id: int, version_label: string} $legal Resolved Terms snapshot.
 * @param string $now GMT datetime.
 * @return array{ok: true, mr_id: int, mr_ref: string, accepted_at: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_persist_accepted_letter($payload, $legal, $now) {
    global $wpdb;

    $participants   = noelclark_v1_mail_room_table('participants');
    $mr_submissions = noelclark_v1_mail_room_table('mr_submissions');
    $mr_state       = noelclark_v1_mail_room_table('mr_state');
    $mr_events      = noelclark_v1_mail_room_table('mr_events');

    $participant_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $participants,
        array(
            'display_name' => $payload['display_name'],
            'email'        => $payload['email'],
            'age_attested' => $payload['age_attested'],
            'created_at'   => $now,
        ),
        'participant_ref',
        array('%s', '%s', '%d', '%s')
    );

    if ($participant_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $mr_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $mr_submissions,
        array(
            'accepted_participant_id' => $participant_id,
            'letter_body'             => $payload['letter_body'],
            'sharing_choice'          => $payload['sharing_choice'],
            'public_credit'           => $payload['public_credit'],
            'age_attested'            => $payload['age_attested'],
            'legal_document_id'       => $legal['id'],
            'legal_document_version'  => $legal['version_label'],
            'terms_accepted_at'       => $now,
            'accepted_at'             => $now,
            'created_at'              => $now,
        ),
        'mr_ref',
        array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
    );

    if ($mr_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $mr_ref = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT mr_ref FROM {$mr_submissions} WHERE id = %d",
            $mr_id
        )
    );

    if (!is_string($mr_ref) || $mr_ref === '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $state_inserted = $wpdb->insert(
        $mr_state,
        array(
            'mr_id'                     => $mr_id,
            'editorial_status'          => NOELCLARK_V1_MAIL_ROOM_EDITORIAL_STATUS_NEW,
            'operative_treatment'       => $payload['sharing_choice'],
            'operative_public_credit'   => $payload['public_credit'],
            'withdrawal_request_status' => NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_NONE,
            'updated_at'                => $now,
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s')
    );

    if ($state_inserted === false) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $event_payload = wp_json_encode(
        array(
            'sharing_choice' => $payload['sharing_choice'],
        )
    );

    if ($event_payload === false) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $event_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $mr_events,
        array(
            'mr_id'        => $mr_id,
            'event_type'   => NOELCLARK_V1_MAIL_ROOM_EVENT_ACCEPTED,
            'payload_json' => $event_payload,
            'created_at'   => $now,
        ),
        'event_ref',
        array('%d', '%s', '%s', '%s')
    );

    if ($event_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'          => true,
        'mr_id'       => $mr_id,
        'mr_ref'      => $mr_ref,
        'accepted_at' => $now,
    );
}

/**
 * Accept one Mail Room letter into the private store.
 *
 * Unhooked internal service. Returns a real mr_ref only after COMMIT.
 *
 * @param array<string, mixed> $input Internal acceptance payload.
 * @return array{ok: true, replay: bool, mr_ref: string, accepted_at: string, association_token: string|null}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_accept_letter($input) {
    global $wpdb;

    $validated = noelclark_v1_mail_room_validate_letter_accept($input);

    if (empty($validated['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($validated['code']) ? $validated['code'] : 'validation_error',
        );
    }

    $payload = $validated['payload'];

    $legal = noelclark_v1_mail_room_resolve_submission_terms($payload['legal_document_version']);

    if (empty($legal['ok'])) {
        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    $now = current_time('mysql', true);

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $claimed = noelclark_v1_mail_room_idempotency_claim($payload['idempotency_key'], $now);

    if ($claimed === 'duplicate') {
        $existing = noelclark_v1_mail_room_idempotency_lock($payload['idempotency_key']);

        if (!$existing || (int) $existing->mr_id < 1) {
            $wpdb->query('ROLLBACK');

            return array(
                'ok'   => false,
                'code' => 'storage_error',
            );
        }

        $replay = noelclark_v1_mail_room_replay_accepted_result((int) $existing->mr_id);

        if (empty($replay['ok'])) {
            $wpdb->query('ROLLBACK');

            return array(
                'ok'   => false,
                'code' => 'storage_error',
            );
        }

        $committed = $wpdb->query('COMMIT');

        if ($committed === false) {
            $wpdb->query('ROLLBACK');

            return array(
                'ok'   => false,
                'code' => 'storage_error',
            );
        }

        noelclark_v1_mail_room_ensure_notification_job((int) $existing->mr_id);

        $association_token = noelclark_v1_mail_room_ensure_association_token((int) $existing->mr_id);
        $replay['association_token'] = (is_string($association_token) && $association_token !== '')
            ? $association_token
            : null;

        return $replay;
    }

    if ($claimed !== true) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $legal_locked = noelclark_v1_mail_room_resolve_submission_terms($payload['legal_document_version']);

    if (empty($legal_locked['ok']) || (int) $legal_locked['id'] !== (int) $legal['id']) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    $persisted = noelclark_v1_mail_room_persist_accepted_letter($payload, $legal_locked, $now);

    if (empty($persisted['ok'])) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (!noelclark_v1_mail_room_idempotency_bind($payload['idempotency_key'], $persisted['mr_id'])) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    noelclark_v1_mail_room_ensure_notification_job((int) $persisted['mr_id']);

    $association_token = noelclark_v1_mail_room_ensure_association_token((int) $persisted['mr_id']);

    return array(
        'ok'                 => true,
        'replay'             => false,
        'mr_ref'             => $persisted['mr_ref'],
        'accepted_at'        => $persisted['accepted_at'],
        'association_token'  => (is_string($association_token) && $association_token !== '')
            ? $association_token
            : null,
    );
}
