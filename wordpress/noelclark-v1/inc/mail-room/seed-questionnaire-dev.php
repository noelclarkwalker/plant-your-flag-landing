<?php
/**
 * One-time Development seed for Mail Room optional questionnaire — NOT auto-run.
 *
 * Status: Development snapshot only.
 * Do not treat as Production-ready. Do not run against Production without
 * separate explicit authorization.
 *
 * This file is intentionally NOT required from functions.php and is NOT
 * hooked to init. Invoke only after explicit authorization, e.g.:
 *
 *   wp eval-file wp-content/themes/noelclark-v1/inc/mail-room/seed-questionnaire-dev.php
 *
 * or a one-time authorized bootstrap that calls:
 *   noelclark_v1_mail_room_seed_questionnaire_dev1()
 *
 * Expected rows:
 *   wp_nc_questionnaires:
 *     questionnaire_ref = mail-room-optional-questions
 *     status            = active
 *   wp_nc_questionnaire_versions:
 *     version_label     = mail-room-optional-questions-2026-09-17-dev1
 *     questions_json    = exact contents of mail-room-optional-questions-2026-09-17-dev1.json
 *     legal_document_id = NULL
 *
 * Source questions are the locked mq01–mq05 set from MAIL_ROOM_OPTIONAL_QUESTIONS.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Development questionnaire_ref. */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_REF = 'mail-room-optional-questions';

/** @var string Development questionnaire version_label. */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_VERSION = 'mail-room-optional-questions-2026-09-17-dev1';

/** @var string Development questionnaire title. */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_TITLE = 'Mail Room optional questions';

/**
 * Absolute path to the frozen Development questions_json snapshot.
 *
 * @return string
 */
function noelclark_v1_mail_room_questionnaire_dev1_source_path() {
    return __DIR__ . '/mail-room-optional-questions-2026-09-17-dev1.json';
}

/**
 * Load and validate exact questions_json for the Development snapshot.
 *
 * @return array{ok: true, json: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_questionnaire_dev1_load_questions_json() {
    $path = noelclark_v1_mail_room_questionnaire_dev1_source_path();

    if (!is_readable($path)) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_unreadable',
        );
    }

    $raw = file_get_contents($path);

    if (!is_string($raw) || $raw === '') {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_empty',
        );
    }

    $raw = str_replace(array("\r\n", "\r"), "\n", $raw);
    $raw = trim($raw);

    $decoded = json_decode($raw, true);

    if (!is_array($decoded) || $decoded === array()) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_invalid',
        );
    }

    if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_invalid',
        );
    }

    // Re-encode from decoded data so storage is a single compact JSON line
    // independent of source file whitespace/trailing newline.
    $json = wp_json_encode($decoded);

    if (!is_string($json) || $json === '') {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_invalid',
        );
    }

    if (
        function_exists('noelclark_v1_mail_room_questionnaire_allowed_from_json')
        && noelclark_v1_mail_room_questionnaire_allowed_from_json($json) === false
    ) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_source_invalid',
        );
    }

    return array(
        'ok'   => true,
        'json' => $json,
    );
}

/**
 * Normalize stored questions_json for exact comparison.
 *
 * @param string $json Stored or candidate JSON.
 * @return string|false
 */
function noelclark_v1_mail_room_questionnaire_dev1_normalize_json($json) {
    if (!is_string($json) || $json === '') {
        return false;
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return false;
    }

    $encoded = wp_json_encode($decoded);

    return is_string($encoded) && $encoded !== '' ? $encoded : false;
}

