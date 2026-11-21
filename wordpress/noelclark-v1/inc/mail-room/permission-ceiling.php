<?php
/**
 * Whether a requested sharing permission stays inside the original consent.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rank of a stored sharing choice. Higher is a broader grant.
 *
 * @param string $choice Stored choice.
 * @param string $credit Public credit, used only for named credit.
 * @return int
 */
function noelclark_v1_mail_room_permission_choice_rank($choice, $credit) {
    $choice = (string) $choice;

    if ($choice === 'not_public') {
        return 0;
    }

    if ($choice === 'anonymous') {
        return 1;
    }

    if ($choice === 'named_credit') {
        return trim((string) $credit) === '' ? 0 : 2;
    }

    return -1;
}

/**
 * ok, same, or broader.
 *
 * A request is broader when it is outside the original consent.
 * A request is the same when it matches the permission already in effect.
 *
 * @param string $original_choice    Immutable submitted choice.
 * @param string $original_credit    Immutable submitted public credit.
 * @param string $current_treatment  Operative treatment.
 * @param string $requested          Requested choice.
 * @return string
 */
function noelclark_v1_mail_room_permission_request_verdict($original_choice, $original_credit, $current_treatment, $requested) {
    $original_choice = (string) $original_choice;
    $original_credit = (string) $original_credit;
    $current         = (string) $current_treatment;
    $requested       = (string) $requested;
    $original_rank   = noelclark_v1_mail_room_permission_choice_rank($original_choice, $original_credit);
    $allowed         = array();

    if ($original_rank >= 0) {
        $allowed['not_public'] = true;

        if ($original_rank >= 1) {
            $allowed['anonymous'] = true;
        }

        if ($original_choice === 'named_credit' && trim($original_credit) !== '') {
            $allowed['named_credit'] = true;
        }
    }

    if (!isset($allowed[$requested])) {
        return 'broader';
    }

    if (isset($allowed[$current]) && $requested === $current) {
        return 'same';
    }

    if (!isset($allowed[$current]) && $requested === $original_choice && isset($allowed[$original_choice])) {
        return 'same';
    }

    return 'ok';
}

/**
 * What a permission decision is allowed to write. Original consent is not a result.
 *
 * A grant applies the requested permission. A decline leaves the current
 * permission in place. A grant outside the original consent, or one that
 * matches the current permission, makes no write.
 *
 * @param string $original_choice   Immutable submitted choice.
 * @param string $original_credit   Immutable submitted public credit.
 * @param string $current_treatment Operative treatment.
 * @param string $requested         Requested choice.
 * @param string $outcome           granted or declined.
 * @return array{ok: bool, reason: string, operative_treatment: string, record_decision: bool, outcome: string, record_operative_change: bool, from: string, to: string}
 */
function noelclark_v1_mail_room_permission_decision_effects($original_choice, $original_credit, $current_treatment, $requested, $outcome) {
    $current   = (string) $current_treatment;
    $requested = (string) $requested;
    $outcome   = (string) $outcome;
    $result    = array();
    $result['ok'] = false;
    $result['reason'] = 'invalid';
    $result['operative_treatment'] = $current;
    $result['record_decision'] = false;
    $result['outcome'] = '';
    $result['record_operative_change'] = false;
    $result['from'] = '';
    $result['to'] = '';

    if ($outcome !== 'granted' && $outcome !== 'declined') {
        return $result;
    }

    if ($outcome === 'declined') {
        $result['ok'] = true;
        $result['reason'] = '';
        $result['record_decision'] = true;
        $result['outcome'] = 'declined';

        return $result;
    }

    $decision_verdict = noelclark_v1_mail_room_permission_request_verdict($original_choice, $original_credit, $current, $requested);

    if ($decision_verdict !== 'ok') {
        $result['reason'] = $decision_verdict === 'same' ? 'grant_same' : 'grant_broader';

        return $result;
    }

    $result['ok'] = true;
    $result['reason'] = '';
    $result['operative_treatment'] = $requested;
    $result['record_decision'] = true;
    $result['outcome'] = 'granted';

    if ($current !== $requested) {
        $result['record_operative_change'] = true;
        $result['from'] = $current;
        $result['to'] = $requested;
    }

    return $result;
}

