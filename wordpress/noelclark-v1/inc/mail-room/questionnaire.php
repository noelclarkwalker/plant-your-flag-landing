<?php
/**
 * Mail Room post-accept questionnaire acceptance — Slice 4.
 *
 * Internal, unhooked, transactional. No REST, AJAX, SEND, admin, or WP-CLI.
 * Failure never mutates an already-accepted letter.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Operational status that may receive new responses. */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_STATUS_ACTIVE = 'active';

/**
 * Privacy-safe operational log. No answers or writer PII.
 *
 * @param string $class Stable class.
 * @return void
 */
function noelclark_v1_mail_room_questionnaire_log($class) {
    error_log('mail_room_questionnaire ' . $class);
}

/**
 * Best-effort ROLLBACK.
 *
 * @return void
 */
function noelclark_v1_mail_room_questionnaire_rollback() {
    global $wpdb;

    $wpdb->query('ROLLBACK');
}

/**
 * Allowed response ids keyed by question id from a frozen questions_json.
 *
 * Expected shape: JSON array of {id, responses:[{id, ...}, ...]}.
 *
 * @param string $questions_json Frozen version snapshot.
 * @return array<string, array<string, true>>|false
 */
function noelclark_v1_mail_room_questionnaire_allowed_from_json($questions_json) {
    if (!is_string($questions_json) || $questions_json === '') {
        return false;
    }

    $decoded = json_decode($questions_json, true);

    if (!is_array($decoded)) {
        return false;
    }

    if ($decoded === array()) {
        return array();
    }

    if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
        return false;
    }

    $allowed = array();

    foreach ($decoded as $question) {
        if (!is_array($question)) {
            return false;
        }

        if (!isset($question['id']) || !is_string($question['id']) || $question['id'] === '') {
            return false;
        }

        $question_id = $question['id'];

        if (isset($allowed[$question_id])) {
            return false;
        }

        if (!isset($question['responses']) || !is_array($question['responses'])) {
            return false;
        }

        $allowed[$question_id] = array();

        foreach ($question['responses'] as $response) {
            if (!is_array($response)) {
                return false;
            }

            if (!isset($response['id']) || !is_string($response['id']) || $response['id'] === '') {
                return false;
            }

            $response_id = $response['id'];

            if (isset($allowed[$question_id][$response_id])) {
                return false;
            }

            $allowed[$question_id][$response_id] = true;
        }
    }

    return $allowed;
}