/**
 * Idempotent one-time seed of the Development optional questionnaire.
 *
 * Fail-closed:
 * - Missing/unreadable/invalid source → error
 * - Existing version with different questions_json → error (immutable; no overwrite)
 * - Existing matching rows → success, no insert (idempotent)
 * - Missing rows → INSERT once
 *
 * @return array{
 *   ok: true,
 *   status: string,
 *   questionnaire_id: int,
 *   version_id: int,
 *   questionnaire_ref: string,
 *   version_label: string
 * }|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_seed_questionnaire_dev1() {
    global $wpdb;

    if (!function_exists('noelclark_v1_mail_room_table')) {
        return array(
            'ok'   => false,
            'code' => 'mail_room_schema_not_loaded',
        );
    }

    $loaded = noelclark_v1_mail_room_questionnaire_dev1_load_questions_json();

    if (empty($loaded['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($loaded['code']) ? $loaded['code'] : 'questionnaire_source_error',
        );
    }

    $questions_json    = $loaded['json'];
    $questionnaire_ref = NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_REF;
    $version_label     = NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_VERSION;
    $questionnaires    = noelclark_v1_mail_room_table('questionnaires');
    $versions          = noelclark_v1_mail_room_table('questionnaire_versions');
    $now               = current_time('mysql', true);

    $existing_defs = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, status, title FROM {$questionnaires} WHERE questionnaire_ref = %s",
            $questionnaire_ref
        )
    );

    if (!is_array($existing_defs) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($existing_defs) > 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $questionnaire_id = 0;

    if (count($existing_defs) === 1) {
        $questionnaire_id = (int) $existing_defs[0]->id;

        if ($questionnaire_id < 1) {
            return array(
                'ok'   => false,
                'code' => 'storage_error',
            );
        }

        if ((string) $existing_defs[0]->status !== 'active') {
            return array(
                'ok'   => false,
                'code' => 'questionnaire_status_conflict',
            );
        }
    } else {
        $inserted_def = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$questionnaires} "
                . '(questionnaire_ref, title, purpose, status, created_at, updated_at) '
                . 'VALUES (%s, %s, NULL, %s, %s, %s)',
                $questionnaire_ref,
                NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_DEV1_TITLE,
                'active',
                $now,
                $now
            )
        );

        if ($inserted_def === false) {
            $again = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, status FROM {$questionnaires} WHERE questionnaire_ref = %s",
                    $questionnaire_ref
                )
            );

            if (is_array($again) && count($again) === 1 && (string) $again[0]->status === 'active') {
                $questionnaire_id = (int) $again[0]->id;
            } else {
                return array(
                    'ok'   => false,
                    'code' => 'storage_error',
                );
            }
        } else {
            $questionnaire_id = (int) $wpdb->insert_id;
        }

        if ($questionnaire_id < 1) {
            return array(
                'ok'   => false,
                'code' => 'storage_error',
            );
        }
    }

    $existing_versions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, questions_json, legal_document_id FROM {$versions} "
            . 'WHERE questionnaire_id = %d AND version_label = %s',
            $questionnaire_id,
            $version_label
        )
    );

    if (!is_array($existing_versions) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($existing_versions) > 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    if (count($existing_versions) === 1) {
        $stored_json = noelclark_v1_mail_room_questionnaire_dev1_normalize_json(
            (string) $existing_versions[0]->questions_json
        );

        if ($stored_json === false || $stored_json !== $questions_json) {
            return array(
                'ok'   => false,
                'code' => 'questionnaire_snapshot_conflict',
            );
        }

        $legal_id = $existing_versions[0]->legal_document_id;

        if ($legal_id !== null && (string) $legal_id !== '') {
            return array(
                'ok'   => false,
                'code' => 'questionnaire_legal_conflict',
            );
        }

        return array(
            'ok'                 => true,
            'status'             => 'already_present',
            'questionnaire_id'   => $questionnaire_id,
            'version_id'         => (int) $existing_versions[0]->id,
            'questionnaire_ref'  => $questionnaire_ref,
            'version_label'      => $version_label,
        );
    }

    // Explicit NULL for legal_document_id (Development decision — no consent doc yet).
    $inserted_version = $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$versions} "
            . '(questionnaire_id, version_label, intro_json, questions_json, legal_document_id, released_at, created_at) '
            . 'VALUES (%d, %s, NULL, %s, NULL, %s, %s)',
            $questionnaire_id,
            $version_label,
            $questions_json,
            $now,
            $now
        )
    );

    if ($inserted_version === false) {
        $again_v = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, questions_json, legal_document_id FROM {$versions} "
                . 'WHERE questionnaire_id = %d AND version_label = %s',
                $questionnaire_id,
                $version_label
            )
        );

        if (is_array($again_v) && count($again_v) === 1) {
            $stored_json = noelclark_v1_mail_room_questionnaire_dev1_normalize_json(
                (string) $again_v[0]->questions_json
            );

            if ($stored_json === $questions_json) {
                $legal_id = $again_v[0]->legal_document_id;

                if ($legal_id !== null && (string) $legal_id !== '') {
                    return array(
                        'ok'   => false,
                        'code' => 'questionnaire_legal_conflict',
                    );
                }

                return array(
                    'ok'                 => true,
                    'status'             => 'already_present',
                    'questionnaire_id'   => $questionnaire_id,
                    'version_id'         => (int) $again_v[0]->id,
                    'questionnaire_ref'  => $questionnaire_ref,
                    'version_label'      => $version_label,
                );
            }

            return array(
                'ok'   => false,
                'code' => 'questionnaire_snapshot_conflict',
            );
        }

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $version_id = (int) $wpdb->insert_id;

    if ($version_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $verify = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, legal_document_id FROM {$versions} WHERE questionnaire_id = %d AND version_label = %s",
            $questionnaire_id,
            $version_label
        )
    );

    if (
        !is_array($verify)
        || count($verify) !== 1
        || (int) $verify[0]->id !== $version_id
        || ($verify[0]->legal_document_id !== null && (string) $verify[0]->legal_document_id !== '')
    ) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    return array(
        'ok'                 => true,
        'status'             => 'inserted',
        'questionnaire_id'   => $questionnaire_id,
        'version_id'         => $version_id,
        'questionnaire_ref'  => $questionnaire_ref,
        'version_label'      => $version_label,
    );
}
