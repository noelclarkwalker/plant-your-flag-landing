<?php
/**
 * One-time Development seed for Mail Room Submission Terms — NOT auto-run.
 *
 * Status: pre-counsel working Development snapshot.
 * Do not treat as counsel-approved. Do not run against Production without
 * separate explicit authorization.
 *
 * This file is intentionally NOT required from functions.php and is NOT
 * hooked to init. Invoke only after explicit authorization, e.g.:
 *
 *   wp eval-file wp-content/themes/noelclark-v1/inc/mail-room/seed-submission-terms-dev.php
 *
 * or a one-time authorized bootstrap that calls:
 *   noelclark_v1_mail_room_seed_submission_terms_dev1()
 *
 * Expected row (matches submit.php resolver):
 *   doc_type      = mail_room_submission_terms
 *   version_label = mail-room-submission-terms-2026-09-17-dev1
 *   body_snapshot = exact contents of MAIL_ROOM_SUBMISSION_TERMS.md beside this file
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Development Terms version label for accept_letter legal_document_version. */
const NOELCLARK_V1_MAIL_ROOM_SUBMISSION_TERMS_DEV1_VERSION = 'mail-room-submission-terms-2026-09-17-dev1';

/**
 * Absolute path to the Development Terms markdown snapshot.
 *
 * @return string
 */
function noelclark_v1_mail_room_submission_terms_dev1_source_path() {
    return __DIR__ . '/MAIL_ROOM_SUBMISSION_TERMS.md';
}

/**
 * Load exact Terms body for the Development snapshot.
 *
 * @return array{ok: true, body: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_submission_terms_dev1_load_body() {
    $path = noelclark_v1_mail_room_submission_terms_dev1_source_path();

    if (!is_readable($path)) {
        return array(
            'ok'   => false,
            'code' => 'terms_source_unreadable',
        );
    }

    $body = file_get_contents($path);

    if (!is_string($body) || $body === '') {
        return array(
            'ok'   => false,
            'code' => 'terms_source_empty',
        );
    }

    // Normalize to LF only so accidental CRLF from transfer does not fork snapshots.
    $body = str_replace(array("\r\n", "\r"), "\n", $body);

    return array(
        'ok'   => true,
        'body' => $body,
    );
}

/**
 * Idempotent one-time seed of the Development Submission Terms snapshot.
 *
 * Fail-closed:
 * - Missing/unreadable source → error
 * - Existing row with different body_snapshot → error (immutable; no overwrite)
 * - Existing row with identical body → success, no insert (idempotent)
 * - Missing row → INSERT once
 *
 * Duplicate (doc_type, version_label) is also blocked by UNIQUE KEY
 * doc_type_version on wp_nc_legal_documents.
 *
 * @return array{ok: true, status: string, id: int, version_label: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_seed_submission_terms_dev1() {
    global $wpdb;

    if (!defined('NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE')) {
        return array(
            'ok'   => false,
            'code' => 'mail_room_submit_not_loaded',
        );
    }

    $loaded = noelclark_v1_mail_room_submission_terms_dev1_load_body();

    if (empty($loaded['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($loaded['code']) ? $loaded['code'] : 'terms_source_error',
        );
    }

    $body          = $loaded['body'];
    $doc_type      = NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE;
    $version_label = NOELCLARK_V1_MAIL_ROOM_SUBMISSION_TERMS_DEV1_VERSION;
    $table         = noelclark_v1_mail_room_table('legal_documents');

    $existing = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, body_snapshot FROM {$table} WHERE doc_type = %s AND version_label = %s",
            $doc_type,
            $version_label
        )
    );

    if (!is_array($existing)) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($existing) > 1) {
        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    if (count($existing) === 1) {
        $row_body = (string) $existing[0]->body_snapshot;
        $row_body = str_replace(array("\r\n", "\r"), "\n", $row_body);

        if ($row_body !== $body) {
            return array(
                'ok'   => false,
                'code' => 'terms_snapshot_conflict',
            );
        }

        return array(
            'ok'            => true,
            'status'        => 'already_present',
            'id'            => (int) $existing[0]->id,
            'version_label' => $version_label,
        );
    }

    $now = current_time('mysql', true);

    $inserted = $wpdb->insert(
        $table,
        array(
            'doc_type'      => $doc_type,
            'version_label' => $version_label,
            'body_snapshot' => $body,
            'released_at'   => $now,
            'created_at'    => $now,
        ),
        array('%s', '%s', '%s', '%s', '%s')
    );

    if ($inserted === false) {
        // Race: another process inserted the same UNIQUE key — re-read and verify body.
        $again = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, body_snapshot FROM {$table} WHERE doc_type = %s AND version_label = %s",
                $doc_type,
                $version_label
            )
        );

        if (is_array($again) && count($again) === 1) {
            $row_body = str_replace(array("\r\n", "\r"), "\n", (string) $again[0]->body_snapshot);

            if ($row_body === $body) {
                return array(
                    'ok'            => true,
                    'status'        => 'already_present',
                    'id'            => (int) $again[0]->id,
                    'version_label' => $version_label,
                );
            }

            return array(
                'ok'   => false,
                'code' => 'terms_snapshot_conflict',
            );
        }

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $id = (int) $wpdb->insert_id;

    if ($id < 1) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    // Confirm exactly one matching row (fail-closed parity with resolver).
    $verify = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE doc_type = %s AND version_label = %s",
            $doc_type,
            $version_label
        )
    );

    if (!is_array($verify) || count($verify) !== 1 || (int) $verify[0]->id !== $id) {
        return array(
            'ok'   => false,
            'code' => 'legal_document_unavailable',
        );
    }

    return array(
        'ok'            => true,
        'status'        => 'inserted',
        'id'            => $id,
        'version_label' => $version_label,
    );
}
