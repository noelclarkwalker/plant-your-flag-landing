<?php
/**
 * Contact form mail handoff — Development Phase 3A slice only.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Human-readable labels for approved Contact topics in outbound mail.
 *
 * @param string $topic Sanitized topic key.
 * @return string
 */
function noelclark_v1_contact_topic_label($topic) {
    $labels = array(
        'general'       => 'General Inquiry',
        'press'         => 'Press & Media',
        'collaboration' => 'Collaboration / Business',
        'speaking'      => 'Speaking & Events',
        'rights'        => 'Rights & Permissions',
        'website'       => 'NoelClark.com / Website',
    );

    return $labels[$topic] ?? $topic;
}

/**
 * Resolve the private Contact recipient from server-side configuration.
 *
 * @return string Empty string when unavailable or invalid.
 */
function noelclark_v1_contact_get_recipient() {
    if (!defined('NOELCLARK_CONTACT_RECIPIENT')) {
        return '';
    }

    $recipient = NOELCLARK_CONTACT_RECIPIENT;

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
 * Hand off a validated Contact submission to WordPress mail.
 *
 * Accepts only the sanitized payload produced by validation. Does not persist
 * message content. wp_mail() true indicates WordPress accepted the handoff only.
 *
 * @param array<string, string> $payload Validated Contact payload.
 * @return array{ok: true, code: 'mail_handoff_ok'}|array{ok: false, code: 'mail_handoff_error'}
 */
function noelclark_v1_contact_send_handoff($payload) {
    $required = array('name', 'email', 'topic', 'subject', 'message');

    foreach ($required as $field) {
        if (!isset($payload[$field]) || !is_string($payload[$field]) || $payload[$field] === '') {
            return array(
                'ok'   => false,
                'code' => 'mail_handoff_error',
            );
        }
    }

    $recipient = noelclark_v1_contact_get_recipient();

    if ($recipient === '') {
        return array(
            'ok'   => false,
            'code' => 'mail_handoff_error',
        );
    }

    $topic_label = noelclark_v1_contact_topic_label($payload['topic']);

    $mail_subject = sprintf(
        '[NoelClark.com Contact] %s',
        $payload['subject']
    );

    $mail_body = implode(
        "\n",
        array(
            'A Contact form submission was received on NoelClark.com.',
            '',
            'Name: ' . $payload['name'],
            'Email: ' . $payload['email'],
            'Topic: ' . $topic_label,
            'Subject: ' . $payload['subject'],
            '',
            'Message:',
            $payload['message'],
        )
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $payload['email'],
    );

    $sent = wp_mail($recipient, $mail_subject, $mail_body, $headers);

    if (!$sent) {
        return array(
            'ok'   => false,
            'code' => 'mail_handoff_error',
        );
    }

    return array(
        'ok'   => true,
        'code' => 'mail_handoff_ok',
    );
}