/**
 * Normalize and validate submitted answers against one frozen version.
 *
 * @param mixed                         $answers Caller answers value.
 * @param array<string, array<string, true>> $allowed Allowed ids.
 * @return array{ok: true, json: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_questionnaire_normalize_answers($answers, $allowed) {
    if (!is_array($answers)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $stored = array();

    foreach ($answers as $question_id => $value) {
        if (!is_string($question_id) && !is_int($question_id)) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        $question_id = (string) $question_id;

        if ($question_id === '' || !isset($allowed[$question_id])) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        if ($value === null) {
            continue;
        }

        if (!is_string($value)) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        $value = wp_unslash($value);

        if ($value === '') {
            continue;
        }

        if (!isset($allowed[$question_id][$value])) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        $stored[$question_id] = $value;
    }

    $json = wp_json_encode((object) $stored);

    if (!is_string($json) || $json === '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'   => true,
        'json' => $json,
    );
}

/**
 * Resolve an exact active questionnaire and exact version. Never “latest.”
 *
 * @param string $questionnaire_ref Caller questionnaire_ref.
 * @param string $version_label     Caller version_label.
 * @return array{
 *   ok: true,
 *   questionnaire_id: int,
 *   version_id: int,
 *   questions_json: string,
 *   legal_document_id: int|null
 * }|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_questionnaire_resolve_version($questionnaire_ref, $version_label) {
    global $wpdb;

    $questionnaires = noelclark_v1_mail_room_table('questionnaires');
    $versions       = noelclark_v1_mail_room_table('questionnaire_versions');

    $definitions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, status FROM {$questionnaires} WHERE questionnaire_ref = %s",
            $questionnaire_ref
        )
    );

    if (!is_array($definitions) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($definitions) !== 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    if ((string) $definitions[0]->status !== NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_STATUS_ACTIVE) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $questionnaire_id = (int) $definitions[0]->id;

    if ($questionnaire_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $version_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, questionnaire_id, version_label, questions_json, legal_document_id "
            . "FROM {$versions} WHERE questionnaire_id = %d AND version_label = %s",
            $questionnaire_id,
            $version_label
        )
    );

    if (!is_array($version_rows) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($version_rows) !== 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $version = $version_rows[0];

    if (
        (int) $version->id < 1
        || (int) $version->questionnaire_id !== $questionnaire_id
        || (string) $version->version_label !== $version_label
    ) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $legal_document_id = $version->legal_document_id;

    if ($legal_document_id === null || (string) $legal_document_id === '') {
        $legal_document_id = null;
    } else {
        $legal_document_id = (int) $legal_document_id;

        if ($legal_document_id < 1) {
            return array(
                'ok'   => false,
                'code' => 'questionnaire_unavailable',
            );
        }
    }

    return array(
        'ok'                => true,
        'questionnaire_id'  => $questionnaire_id,
        'version_id'        => (int) $version->id,
        'questions_json'    => (string) $version->questions_json,
        'legal_document_id' => $legal_document_id,
    );
}

/**
 * Resolve the exact legal snapshot named by a questionnaire version.
 *
 * @param int|null $legal_document_id Version legal_document_id.
 * @return array{ok: true, id: int|null, version_label: string|null}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_questionnaire_resolve_legal($legal_document_id) {
    global $wpdb;

    if ($legal_document_id === null) {
        return array(
            'ok'            => true,
            'id'            => null,
            'version_label' => null,
        );
    }

    $legal_document_id = (int) $legal_document_id;

    if ($legal_document_id < 1) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $table = noelclark_v1_mail_room_table('legal_documents');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, version_label FROM {$table} WHERE id = %d",
            $legal_document_id
        )
    );

    if (!is_array($rows) || $wpdb->last_error !== '') {
        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (count($rows) !== 1 || (int) $rows[0]->id !== $legal_document_id) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $version_label = (string) $rows[0]->version_label;

    if ($version_label === '') {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    return array(
        'ok'            => true,
        'id'            => $legal_document_id,
        'version_label' => $version_label,
    );
}

/**
 * Validate the unhooked questionnaire-accept payload and freeze snapshots.
 *
 * @param mixed $input Raw internal input.
 * @return array{
 *   ok: true,
 *   association_token: string,
 *   version_id: int,
 *   answers_json: string,
 *   legal_id: int|null,
 *   legal_version: string|null
 * }|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_questionnaire_validate_input($input) {
    if (!is_array($input)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!array_key_exists('association_token', $input) || !is_string($input['association_token'])) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $association_token = $input['association_token'];

    if ($association_token === '' || noelclark_v1_mail_room_has_nul($association_token)) {
        return array(
            'ok'   => false,
            'code' => 'association_invalid',
        );
    }

    $questionnaire_ref = noelclark_v1_mail_room_unslash_string($input, 'questionnaire_ref');
    $version_label     = noelclark_v1_mail_room_unslash_string($input, 'version_label');

    if ($questionnaire_ref === false || $version_label === false) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $questionnaire_ref = trim($questionnaire_ref);
    $version_label     = trim($version_label);

    if (
        $questionnaire_ref === ''
        || $version_label === ''
        || noelclark_v1_mail_room_has_nul($questionnaire_ref)
        || noelclark_v1_mail_room_has_nul($version_label)
        || noelclark_v1_mail_room_has_crlf($questionnaire_ref)
        || noelclark_v1_mail_room_has_crlf($version_label)
        || noelclark_v1_mail_room_text_length($questionnaire_ref) > 64
        || noelclark_v1_mail_room_text_length($version_label) > 64
    ) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!array_key_exists('answers', $input)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $resolved = noelclark_v1_mail_room_questionnaire_resolve_version($questionnaire_ref, $version_label);

    if (empty($resolved['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($resolved['code']) ? $resolved['code'] : 'questionnaire_unavailable',
        );
    }

    $allowed = noelclark_v1_mail_room_questionnaire_allowed_from_json($resolved['questions_json']);

    if ($allowed === false) {
        return array(
            'ok'   => false,
            'code' => 'questionnaire_unavailable',
        );
    }

    $normalized = noelclark_v1_mail_room_questionnaire_normalize_answers($input['answers'], $allowed);

    if (empty($normalized['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($normalized['code']) ? $normalized['code'] : 'validation_error',
        );
    }

    $legal = noelclark_v1_mail_room_questionnaire_resolve_legal($resolved['legal_document_id']);

    if (empty($legal['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($legal['code']) ? $legal['code'] : 'questionnaire_unavailable',
        );
    }

    return array(
        'ok'                 => true,
        'association_token'  => $association_token,
        'version_id'         => (int) $resolved['version_id'],
        'answers_json'       => $normalized['json'],
        'legal_id'           => $legal['id'],
        'legal_version'      => $legal['version_label'],
    );
}

/**
 * Load the accepted letter named by a token. Does not lock or mutate the MR.
 *
 * @param int $mr_id Token mr_id.
 * @return object|null
 */
