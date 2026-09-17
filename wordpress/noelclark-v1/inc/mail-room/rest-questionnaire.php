<?php
/**
 * Mail Room questionnaire REST adapter — Development wiring slice.
 *
 * Thin public POST endpoint that maps JSON into
 * noelclark_v1_mail_room_accept_questionnaire(). Does not change accept rules.
 * Does not send mail. Does not expose backstage mapping.
 *
 * Requires rest-submit.php (or equivalent) to have defined
 * NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE when both are loaded from the theme.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string REST route (relative to namespace). */
const NOELCLARK_V1_MAIL_ROOM_REST_QUESTIONNAIRE_ROUTE = '/mail-room/questionnaire';

/** @var string Nonce action for Mail Room questionnaire submit. */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_NONCE_ACTION = 'noelclark_v1_mail_room_questionnaire_submit';

/**
 * Development questionnaire identity currently seeded in Dev.
 *
 * Must match wp_nc_questionnaires.questionnaire_ref.
 * Server-enforced (client cannot override).
 */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_REF_DEV1 = 'mail-room-optional-questions';

/**
 * Development questionnaire version currently seeded in Dev.
 *
 * Must match wp_nc_questionnaire_versions.version_label for the Dev questionnaire.
 * Server-enforced (client cannot override).
 */
const NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_VERSION_DEV1 = 'mail-room-optional-questions-2026-09-17-dev1';

/**
 * Register Mail Room questionnaire REST routes.
 *
 * @return void
 */
function noelclark_v1_mail_room_register_questionnaire_rest_routes() {
    if (!defined('NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE')) {
        return;
    }

    register_rest_route(
        NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE,
        NOELCLARK_V1_MAIL_ROOM_REST_QUESTIONNAIRE_ROUTE,
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'noelclark_v1_mail_room_rest_submit_questionnaire',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'noelclark_v1_mail_room_register_questionnaire_rest_routes');

/**
 * Localized frontend config for Mail Room questionnaire submit.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_questionnaire_frontend_config() {
    $namespace = defined('NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE')
        ? NOELCLARK_V1_MAIL_ROOM_REST_NAMESPACE
        : 'noelclark/v1';

    return array(
        'submitUrl'         => esc_url_raw(
            rest_url($namespace . NOELCLARK_V1_MAIL_ROOM_REST_QUESTIONNAIRE_ROUTE)
        ),
        'nonce'             => wp_create_nonce(NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_NONCE_ACTION),
        'questionnaireRef'  => NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_REF_DEV1,
        'versionLabel'      => NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_VERSION_DEV1,
    );
}

/**
 * Map REST JSON into the internal accept_questionnaire input array.
 *
 * @param WP_REST_Request $request Request.
 * @return array{ok: true, input: array<string, mixed>}|array{ok: false, code: string, status: int}
 */
function noelclark_v1_mail_room_rest_map_questionnaire_input(WP_REST_Request $request) {
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
        $header = $request->get_header('x_noelclark_mail_room_questionnaire_nonce');

        if (is_string($header)) {
            $nonce = $header;
        }
    }

    if ($nonce === '' || !wp_verify_nonce($nonce, NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_NONCE_ACTION)) {
        return array(
            'ok'     => false,
            'code'   => 'nonce_error',
            'status' => 403,
        );
    }

    if (!array_key_exists('association_token', $params) || !is_string($params['association_token'])) {
        return array(
            'ok'     => false,
            'code'   => 'validation_error',
            'status' => 400,
        );
    }

    if ($params['association_token'] === '') {
        return array(
            'ok'     => false,
            'code'   => 'association_invalid',
            'status' => 400,
        );
    }

    if (!array_key_exists('answers', $params) || !is_array($params['answers'])) {
        return array(
            'ok'     => false,
            'code'   => 'validation_error',
            'status' => 400,
        );
    }

    $answers = array();

    foreach ($params['answers'] as $question_id => $response_id) {
        if (!is_string($question_id) && !is_int($question_id)) {
            return array(
                'ok'     => false,
                'code'   => 'validation_error',
                'status' => 400,
            );
        }

        $question_id = (string) $question_id;

        if ($question_id === '') {
            return array(
                'ok'     => false,
                'code'   => 'validation_error',
                'status' => 400,
            );
        }

        if ($response_id === null || $response_id === '') {
            continue;
        }

        if (!is_string($response_id)) {
            return array(
                'ok'     => false,
                'code'   => 'validation_error',
                'status' => 400,
            );
        }

        $answers[$question_id] = $response_id;
    }

    return array(
        'ok'    => true,
        'input' => array(
            'association_token' => $params['association_token'],
            // Server-enforced questionnaire identity — ignore any client-supplied values.
            'questionnaire_ref' => NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_REF_DEV1,
            'version_label'     => NOELCLARK_V1_MAIL_ROOM_QUESTIONNAIRE_VERSION_DEV1,
            'answers'           => $answers,
        ),
    );
}

/**
 * Map accept_questionnaire failure codes to HTTP status.
 *
 * @param string $code Backend code.
 * @return int
 */
function noelclark_v1_mail_room_rest_questionnaire_status_for_code($code) {
    switch ($code) {
        case 'validation_error':
        case 'invalid_json':
        case 'association_invalid':
        case 'association_expired':
        case 'association_revoked':
        case 'association_consumed':
            return 400;
        case 'nonce_error':
            return 403;
        case 'questionnaire_unavailable':
            return 503;
        case 'storage_error':
            return 500;
        default:
            return 500;
    }
}

/**
 * REST callback: accept a Mail Room questionnaire response.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function noelclark_v1_mail_room_rest_submit_questionnaire(WP_REST_Request $request) {
    if (!function_exists('noelclark_v1_mail_room_accept_questionnaire')) {
        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => 'service_unavailable',
            ),
            503
        );
    }

    $mapped = noelclark_v1_mail_room_rest_map_questionnaire_input($request);

    if (empty($mapped['ok'])) {
        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => isset($mapped['code']) ? $mapped['code'] : 'validation_error',
            ),
            isset($mapped['status']) ? (int) $mapped['status'] : 400
        );
    }

    $result = noelclark_v1_mail_room_accept_questionnaire($mapped['input']);

    if (empty($result['ok'])) {
        $code = isset($result['code']) ? (string) $result['code'] : 'storage_error';

        return new WP_REST_Response(
            array(
                'ok'   => false,
                'code' => $code,
            ),
            noelclark_v1_mail_room_rest_questionnaire_status_for_code($code)
        );
    }

    return new WP_REST_Response(
        array(
            'ok'          => true,
            'replay'      => !empty($result['replay']),
            'q_ref'       => isset($result['q_ref']) ? (string) $result['q_ref'] : '',
            'accepted_at' => isset($result['accepted_at']) ? (string) $result['accepted_at'] : '',
        ),
        200
    );
}
