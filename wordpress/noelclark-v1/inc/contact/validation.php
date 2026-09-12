<?php
/**
 * Contact form server-side validation — routing/validation slice only.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Maximum characters for the name field. */
const NOELCLARK_CONTACT_NAME_MAX = 100;

/** @var int Maximum characters for the email field. */
const NOELCLARK_CONTACT_EMAIL_MAX = 254;

/** @var int Maximum characters for the subject field. */
const NOELCLARK_CONTACT_SUBJECT_MAX = 200;

/** @var int Maximum characters for the message field. */
const NOELCLARK_CONTACT_MESSAGE_MAX = 5000;

/**
 * Approved Contact topic allowlist.
 *
 * @return string[]
 */
function noelclark_v1_contact_allowed_topics() {
    return array(
        'general',
        'press',
        'collaboration',
        'speaking',
        'rights',
        'website',
    );
}

/**
 * Count UTF-8 text length for validation limits.
 *
 * @param string $value Text to measure.
 * @return int
 */
function noelclark_v1_contact_text_length($value) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    if (preg_match_all('/./us', $value, $matches)) {
        return count($matches[0]);
    }

    return strlen($value);
}

/**
 * Reject values that contain CR/LF header-injection patterns.
 *
 * @param string $value Raw field value.
 * @return bool
 */
function noelclark_v1_contact_has_header_injection($value) {
    return (bool) preg_match('/[\r\n]/', $value);
}

/**
 * Validate a Contact POST payload for the routing/validation slice.
 *
 * Does not persist submitted content. Returns a non-sensitive error code on failure.
 *
 * @param array<string, mixed> $post Raw POST data.
 * @return array{ok: true, payload: array{name: string, email: string, topic: string, subject: string, message: string}}|array{ok: false, code: string}
 */
function noelclark_v1_contact_validate_submission($post) {
    $name    = isset($post['contact_name']) ? sanitize_text_field(wp_unslash($post['contact_name'])) : '';
    $email   = isset($post['email']) ? sanitize_email(wp_unslash($post['email'])) : '';
    $topic   = isset($post['topic']) ? sanitize_key(wp_unslash($post['topic'])) : '';
    $subject = isset($post['subject']) ? sanitize_text_field(wp_unslash($post['subject'])) : '';
    $message = isset($post['message']) ? sanitize_textarea_field(wp_unslash($post['message'])) : '';

    if ($name === '' || $email === '' || $topic === '' || $subject === '' || $message === '') {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_contact_text_length($name) > NOELCLARK_CONTACT_NAME_MAX) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_contact_text_length($email) > NOELCLARK_CONTACT_EMAIL_MAX || !is_email($email)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (!in_array($topic, noelclark_v1_contact_allowed_topics(), true)) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_contact_text_length($subject) > NOELCLARK_CONTACT_SUBJECT_MAX) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    if (noelclark_v1_contact_text_length($message) > NOELCLARK_CONTACT_MESSAGE_MAX) {
        return array(
            'ok'   => false,
            'code' => 'validation_error',
        );
    }

    $injection_fields = array(
        'name'    => isset($post['contact_name']) ? (string) wp_unslash($post['contact_name']) : '',
        'email'   => isset($post['email']) ? (string) wp_unslash($post['email']) : '',
        'subject' => isset($post['subject']) ? (string) wp_unslash($post['subject']) : '',
    );

    foreach ($injection_fields as $value) {
        if (noelclark_v1_contact_has_header_injection($value)) {
            return array(
                'ok'   => false,
                'code' => 'validation_error',
            );
        }
    }

    return array(
        'ok'      => true,
        'payload' => array(
            'name'    => $name,
            'email'   => $email,
            'topic'   => $topic,
            'subject' => $subject,
            'message' => $message,
        ),
    );
}