function noelclark_v1_mail_room_questionnaire_load_mr($mr_id) {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_submissions');

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, accepted_participant_id, age_attested FROM {$table} WHERE id = %d",
            (int) $mr_id
        )
    );
}

/**
 * Accept one post-letter questionnaire response into the private store.
 *
 * Unhooked internal service. Returns a real q_ref only after COMMIT.
 *
 * @param array<string, mixed> $input Internal acceptance payload.
 * @return array{ok: true, replay: false, q_ref: string, accepted_at: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_accept_questionnaire($input) {
    global $wpdb;

    $validated = noelclark_v1_mail_room_questionnaire_validate_input($input);

    if (empty($validated['ok'])) {
        return array(
            'ok'   => false,
            'code' => isset($validated['code']) ? $validated['code'] : 'validation_error',
        );
    }

    $now = current_time('mysql', true);

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_questionnaire_log('start_failed');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $hash = noelclark_v1_mail_room_association_hash($validated['association_token']);
    $lock = noelclark_v1_mail_room_association_lock_hash($hash);

    if (empty($lock['ok'])) {
        noelclark_v1_mail_room_questionnaire_rollback();

        return array(
            'ok'   => false,
            'code' => isset($lock['code']) ? $lock['code'] : 'association_invalid',
        );
    }

    $token_fail = noelclark_v1_mail_room_association_token_failure($lock['row'], $now);

    if ($token_fail !== null) {
        noelclark_v1_mail_room_questionnaire_rollback();

        return array(
            'ok'   => false,
            'code' => $token_fail,
        );
    }

    $mr_id = (int) $lock['row']->mr_id;
    $mr    = noelclark_v1_mail_room_questionnaire_load_mr($mr_id);

    if ($mr === null && $wpdb->last_error !== '') {
        noelclark_v1_mail_room_questionnaire_rollback();
        noelclark_v1_mail_room_questionnaire_log('storage_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (!$mr || (int) $mr->id !== $mr_id || (int) $mr->accepted_participant_id < 1) {
        noelclark_v1_mail_room_questionnaire_rollback();

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $participant_id = (int) $mr->accepted_participant_id;
    $age_attested   = ((int) $mr->age_attested) ? 1 : 0;
    $q_responses    = noelclark_v1_mail_room_table('q_responses');

    $row = array(
        'accepted_participant_id'  => $participant_id,
        'questionnaire_version_id' => (int) $validated['version_id'],
        'associated_mr_id'         => $mr_id,
        'answers_json'             => $validated['answers_json'],
        'age_attested'             => $age_attested,
        'accepted_at'              => $now,
        'created_at'               => $now,
    );
    $formats = array('%d', '%d', '%d', '%s', '%d', '%s', '%s');

    if ($validated['legal_id'] !== null) {
        $row['legal_document_id']      = (int) $validated['legal_id'];
        $row['legal_document_version'] = (string) $validated['legal_version'];
        $formats[]                     = '%d';
        $formats[]                     = '%s';
    }

    $q_id = noelclark_v1_mail_room_insert_with_opaque_ref(
        $q_responses,
        $row,
        'q_ref',
        $formats
    );

    if ($q_id < 1) {
        noelclark_v1_mail_room_questionnaire_rollback();
        noelclark_v1_mail_room_questionnaire_log('storage_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $q_ref = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT q_ref FROM {$q_responses} WHERE id = %d",
            $q_id
        )
    );

    if (!is_string($q_ref) || $q_ref === '') {
        noelclark_v1_mail_room_questionnaire_rollback();
        noelclark_v1_mail_room_questionnaire_log('storage_error');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    if (!noelclark_v1_mail_room_association_consume((int) $lock['row']->id, $now)) {
        noelclark_v1_mail_room_questionnaire_rollback();

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    $committed = $wpdb->query('COMMIT');

    if ($committed === false) {
        noelclark_v1_mail_room_questionnaire_rollback();
        noelclark_v1_mail_room_questionnaire_log('commit_failed');

        return array(
            'ok'   => false,
            'code' => 'storage_error',
        );
    }

    return array(
        'ok'          => true,
        'replay'      => false,
        'q_ref'       => $q_ref,
        'accepted_at' => $now,
    );
}
