<?php
/**
 * Mail Room letter-accept validation — Slice 2 internal service only.
 *
 * No REST, AJAX, or SEND wiring lives here.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Maximum UTF-8 characters for the original letter. */
const NOELCLARK_V1_MAIL_ROOM_LETTER_MAX = 50000;

/** @var int Maximum UTF-8 characters for private display_name. */
const NOELCLARK_V1_MAIL_ROOM_DISPLAY_NAME_MAX = 200;

/** @var int Maximum UTF-8 characters for email. */
const NOELCLARK_V1_MAIL_ROOM_EMAIL_MAX = 254;

/** @var int Maximum UTF-8 characters for stored Public Credit. */
const NOELCLARK_V1_MAIL_ROOM_PUBLIC_CREDIT_MAX = 200;

/** @var int Maximum characters for legal_document_version / version_label. */
const NOELCLARK_V1_MAIL_ROOM_LEGAL_VERSION_MAX = 64;

/** @var int Minimum idempotency key characters. */
const NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_KEY_MIN = 16;

/** @var int Maximum idempotency key characters. */
const NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_KEY_MAX = 64;

/**
 * Stored sharing choices. No fourth value.
 *
 * @return string[]
 */
function noelclark_v1_mail_room_allowed_sharing_choices() {
    return array(
        'not_public',
        'anonymous',
        'named_credit',
    );
}

/**
 * Payload keys that mean a questionnaire was included. Reject if present.
 *
 * @return string[]
 */
function noelclark_v1_mail_room_forbidden_question_keys() {
    return array(
        'answers',
        'answers_json',
        'questions',
        'optionalResponses',
        'optional_responses',
        'q_responses',
        'questionnaire',
        'questionnaire_version_id',
    );
}

/**
 * UTF-8 character length for bounded fields.
 *
 * @param string $value Text to measure.
 * @return int
 */
function noelclark_v1_mail_room_text_length($value) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    if (preg_match_all('/./us', $value, $matches)) {
        return count($matches[0]);
    }

    return strlen($value);
}

/**
 * Whether the string contains a CR or LF.
 *
 * @param string $value Raw value.
 * @return bool
 */
function noelclark_v1_mail_room_has_crlf($value) {
    return (bool) preg_match('/[\r\n]/', $value);
}

/**
 * Whether the string contains a NUL byte.
 *
 * @param string $value Raw value.
 * @return bool
 */
function noelclark_v1_mail_room_has_nul($value) {
    return strpos($value, "\0") !== false;
}

/**
 * Whether the string contains any non-whitespace character.
 *
 * Used as an emptiness test only. Does not produce a stored representation.
 *
 * @param string $value Unslashed value.
 * @return bool
 */
function noelclark_v1_mail_room_has_non_whitespace($value) {
    return (bool) preg_match('/\S/u', $value);
}

/**
 * Whether the caller supplied a true attestation / acceptance flag.
 *
 * @param mixed $value Raw input.
 * @return bool
 */
function noelclark_v1_mail_room_is_affirmed($value) {
    return $value === true || $value === 1 || $value === '1';
}

/**
 * Unslash a string field. Missing keys become an empty string.
 * A supplied non-string value is rejected.
 *
 * @param array<string, mixed> $input Raw input.
 * @param string               $key   Field name.
 * @return string|false false when the supplied value is not a string.
 */
function noelclark_v1_mail_room_unslash_string($input, $key) {
    if (!array_key_exists($key, $input)) {
        return '';
    }

    if (!is_string($input[$key])) {
        return false;
    }

    return wp_unslash($input[$key]);
}

