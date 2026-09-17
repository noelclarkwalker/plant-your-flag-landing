<?php
/**
 * Mail Room letter-submit REST adapter — Development wiring slice.
 *
 * Thin public POST endpoint that maps JSON into
 * noelclark_v1_mail_room_accept_letter(). Does not change accept rules.
 * Does not send mail inline; notification remains queued by the accept service.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string REST namespace. */
const NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE = 'noelclark/v1';

/** @var string REST route (relative to namespace). */
const NOELCLARK_V1_MAIL_ROOM_REST_SUBMIT_ROUTE = '/mail-room/submit';

/** @var string Nonce action for Mail Room letter submit. */
const NOELCLARK_V1_MAIL_ROOM_SUBMIT_NONCE_ACTION = 'noelclark_v1_mail_room_letter_submit';

/**
 * Development Submission Terms version currently seeded in Dev.
 *
 * Must match wp_nc_legal_documents.version_label for doc_type
 * mail_room_submission_terms. Server-enforced (client cannot override).
 */
const NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION = 'mail-room-submission-terms-2026-09-17-dev1';

/**
 * Register Mail Room REST routes.
 *
 * @return void
 */
function noelclark_v1_mail_room_register_rest_routes() {
    register_rest_route(
        NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE,
        NOELCLARK_V1_MAIL_ROOM_REST_SUBMIT_ROUTE,
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'noelclark_v1_mail_room_rest_submit_letter',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'noelclark_v1_mail_room_register_rest_routes');

/**
 * Localized frontend config for Mail Room submit.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_submit_frontend_config() {
    return array(
        'submitUrl'              => esc_url_raw(
            rest_url(NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE . NOELCLARK_V1_MAIL_ROOM_REST_SUBMIT_ROUTE)
        ),
        'nonce'                  => wp_create_nonce(NOELCLARK_V1_MAIL_ROOM_SUBMIT_NONCE_ACTION),
        'legalDocumentVersion'   => NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION,
    );
}

/**
 * Map REST JSON into the internal accept_letter input array.
 *
 * @param WP_REST_Request $request Request.
 * @return array{ok: true, input: array<string, mixed>}|array{ok: false, code: string, status: int}
 */
function noelclark_v1_mail_room_rest_map_submit_input(WP_REST_Request $request) {
    $params = $request->get_json_params();

    if (!is_array($params)) {
        return array(
            'ok'     => false,
            'code'   => 'invalid_json',
            'status' => 400,
        );
    }

    $nonce = '';

    if (isset($params['nonce']) && is_string($params['nonce'])) {
        $nonce = $params['nonce'];
    } else {
        $header = $request->get_header('x_noelclark_mail_room_nonce');

        if (is_string($header)) {
            $nonce = $header;
        }
    }

    if ($nonce === '' || !wp_verify_nonce($nonce, NOELCLARK_V1_MAIL_ROOM_SUBMIT_NONCE_ACTION)) {
        return array(
            'ok'     => false,
            'code'   => 'nonce_error',
            'status' => 403,
        );
    }

    $string_keys = array(
        'idempotency_key',
        'letter_body',
        'sharing_choice',
        'public_credit',
        'display_name',
        'email',
    );

    $input = array();

    foreach ($string_keys as $key) {
        if (!array_key_exists($key, $params) || !is_string($params[$key])) {
            return array(
                'ok'     => false,
                'code'   => 'validation_error',
                'status' => 400,
            );
        }

        $input[$key] = $params[$key];
    }

    // Server-enforced Terms version — ignore any client-supplied value.
    $input['legal_document_version'] = NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION;

    $input['age_attested']   = !empty($params['age_attested']);
    $input['terms_accepted'] = !empty($params['terms_accepted']);

    if (!$input['age_attested'] || !$input['terms_accepted']) {
        return array(
            'ok'     => false,
            'code'   => 'validation_error',
            'status' => 400,
        );
    }

    return array(
        'ok'    => true,
        'input' => $input,
    );
}

/**
 * Map accept_letter failure codes to HTTP status.
 *
 * @param string $code Backend code.
 * @return int
 */
function noelclark_v1_mail_room_rest_status_for_code($code) {
    switch ($code) {
        case 'validation_error':
        case 'invalid_json':
            return 400;
        case 'nonce_error':
            return 403;
        case 'legal_document_unavailable':
            return 503;
        case 'storage_error':
            return 500;
        default:
            return 500;
    }
}

/**
 * REST callback: accept a Mail Room letter.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function noelclark_v1_mail_room_rest_submit_letter(WP_REST_Request $request) {
    if (!function_exists('noelclark_v1_mail_room_accept_letter')) {
        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => 'service_unavailable',
            ),
            503
        );
    }

    $mapped = noelclark_v1_mail_room_rest_map_submit_input($request);

    if (empty($mapped['ok'])) {
        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => isset($mapped['code']) ? $mapped['code'] : 'validation_error',
            ),
            isset($mapped['status']) ? (int) $mapped['status'] : 400
        );
    }

    $result = noelclark_v1_mail_room_accept_letter($mapped['input']);

    if (empty($result['ok'])) {
        $code = isset($result['code']) ? (string) $result['code'] : 'storage_error';

        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => $code,
            ),
            noelclark_v1_mail_room_rest_status_for_code($code)
        );
    }

    // association_token is returned only as the short-lived post-success
    // questionnaire handoff credential. Frontend must keep it in memory only:
    // never display, URL-encode, log, or persist in browser storage.
    $response = array(
        'ok'          => true,
        'replay'      => !empty($result['replay']),
        'mr_ref'      => isset($result['mr_ref']) ? (string) $result['mr_ref'] : '',
        'accepted_at' => isset($result['accepted_at']) ? (string) $result['accepted_at'] : '',
    );

    if (
        isset($result['association_token'])
        && is_string($result['association_token'])
        && $result['association_token'] !== ''
    ) {
        $response['association_token'] = $result['association_token'];
    }

    return new WP_REST_Response($response, 200);
}