/**
 * Rank of the permission sharing currently follows.
 *
 * A stored current permission broader than the original consent follows the
 * original consent. An unconfirmed choice has no rank.
 *
 * @param string $original_choice   Immutable submitted choice.
 * @param string $original_credit   Immutable submitted public credit.
 * @param string $current_treatment Operative treatment.
 * @param string $current_credit    Operative public credit.
 * @return int
 */
function noelclark_v1_mail_room_permission_effective_rank($original_choice, $original_credit, $current_treatment, $current_credit) {
    $original_rank = noelclark_v1_mail_room_permission_choice_rank($original_choice, $original_credit);
    $current_rank  = noelclark_v1_mail_room_permission_choice_rank($current_treatment, $current_credit);

    if ($original_rank < 0 || $current_rank < 0) {
        return -1;
    }

    if ($current_rank > $original_rank) {
        return $original_rank;
    }

    return $current_rank;
}

/**
 * Whether this request is a real reduction of the permission sharing follows.
 *
 * A request outside the original consent, or one that makes no change, does not.
 *
 * @param string $original_choice   Immutable submitted choice.
 * @param string $original_credit   Immutable submitted public credit.
 * @param string $current_treatment Operative treatment.
 * @param string $current_credit    Operative public credit.
 * @param string $requested         Requested choice.
 * @return bool
 */
function noelclark_v1_mail_room_permission_request_reduces($original_choice, $original_credit, $current_treatment, $current_credit, $requested) {
    $requested = (string) $requested;

    if ($requested === '') {
        return false;
    }

    $decision_verdict = noelclark_v1_mail_room_permission_request_verdict($original_choice, $original_credit, $current_treatment, $requested);

    if ($decision_verdict !== 'ok') {
        return false;
    }

    $effective_rank = noelclark_v1_mail_room_permission_effective_rank($original_choice, $original_credit, $current_treatment, $current_credit);
    $requested_rank = noelclark_v1_mail_room_permission_choice_rank($requested, $original_credit);

    if ($effective_rank < 0 || $requested_rank < 0) {
        return false;
    }

    return $requested_rank < $effective_rank;
}

/**
 * Requested choice on the latest permission request that has no decision yet.
 *
 * Events are oldest first. A decision closes the request in front of it.
 * Withdrawal events are ignored.
 *
 * @param array<int, array{type?: string, requested?: string}> $events Normalized events.
 * @return string Empty when no permission request is open.
 */
function noelclark_v1_mail_room_permission_open_requested($events) {
    $open  = null;
    $index = 0;
    $count = is_array($events) ? count($events) : 0;

    while ($index < $count) {
        $event = $events[$index];
        $index = $index + 1;

        if (!is_array($event) || !isset($event['type'])) {
            continue;
        }

        $type = (string) $event['type'];

        if ($type === 'permission_change_requested') {
            $open = isset($event['requested']) ? (string) $event['requested'] : '';
            continue;
        }

        if ($type === 'permission_change_decided' && $open !== null) {
            $open = null;
        }
    }

    if ($open === null) {
        return '';
    }

    return (string) $open;
}

/**
 * Whether an open permission request should pause new public sharing.
 *
 * @param string $original_choice   Immutable submitted choice.
 * @param string $original_credit   Immutable submitted public credit.
 * @param string $current_treatment Operative treatment.
 * @param string $current_credit    Operative public credit.
 * @param array<int, array{type?: string, requested?: string}> $events Normalized events, oldest first.
 * @return bool
 */
function noelclark_v1_mail_room_permission_reduction_is_pending($original_choice, $original_credit, $current_treatment, $current_credit, $events) {
    $requested = noelclark_v1_mail_room_permission_open_requested($events);

    if ($requested === '') {
        return false;
    }

    return noelclark_v1_mail_room_permission_request_reduces($original_choice, $original_credit, $current_treatment, $current_credit, $requested);
}