/**
 * Validate an internal Mail Room letter-accept payload.
 *
 * Does not write. Does not resolve legal documents.
 *
 * @param array<string, mixed> $input Raw internal input.
 * @return array{ok: true, payload: array<string, mixed>}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_validate_letter_accept($input) {
    if (!is_array($input)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    foreach (noelclark_v1_mail_room_forbidden_question_keys() as $forbidden) {
        if (array_key_exists($forbidden, $input)) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }
    }

    $idempotency_key = noelclark_v1_mail_room_unslash_string($input, 'idempotency_key');
    $letter_body     = noelclark_v1_mail_room_unslash_string($input, 'letter_body');
    $sharing_choice  = noelclark_v1_mail_room_unslash_string($input, 'sharing_choice');
    $public_credit   = noelclark_v1_mail_room_unslash_string($input, 'public_credit');
    $display_name    = noelclark_v1_mail_room_unslash_string($input, 'display_name');
    $email           = noelclark_v1_mail_room_unslash_string($input, 'email');

    if (
        $idempotency_key === false
        || $letter_body === false
        || $sharing_choice === false
        || $public_credit === false
        || $display_name === false
        || $email === false
    ) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!array_key_exists('legal_document_version', $input) || !is_string($input['legal_document_version'])) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $display_name  = trim($display_name);
    $email         = trim($email);
    $legal_version = trim(wp_unslash($input['legal_document_version']));

    if (!preg_match('/^[A-Za-z0-9._-]+$/', $idempotency_key)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $key_length = noelclark_v1_mail_room_text_length($idempotency_key);

    if (
        $key_length < NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_KEY_MIN
        || $key_length > NOELCLARK_V1_MAIL_ROOM_IDEMPOTENCY_KEY_MAX
    ) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!noelclark_v1_mail_room_is_affirmed(isset($input['age_attested']) ? $input['age_attested'] : false)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!noelclark_v1_mail_room_is_affirmed(isset($input['terms_accepted']) ? $input['terms_accepted'] : false)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_mail_room_has_nul($letter_body)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!noelclark_v1_mail_room_has_non_whitespace($letter_body)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_mail_room_text_length($letter_body) > NOELCLARK_V1_MAIL_ROOM_LETTER_MAX) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!in_array($sharing_choice, noelclark_v1_mail_room_allowed_sharing_choices(), true)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_mail_room_has_crlf($public_credit) || noelclark_v1_mail_room_has_nul($public_credit)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $credit_has_content = noelclark_v1_mail_room_has_non_whitespace($public_credit);

    if ($sharing_choice === 'named_credit') {
        if (!$credit_has_content) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        if (noelclark_v1_mail_room_text_length($public_credit) > NOELCLARK_V1_MAIL_ROOM_PUBLIC_CREDIT_MAX) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        $stored_public_credit = $public_credit;
    } else {
        if ($credit_has_content) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }

        $stored_public_credit = null;
    }

    if ($display_name === '' || noelclark_v1_mail_room_has_crlf($display_name) || noelclark_v1_mail_room_has_nul($display_name)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_mail_room_text_length($display_name) > NOELCLARK_V1_MAIL_ROOM_DISPLAY_NAME_MAX) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (
        $email === ''
        || noelclark_v1_mail_room_has_crlf($email)
        || noelclark_v1_mail_room_has_nul($email)
        || noelclark_v1_mail_room_text_length($email) > NOELCLARK_V1_MAIL_ROOM_EMAIL_MAX
        || !is_email($email)
    ) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (
        $legal_version === ''
        || noelclark_v1_mail_room_has_crlf($legal_version)
        || noelclark_v1_mail_room_has_nul($legal_version)
        || noelclark_v1_mail_room_text_length($legal_version) > NOELCLARK_V1_MAIL_ROOM_LEGAL_VERSION_MAX
    ) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    return array(
        'ok'      => true,
        'payload' => array(
            'idempotency_key'         => $idempotency_key,
            'letter_body'             => $letter_body,
            'sharing_choice'          => $sharing_choice,
            'public_credit'           => $stored_public_credit,
            'display_name'            => $display_name,
            'email'                   => $email,
            'legal_document_version'  => $legal_version,
            'age_attested'            => 1,
        ),
    );
}
