<?php
/**
 * Contact form POST handler — same-page POST + PRG.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Nonce action for the Contact form. */
const NOELCLARK_CONTACT_NONCE_ACTION = 'noelclark_contact_form';

/** @var string POST field name for the Contact form nonce. */
const NOELCLARK_CONTACT_NONCE_FIELD = 'noelclark_contact_nonce';

/** @var string POST field name for Contact submission intent. */
const NOELCLARK_CONTACT_INTENT_FIELD = 'contact_form';

/** @var string POST field name for the honeypot input. */
const NOELCLARK_CONTACT_HONEYPOT_FIELD = 'contact_hp';

/**
 * Allowed non-sensitive stage identifiers in the PRG URL.
 *
 * @return string[]
 */
function noelclark_v1_contact_allowed_stages() {
    return array(
        'mail_handoff_ok',
        'mail_handoff_error',
        'validation_error',
        'nonce_error',
        'honeypot_error',
    );
}

/**
 * Resolve a whitelisted stage identifier from the query string.
 *
 * @return string Empty string when absent or invalid.
 */
function noelclark_v1_contact_get_stage() {
    if (!isset($_GET['contact_stage'])) {
        return '';
    }

    $stage = sanitize_key(wp_unslash($_GET['contact_stage']));

    if (!in_array($stage, noelclark_v1_contact_allowed_stages(), true)) {
        return '';
    }

    return $stage;
}

/**
 * Visitor-facing status copy. Must not reveal which security check failed.
 *
 * @param string $stage Whitelisted stage identifier.
 * @return string Empty string when no message should be shown.
 */
function noelclark_v1_contact_stage_message($stage) {
    switch ($stage) {
        case 'mail_handoff_ok':
            return 'Thank you. Your message has been sent.';
        case 'mail_handoff_error':
            return 'Sorry, your message could not be sent right now. Please try again later.';
        case 'validation_error':
            return 'Your message could not be sent. Please check that every field is filled in correctly and try again.';
        case 'nonce_error':
            return 'Your message could not be sent. Please refresh the page and try again.';
        case 'honeypot_error':
            return 'Your message could not be sent. Please try again.';
        default:
            return '';
    }
}

/**
 * Redirect back to the canonical Contact URL with a non-sensitive stage identifier.
 *
 * @param string $stage Whitelisted stage identifier.
 */
function noelclark_v1_contact_redirect_with_stage($stage) {
    $contact_url = get_permalink();

    if (!$contact_url) {
        $contact_url = home_url('/contact/');
    }

    wp_safe_redirect(
        add_query_arg(
            'contact_stage',
            $stage,
            $contact_url
        )
    );
    exit;
}

/**
 * Handle Contact form POST on the canonical Contact page.
 */
function noelclark_v1_contact_handle_post() {
    if (!noelclark_v1_is_contact_page()) {
        return;
    }

    if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
        return;
    }

    if (
        !isset($_POST[NOELCLARK_CONTACT_INTENT_FIELD]) ||
        sanitize_text_field(wp_unslash($_POST[NOELCLARK_CONTACT_INTENT_FIELD])) !== '1'
    ) {
        return;
    }

    $nonce = isset($_POST[NOELCLARK_CONTACT_NONCE_FIELD])
        ? sanitize_text_field(wp_unslash($_POST[NOELCLARK_CONTACT_NONCE_FIELD]))
        : '';

    if (!wp_verify_nonce($nonce, NOELCLARK_CONTACT_NONCE_ACTION)) {
        noelclark_v1_contact_redirect_with_stage('nonce_error');
    }

    $honeypot = isset($_POST[NOELCLARK_CONTACT_HONEYPOT_FIELD])
        ? trim((string) wp_unslash($_POST[NOELCLARK_CONTACT_HONEYPOT_FIELD]))
        : '';

    if ($honeypot !== '') {
        noelclark_v1_contact_redirect_with_stage('honeypot_error');
    }

    $validation = noelclark_v1_contact_validate_submission($_POST);

    if (empty($validation['ok'])) {
        noelclark_v1_contact_redirect_with_stage($validation['code'] ?? 'validation_error');
    }

    $payload = isset($validation['payload']) && is_array($validation['payload'])
        ? $validation['payload']
        : array();

    $mail_result = noelclark_v1_contact_send_handoff($payload);

    noelclark_v1_contact_redirect_with_stage($mail_result['code'] ?? 'mail_handoff_error');
}
add_action('template_redirect', 'noelclark_v1_contact_handle_post');
