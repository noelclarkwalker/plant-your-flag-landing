<?php
/**
 * Letter-author request log.
 *
 * A logged request is not the same as carrying it out. Sharing permission
 * stays on the existing Sharing Permissions control. Credit is saved there
 * too. This file appends request events and does not rewrite older ones.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/author-request-rules.php';

/**
 * Request types, in the approved order.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_author_types() {
    return array(
        'credit'    => 'Their name',
        'sharing'   => 'How it\'s shared',
        'take_down' => 'Taking it down',
        'restore'   => 'Putting it back up',
        'tweak'     => 'The letter itself',
        'other'     => 'Something else',
        'anonymous' => 'Anonymously',
    );
}

/**
 * Choices for a new request. Older anonymity rows keep their own label.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_author_type_choices() {
    $types = noelclark_v1_mail_room_admin_author_types();
    unset($types['anonymous']);

    return $types;
}

/**
 * How the request arrived.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_author_channels() {
    return array(
        'email'  => 'Email',
        'dm'     => 'DM',
        'form'   => 'Reply to the form',
        'phone'  => 'Phone',
        'person' => 'In person',
        'other'  => 'Other',
    );
}

/**
 * Progress words.
 *
 * @return array<string, string>
 */
function noelclark_v1_mail_room_admin_author_progress_labels() {
    return array(
        'still_to_do' => 'No',
        'done'        => 'Yes',
        'not_doing'   => 'Not doing it',
    );
}

/**
 * @param string $code Block code.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_block_message($code) {
    $messages = array(
        'clarify'    => 'We can\'t tell exactly what they asked for, so this stays No.',
        'authorize'  => 'We need to check their words before we use that name.',
        'consent'    => 'They didn\'t allow us to share it that widely.',
        'sharing'    => 'How we share it now still doesn\'t match what they asked for.',
        'credit'     => 'The name we use now still doesn\'t match what they asked for.',
        'notes'      => 'We need a note about what we actually did.',
        'revision'   => 'We can\'t change the letter itself from here. It stays as they sent it.',
        'date'       => 'We need the date we handled it.',
        'invalid'    => 'We can\'t make that change.',
        'email'      => 'We\'re not using their email address as their name.',
        'contact'    => 'We\'re not using their private contact name.',
        'withdrawal' => 'A completed withdrawal still stands, so we\'re not widening sharing from here.',
        'pause'      => 'A withdrawal or a request to reduce sharing is still open.',
        'unreadable' => 'We couldn\'t read the sharing history, so we\'re not widening sharing from here.',
        'missing'    => 'We don\'t have this request written down.',
        'length'     => 'That name is too long.',
    );

    return isset($messages[$code]) ? $messages[$code] : $messages['invalid'];
}

/**
 * @param string $ymd Y-m-d.
 * @return bool
 */
function noelclark_v1_mail_room_admin_author_valid_date($ymd) {
    if (!is_string($ymd) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $parts)) {
        return false;
    }

    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

/**
 * Compact date, such as Oct 12.
 *
 * @param string $ymd Y-m-d.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_short_date($ymd) {
    if (!noelclark_v1_mail_room_admin_author_valid_date($ymd)) {
        return '';
    }

    $stamp = strtotime($ymd . ' 00:00:00 UTC');

    if ($stamp === false) {
        return '';
    }

    return gmdate('M j', $stamp);
}

/**
 * @param array<string, mixed> $row Letter row.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_hold_for_row($event_rows, $row) {
    $types = noelclark_v1_mail_room_admin_author_open_types($event_rows);
    $original = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $original_credit = isset($row['public_credit']) ? (string) $row['public_credit'] : '';
    $current = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $current_credit = isset($row['operative_public_credit']) ? (string) $row['operative_public_credit'] : '';
    $reduces = noelclark_v1_mail_room_permission_request_reduces($original, $original_credit, $current, $current_credit, 'anonymous');

    return noelclark_v1_mail_room_author_request_hold($types, $reduces);
}

/**
 * Add one open permission request when it would reduce sharing.
 *
 * An unreadable request is pending so an ordinary save cannot widen past it.
 *
 * @param array<int, array{kind: string, state: string}> $items            Lock rows.
 * @param string                                         $requested        Requested choice.
 * @param string                                         $original         Original choice.
 * @param string                                         $original_credit  Original credit.
 * @param string                                         $current          Current treatment.
 * @param string                                         $current_credit   Current credit.
 * @return array<int, array{kind: string, state: string}>
 */
function noelclark_v1_mail_room_admin_author_reduction_item($items, $requested, $original, $original_credit, $current, $current_credit) {
    $requested = (string) $requested;

    if ($requested !== 'not_public' && $requested !== 'anonymous' && $requested !== 'named_credit') {
        $items[] = array(
            'kind'  => 'reduction',
            'state' => 'unreadable',
        );

        return $items;
    }

    if (noelclark_v1_mail_room_permission_request_reduces($original, $original_credit, $current, $current_credit, $requested)) {
        $items[] = array(
            'kind'  => 'reduction',
            'state' => 'pending',
        );
    }

    return $items;
}

/**
 * Lock rows from legacy withdrawals, logged takedowns, and pending reductions.
 *
 * Reading these events does not change the stored permission.
 *
 * @param array<int, array<string, mixed>> $event_rows Event rows, oldest first.
 * @param array<string, mixed>             $row        Letter row.
 * @return array<int, array{kind: string, state: string}>
 */
function noelclark_v1_mail_room_admin_author_withdrawal_items($event_rows, $row) {
    $items            = array();
    $logged           = array();
    $asked            = array();
    $progress         = array();
    $withdrawal_open  = false;
    $permission_open  = null;
    $original         = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $original_credit  = isset($row['public_credit']) ? (string) $row['public_credit'] : '';
    $current          = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $current_credit   = isset($row['operative_public_credit']) ? (string) $row['operative_public_credit'] : '';

    if (isset($row['withdrawal_request_status']) && (string) $row['withdrawal_request_status'] === NOELCLARK_V1_MAIL_ROOM_WITHDRAWAL_STATUS_PENDING) {
        $items[] = array(
            'kind'  => 'withdrawal',
            'state' => 'pending',
        );
    }

    if (!is_array($event_rows)) {
        return $items;
    }

    foreach ($event_rows as $event) {
        if (!is_array($event) || !isset($event['event_type'])) {
            continue;
        }

        $event_type = (string) $event['event_type'];
        $pairs      = noelclark_v1_mail_room_admin_event_payload_pairs(isset($event['payload_json']) ? $event['payload_json'] : '');

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED) {
            if ($withdrawal_open) {
                $items[] = array(
                    'kind'  => 'withdrawal',
                    'state' => 'pending',
                );
            }

            $withdrawal_open = true;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED && $withdrawal_open) {
            $outcome = is_array($pairs) && isset($pairs['outcome']) ? (string) $pairs['outcome'] : '';

            if ($outcome === 'granted') {
                $state = 'granted';
            } elseif ($outcome === 'declined') {
                $state = 'declined';
            } else {
                $state = 'unreadable';
            }

            $items[] = array(
                'kind'  => 'withdrawal',
                'state' => $state,
            );
            $withdrawal_open = false;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED) {
            $requested = is_array($pairs) && isset($pairs['requested']) ? (string) $pairs['requested'] : '';

            if ($permission_open !== null) {
                $items = noelclark_v1_mail_room_admin_author_reduction_item($items, $permission_open, $original, $original_credit, $current, $current_credit);
            }

            $permission_open = $requested;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED && $permission_open !== null) {
            $permission_open = null;
            continue;
        }

        if (!is_array($pairs) || !isset($pairs['request_key']) || $pairs['request_key'] === '') {
            continue;
        }

        $key = (string) $pairs['request_key'];

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            $request_type = isset($pairs['request_type']) ? (string) $pairs['request_type'] : '';

            if ($request_type === 'take_down' || $request_type === 'anonymous' || $request_type === 'sharing') {
                $logged[$key] = $request_type;
                $asked[$key]  = isset($pairs['want']) ? (string) $pairs['want'] : '';
            }

            continue;
        }

        if ($event_type !== NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS || !isset($logged[$key])) {
            continue;
        }

        $latest = isset($pairs['progress']) ? (string) $pairs['progress'] : '';

        if ($latest === 'still_to_do' || $latest === 'done' || $latest === 'not_doing') {
            $progress[$key] = $latest;
        }
    }

    if ($withdrawal_open) {
        $items[] = array(
            'kind'  => 'withdrawal',
            'state' => 'pending',
        );
    }

    if ($permission_open !== null) {
        $items = noelclark_v1_mail_room_admin_author_reduction_item($items, $permission_open, $original, $original_credit, $current, $current_credit);
    }

    foreach ($logged as $key => $request_type) {
        $latest = isset($progress[$key]) ? (string) $progress[$key] : 'still_to_do';

        if ($request_type === 'take_down') {
            if ($latest === 'done') {
                $items[] = array(
                    'kind'  => 'takedown',
                    'state' => 'done',
                );
            } elseif ($latest === 'not_doing') {
                $items[] = array(
                    'kind'  => 'takedown',
                    'state' => 'not_doing',
                );
            } else {
                $items[] = array(
                    'kind'  => 'takedown',
                    'state' => 'pending',
                );
            }
        }

        if ($request_type === 'anonymous' && $latest === 'still_to_do' && noelclark_v1_mail_room_permission_request_reduces($original, $original_credit, $current, $current_credit, 'anonymous')) {
            $items[] = array(
                'kind'  => 'anonymity',
                'state' => 'pending',
            );
        }

        if ($request_type === 'sharing' && $latest === 'still_to_do') {
            $level = noelclark_v1_mail_room_author_asked_level(isset($asked[$key]) ? (string) $asked[$key] : '');
            $items = noelclark_v1_mail_room_admin_author_reduction_item($items, $level, $original, $original_credit, $current, $current_credit);
        }
    }

    return $items;
}

/**
 * completed, pending, or clear for one letter's loaded events.
 *
 * @param array<int, array<string, mixed>> $event_rows Event rows, oldest first.
 * @param array<string, mixed>             $row        Letter row.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_withdrawal_standing($event_rows, $row) {
    return noelclark_v1_mail_room_author_withdrawal_standing(
        noelclark_v1_mail_room_admin_author_withdrawal_items($event_rows, is_array($row) ? $row : array())
    );
}

/**
 * Open types from new log events only. Older withdrawal and permission
 * requests keep their own pause checks.
 *
 * @param array<int, array<string, mixed>> $event_rows Event rows, oldest first.
 * @return array<int, string>
 */
function noelclark_v1_mail_room_admin_author_open_types($event_rows) {
    $logged   = array();
    $progress = array();

    if (!is_array($event_rows)) {
        return array();
    }

    foreach ($event_rows as $event) {
        if (!is_array($event) || !isset($event['event_type'])) {
            continue;
        }

        $type  = (string) $event['event_type'];
        $pairs = noelclark_v1_mail_room_admin_event_payload_pairs(isset($event['payload_json']) ? $event['payload_json'] : '');

        if (!is_array($pairs) || !isset($pairs['request_key']) || $pairs['request_key'] === '') {
            continue;
        }

        $key = (string) $pairs['request_key'];

        if ($type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            $logged[$key] = isset($pairs['request_type']) ? (string) $pairs['request_type'] : '';
            continue;
        }

        if ($type !== NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS || !isset($logged[$key])) {
            continue;
        }

        $latest = isset($pairs['progress']) ? (string) $pairs['progress'] : '';

        if ($latest === 'still_to_do' || $latest === 'done' || $latest === 'not_doing') {
            $progress[$key] = $latest;
        }
    }

    $types = array();

    foreach ($logged as $key => $request_type) {
        $latest = isset($progress[$key]) ? (string) $progress[$key] : 'still_to_do';

        if ($latest === 'still_to_do') {
            $types[] = (string) $request_type;
        }
    }

    return $types;
}

/**
 * One running log, oldest first. Older requests stay one row each.
 *
 * @param array<int, array<string, mixed>> $event_rows Event rows.
 * @param array<string, mixed>             $letter     Letter row.
 * @return array<int, array<string, mixed>>
 */
function noelclark_v1_mail_room_admin_author_log_rows($event_rows, $letter) {
    $rows = array();
    $keys = array();
    $withdrawal_open = -1;
    $permission_open = -1;
    $original = isset($letter['sharing_choice']) ? (string) $letter['sharing_choice'] : '';
    $original_credit = isset($letter['public_credit']) ? (string) $letter['public_credit'] : '';
    $current = isset($letter['operative_treatment']) ? (string) $letter['operative_treatment'] : '';
    $types = noelclark_v1_mail_room_admin_author_types();

    if (!is_array($event_rows)) {
        return $rows;
    }

    foreach ($event_rows as $event) {
        if (!is_array($event) || !isset($event['event_type'])) {
            continue;
        }

        $event_type = (string) $event['event_type'];
        $pairs = noelclark_v1_mail_room_admin_event_payload_pairs(isset($event['payload_json']) ? $event['payload_json'] : '');
        $recorded = isset($event['created_at']) ? (string) $event['created_at'] : '';

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED) {
            $rows[] = array(
                'source'   => 'legacy_withdrawal',
                'type'     => 'take_down',
                'want'     => '',
                'progress' => 'still_to_do',
                'asked_on' => '',
                'recorded' => $recorded,
                'quote'    => '',
                'channel'  => '',
                'notes'    => '',
                'handled_on' => '',
                'key'      => '',
                'detail'   => 'take_down',
                'legacy_open' => true,
                'note'     => '',
                'allow_grant' => true,
                'credit_on_file' => '',
                'permission_on_file' => '',
                'consent_choice' => $original,
                'consent_credit' => $original_credit,
            );
            $withdrawal_open = count($rows) - 1;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_DECIDED && $withdrawal_open >= 0) {
            $outcome = is_array($pairs) && isset($pairs['outcome']) ? (string) $pairs['outcome'] : '';
            $rows[$withdrawal_open]['progress'] = $outcome === 'granted' ? 'done' : 'not_doing';
            $rows[$withdrawal_open]['legacy_open'] = false;
            $rows[$withdrawal_open]['handled_on'] = substr($recorded, 0, 10);
            $withdrawal_open = -1;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED) {
            $requested = is_array($pairs) && isset($pairs['requested']) ? (string) $pairs['requested'] : '';
            $mapped = 'restore';
            $want = '';

            if ($requested === 'not_public') {
                $mapped = 'take_down';
            } elseif ($requested === 'anonymous') {
                $mapped = 'anonymous';
            } elseif ($requested === 'named_credit') {
                $mapped = 'restore';
                $want = 'Share with my name';
            } else {
                $mapped = 'other';
                $want = 'The requested permission could not be read.';
            }

            $verdict = noelclark_v1_mail_room_permission_request_verdict($original, $original_credit, $current, $requested);
            $note = '';
            $allow_grant = $verdict === 'ok';

            if ($verdict === 'broader') {
                $note = 'This request is broader than the original consent, so it cannot be granted.';
                $allow_grant = false;
            } elseif ($verdict === 'same') {
                $note = 'This request matches the current sharing permission, so it cannot be granted.';
                $allow_grant = false;
            }

            $rows[] = array(
                'source'   => 'legacy_permission',
                'type'     => $mapped,
                'want'     => $want,
                'progress' => 'still_to_do',
                'asked_on' => '',
                'recorded' => $recorded,
                'quote'    => '',
                'channel'  => '',
                'notes'    => '',
                'handled_on' => '',
                'key'      => '',
                'detail'   => $mapped . ':' . $requested,
                'legacy_open' => true,
                'note'     => $note,
                'allow_grant' => $allow_grant,
                'credit_on_file' => '',
                'permission_on_file' => '',
                'consent_choice' => $original,
                'consent_credit' => $original_credit,
            );
            $permission_open = count($rows) - 1;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_DECIDED && $permission_open >= 0) {
            $outcome = is_array($pairs) && isset($pairs['outcome']) ? (string) $pairs['outcome'] : '';
            $rows[$permission_open]['progress'] = $outcome === 'granted' ? 'done' : 'not_doing';
            $rows[$permission_open]['legacy_open'] = false;
            $rows[$permission_open]['handled_on'] = substr($recorded, 0, 10);
            $permission_open = -1;
            continue;
        }

        if (!is_array($pairs) || !isset($pairs['request_key'])) {
            continue;
        }

        $key = (string) $pairs['request_key'];

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            $request_type = isset($pairs['request_type']) ? (string) $pairs['request_type'] : '';

            if (!isset($types[$request_type])) {
                $request_type = 'other';
            }

            $rows[] = array(
                'source'   => 'log',
                'type'     => $request_type,
                'want'     => isset($pairs['want']) ? (string) $pairs['want'] : '',
                'progress' => 'still_to_do',
                'asked_on' => isset($pairs['asked_on']) ? (string) $pairs['asked_on'] : '',
                'recorded' => $recorded,
                'quote'    => isset($pairs['quote']) ? (string) $pairs['quote'] : '',
                'channel'  => isset($pairs['channel']) ? (string) $pairs['channel'] : '',
                'notes'    => '',
                'handled_on' => '',
                'key'      => $key,
                'detail'   => isset($pairs['want']) ? (string) $pairs['want'] : '',
                'legacy_open' => false,
                'note'     => '',
                'allow_grant' => false,
                'credit_on_file' => isset($pairs['credit_on_file']) ? (string) $pairs['credit_on_file'] : '',
                'permission_on_file' => isset($pairs['permission_on_file']) ? (string) $pairs['permission_on_file'] : '',
                'consent_choice' => isset($pairs['consent_choice']) ? (string) $pairs['consent_choice'] : $original,
                'consent_credit' => isset($pairs['consent_credit']) ? (string) $pairs['consent_credit'] : $original_credit,
            );
            $keys[$key] = count($rows) - 1;
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS && isset($keys[$key])) {
            $slot = $keys[$key];
            $progress = isset($pairs['progress']) ? (string) $pairs['progress'] : '';
            $labels = noelclark_v1_mail_room_admin_author_progress_labels();

            if (isset($labels[$progress])) {
                $rows[$slot]['progress'] = $progress;
            }

            $rows[$slot]['notes'] = isset($pairs['notes']) ? (string) $pairs['notes'] : '';
            $rows[$slot]['handled_on'] = isset($pairs['handled_on']) ? (string) $pairs['handled_on'] : '';
            $rows[$slot]['credit_at_progress'] = isset($pairs['credit_on_file']) ? (string) $pairs['credit_on_file'] : '';
            $rows[$slot]['permission_at_progress'] = isset($pairs['permission_on_file']) ? (string) $pairs['permission_on_file'] : '';
        }
    }

    return $rows;
}

/**
 * @param array<int, array<string, mixed>> $log_rows Log rows.
 * @return bool
 */
function noelclark_v1_mail_room_admin_author_log_conflicts($log_rows) {
    $items = array();

    foreach ($log_rows as $row) {
        if (!is_array($row) || !isset($row['progress']) || $row['progress'] !== 'still_to_do') {
            continue;
        }

        $type = (string) $row['type'];

        if ($type === 'sharing') {
            $level = noelclark_v1_mail_room_author_asked_level(isset($row['want']) ? (string) $row['want'] : '');

            if ($level === 'anonymous') {
                $type = 'anonymous';
            } elseif ($level === 'named_credit') {
                $type = 'restore';
            } else {
                $type = 'take_down';
            }
        }

        if ($type !== 'take_down' && $type !== 'anonymous' && $type !== 'credit' && $type !== 'restore') {
            continue;
        }

        $items[] = array(
            'type'   => $type,
            'detail' => isset($row['detail']) ? (string) $row['detail'] : '',
        );
    }

    return noelclark_v1_mail_room_author_requests_conflict($items);
}

/**
 * Plain words for what a request asked for.
 *
 * @param string $type Request type.
 * @param string $want Stored ask.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_ask_text($type, $want) {
    $type = (string) $type;
    $want = trim((string) $want);

    if ($type === 'credit') {
        $found = noelclark_v1_mail_room_author_credit_from_words($want);

        if (isset($found['status']) && $found['status'] === 'clear') {
            return (string) $found['credit'];
        }

        return $want;
    }

    if ($type === 'take_down') {
        return 'Keep it private';
    }

    if ($type === 'anonymous') {
        return 'Anonymously';
    }

    $level = noelclark_v1_mail_room_author_asked_level($want);

    if ($level === 'not_public') {
        return 'Keep it private';
    }

    if ($level === 'anonymous') {
        return 'Anonymously';
    }

    if ($level === 'named_credit') {
        return 'With their name';
    }

    return $want;
}

/**
 * @param array<string, mixed> $entry Log row.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_compact_line($entry) {
    $types = noelclark_v1_mail_room_admin_author_types();
    $labels = noelclark_v1_mail_room_admin_author_progress_labels();
    $type = isset($types[$entry['type']]) ? $types[$entry['type']] : 'Something else';
    $progress = isset($labels[$entry['progress']]) ? $labels[$entry['progress']] : 'No';
    $asked = noelclark_v1_mail_room_admin_author_short_date(isset($entry['asked_on']) ? (string) $entry['asked_on'] : '');

    if ($asked === '' && isset($entry['source']) && $entry['source'] !== 'log') {
        $asked = noelclark_v1_mail_room_admin_author_short_date(substr((string) $entry['recorded'], 0, 10));
    }

    $line = $type;

    if ($asked !== '') {
        $line = $asked . ' · ' . $line;
    }

    $want = noelclark_v1_mail_room_admin_author_ask_text((string) $entry['type'], (string) $entry['want']);

    if ($want !== '') {
        $line .= ': ' . $want;
    }

    $line .= ' · ' . $progress;
    $handled = noelclark_v1_mail_room_admin_author_short_date(isset($entry['handled_on']) ? (string) $entry['handled_on'] : '');

    if ($handled !== '' && $entry['progress'] !== 'still_to_do') {
        $line .= ' ' . $handled;
    }

    return $line;
}

/**
 * Lock the letter and read author-request events.
 *
 * @param string $mr_ref Normalized reference.
 * @return array{ok: true, row: array<string, mixed>, events: array<int, array<string, mixed>>, state: string, events_table: string}|array{ok: false, code: string}
 */
function noelclark_v1_mail_room_admin_begin_author_write($mr_ref) {
    global $wpdb;

    $started = $wpdb->query('START TRANSACTION');

    if ($started === false) {
        noelclark_v1_mail_room_admin_log('request_write_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    $submissions  = noelclark_v1_mail_room_table('mr_submissions');
    $state        = noelclark_v1_mail_room_table('mr_state');
    $events       = noelclark_v1_mail_room_table('mr_events');
    $participants = noelclark_v1_mail_room_table('participants');
    $locked       = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT s.id AS mr_id, s.sharing_choice, s.public_credit, '
            . 'st.operative_treatment, st.operative_public_credit, st.withdrawal_request_status, '
            . 'p.display_name, p.email '
            . "FROM {$state} st INNER JOIN {$submissions} s ON s.id = st.mr_id "
            . "LEFT JOIN {$participants} p ON p.id = s.accepted_participant_id "
            . 'WHERE s.mr_ref = %s LIMIT 1 FOR UPDATE',
            $mr_ref
        ),
        ARRAY_A
    );

    if ($locked === null && $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    if (!is_array($locked) || !isset($locked['mr_id']) || (int) $locked['mr_id'] < 1) {
        $wpdb->query('ROLLBACK');

        return array(
            'ok'   => false,
            'code' => 'missing',
        );
    }

    $history = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT e.event_type, e.payload_json, e.created_at '
            . "FROM {$events} e INNER JOIN {$submissions} s ON s.id = e.mr_id "
            . 'WHERE s.mr_ref = %s AND e.event_type IN (%s, %s, %s, %s, %s) '
            . 'ORDER BY e.created_at ASC, e.id ASC',
            $mr_ref,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS,
            NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED,
            NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED
        ),
        ARRAY_A
    );

    if (!is_array($history) || $wpdb->last_error !== '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_read_error');

        return array(
            'ok'   => false,
            'code' => 'storage',
        );
    }

    return array(
        'ok'           => true,
        'row'          => $locked,
        'events'       => $history,
        'state'        => $state,
        'events_table' => $events,
    );
}

/**
 * @param array<int, array<string, mixed>> $events Author events.
 * @param string                           $key    Request key.
 * @return array<string, string>|null
 */
function noelclark_v1_mail_room_admin_author_logged_payload($events, $key) {
    $found = null;

    foreach ($events as $event) {
        if (!is_array($event) || (string) $event['event_type'] !== NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            continue;
        }

        $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($event['payload_json']);

        if (is_array($pairs) && isset($pairs['request_key']) && (string) $pairs['request_key'] === $key) {
            $found = $pairs;
        }
    }

    return $found;
}

/**
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_author_log() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_author_log_' . $mr_ref);

    $types = noelclark_v1_mail_room_admin_author_type_choices();
    $channels = noelclark_v1_mail_room_admin_author_channels();
    $request_type = sanitize_key(noelclark_v1_mail_room_admin_post_string('author_type'));
    $channel = sanitize_key(noelclark_v1_mail_room_admin_post_string('author_channel'));
    $made = sanitize_key(noelclark_v1_mail_room_admin_post_string('author_made_change'));
    $asked_on = noelclark_v1_mail_room_admin_post_string('author_asked_on');
    $quote = sanitize_textarea_field(noelclark_v1_mail_room_admin_post_string('author_quote'));
    $share_choice = sanitize_key(noelclark_v1_mail_room_admin_post_string('author_share_choice'));
    $name = sanitize_text_field(noelclark_v1_mail_room_admin_post_string('author_name'));
    $want = sanitize_text_field(noelclark_v1_mail_room_admin_post_string('author_want'));

    if (!isset($types[$request_type]) || !isset($channels[$channel]) || ($made !== 'yes' && $made !== 'no')) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'invalid');
    }

    $progress = $made === 'yes' ? 'done' : 'still_to_do';

    if ($request_type === 'credit') {
        if ($name === '' || strlen($name) > 200) {
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', $name === '' ? 'name' : 'length');
        }

        $want = 'change their credit to ' . $name;
    } elseif ($request_type === 'take_down') {
        $want = 'not_public';
    } elseif ($request_type === 'sharing' || $request_type === 'restore') {
        if ($share_choice !== 'not_public' && $share_choice !== 'anonymous' && $share_choice !== 'named_credit') {
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'choice');
        }

        $want = $share_choice;
    }

    if (strlen($want) > 500 || strlen($quote) > 5000) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'length');
    }

    if (!noelclark_v1_mail_room_admin_author_valid_date($asked_on)) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'asked');
    }

    $key = bin2hex(random_bytes(8));
    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_author_write($mr_ref),
        $mr_ref,
        'areq'
    );

    if ($begun === null) {
        return;
    }

    $now = current_time('mysql', true);
    $credit_on_file = isset($begun['row']['operative_public_credit']) ? (string) $begun['row']['operative_public_credit'] : '';
    $permission_on_file = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';
    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED,
        array(
            'request_key'         => $key,
            'request_type'        => $request_type,
            'want'                => $want,
            'asked_on'            => $asked_on,
            'channel'             => $channel,
            'quote'               => $quote,
            'credit_on_file'      => $credit_on_file,
            'permission_on_file'  => $permission_on_file,
            'consent_choice'      => isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '',
            'consent_credit'      => isset($begun['row']['public_credit']) ? (string) $begun['row']['public_credit'] : '',
        ),
        $now
    );

    if (!$saved) {
        $GLOBALS['wpdb']->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
    }

    if ($progress === 'still_to_do') {
        if (!noelclark_v1_mail_room_admin_commit_request_write()) {
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
        }

        noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'logged');
    }

    $events = isset($begun['events']) && is_array($begun['events']) ? $begun['events'] : array();
    $events[] = array(
        'event_type'   => NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED,
        'payload_json' => wp_json_encode(array(
            'request_key'  => $key,
            'request_type' => $request_type,
            'want'         => $want,
            'asked_on'     => $asked_on,
            'quote'        => $quote,
        )),
    );
    $result = noelclark_v1_mail_room_admin_author_apply_logged_change($begun, $mr_ref, $request_type, $want, '', $key, $events);

    if (!isset($result['code']) || $result['code'] === 'storage') {
        $GLOBALS['wpdb']->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
    }

    if ($result['code'] !== 'ok') {
        if (!noelclark_v1_mail_room_admin_commit_request_write()) {
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
        }

        noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'refused');
    }

    $progress_saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS,
        array(
            'request_key'        => $key,
            'progress'           => 'done',
            'handled_on'         => current_time('Y-m-d', true),
            'notes'              => '',
            'credit_on_file'     => isset($result['credit']) ? (string) $result['credit'] : $credit_on_file,
            'permission_on_file' => isset($result['treatment']) ? (string) $result['treatment'] : $permission_on_file,
        ),
        $now
    );

    if (!$progress_saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$progress_saved) {
            $GLOBALS['wpdb']->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'done');
}
add_action('admin_post_noelclark_v1_mail_room_log_author_request', 'noelclark_v1_mail_room_admin_handle_author_log');

/**
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_author_progress() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_author_progress_' . $mr_ref);

    $key = strtolower(noelclark_v1_mail_room_admin_post_string('author_request_key'));
    $progress = sanitize_key(noelclark_v1_mail_room_admin_post_string('author_progress'));
    $handled_on = noelclark_v1_mail_room_admin_post_string('author_handled_on');
    $notes = sanitize_textarea_field(noelclark_v1_mail_room_admin_post_string('author_notes'));
    $labels = noelclark_v1_mail_room_admin_author_progress_labels();

    if (!preg_match('/^[a-f0-9]{16}$/', $key) || !isset($labels[$progress]) || strlen($notes) > 5000) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'invalid');
    }

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_author_write($mr_ref),
        $mr_ref,
        'areq'
    );

    if ($begun === null) {
        return;
    }

    $logged = noelclark_v1_mail_room_admin_author_logged_payload($begun['events'], $key);

    if ($logged === null) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'missing');
    }

    if ($progress !== 'still_to_do' && !noelclark_v1_mail_room_admin_author_valid_date($handled_on)) {
        $handled_on = current_time('Y-m-d', true);
    }

    $credit_on_file = isset($begun['row']['operative_public_credit']) ? (string) $begun['row']['operative_public_credit'] : '';
    $permission_on_file = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';

    if ($progress === 'done') {
        $result = noelclark_v1_mail_room_admin_author_apply_logged_change(
            $begun,
            $mr_ref,
            isset($logged['request_type']) ? (string) $logged['request_type'] : '',
            isset($logged['want']) ? (string) $logged['want'] : '',
            $notes,
            $key,
            isset($begun['events']) && is_array($begun['events']) ? $begun['events'] : array()
        );

        if (!isset($result['code']) || $result['code'] !== 'ok') {
            $wpdb->query('ROLLBACK');

            if (isset($result['code']) && $result['code'] === 'storage') {
                noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
            }

            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'refused');
        }

        $credit_on_file = isset($result['credit']) ? (string) $result['credit'] : $credit_on_file;
        $permission_on_file = isset($result['treatment']) ? (string) $result['treatment'] : $permission_on_file;
    }

    $now = current_time('mysql', true);
    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS,
        array(
            'request_key'        => $key,
            'progress'           => $progress,
            'handled_on'         => $progress === 'still_to_do' ? '' : $handled_on,
            'notes'              => $notes,
            'credit_on_file'     => $credit_on_file,
            'permission_on_file' => $permission_on_file,
        ),
        $now
    );

    if (!$saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'progress');
}
add_action('admin_post_noelclark_v1_mail_room_progress_author_request', 'noelclark_v1_mail_room_admin_handle_author_progress');

/**
 * Whether a logged request lets us save this credit.
 *
 * @param array<int, array<string, mixed>> $events Author events.
 * @param string                           $credit Requested credit.
 * @param string                           $original_choice Immutable choice.
 * @param string                           $original_credit Immutable credit.
 * @param string                           $current_credit  Credit in use.
 * @return string ok, authorize, consent, or missing.
 */
function noelclark_v1_mail_room_admin_author_credit_save_block($events, $credit, $original_choice, $original_credit, $current_credit) {
    if (noelclark_v1_mail_room_author_credit_consent_conflict($original_choice, $original_credit, $credit)) {
        return 'consent';
    }

    if (trim($credit) === trim($original_credit)) {
        return 'ok';
    }

    $relation = noelclark_v1_mail_room_author_credit_relation($credit, $original_credit, $current_credit);
    $matched  = false;

    foreach ($events as $event) {
        if (!is_array($event) || (string) $event['event_type'] !== NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            continue;
        }

        $pairs = noelclark_v1_mail_room_admin_event_payload_pairs($event['payload_json']);

        if (!is_array($pairs) || !isset($pairs['request_type']) || $pairs['request_type'] !== 'credit') {
            continue;
        }

        $found = noelclark_v1_mail_room_author_credit_from_words(isset($pairs['want']) ? (string) $pairs['want'] : '');

        if (!isset($found['status']) || $found['status'] !== 'clear' || (string) $found['credit'] !== $credit) {
            continue;
        }

        $matched = true;
    }

    if (!$matched) {
        return 'missing';
    }

    if (($relation === 'broader' || $relation === 'different') && !noelclark_v1_mail_room_admin_author_broadening_confirmed($events, $credit)) {
        return 'authorize';
    }

    return 'ok';
}

/**
 * Logged requests, confirmations, and other writer requests used for binding.
 *
 * Evidence is copied exactly so a later change cannot reuse the old confirmation.
 * The evidence text is not searched for authorizing phrases.
 *
 * @param array<int, array<string, mixed>> $events Event rows.
 * @return array{requests: array<string, array<string, string>>, confirmations: array<int, array<string, string>>, others: array<int, array<string, string>>}
 */
function noelclark_v1_mail_room_admin_author_credit_parties($events) {
    $requests      = array();
    $confirmations = array();
    $others        = array();
    $legacy        = 0;

    if (!is_array($events)) {
        return array(
            'requests'      => $requests,
            'confirmations' => $confirmations,
            'others'        => $others,
        );
    }

    foreach ($events as $event) {
        if (!is_array($event) || !isset($event['event_type'])) {
            continue;
        }

        $event_type = (string) $event['event_type'];
        $pairs      = noelclark_v1_mail_room_admin_event_payload_pairs(isset($event['payload_json']) ? $event['payload_json'] : '');

        if (!is_array($pairs)) {
            $pairs = array();
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_LOGGED) {
            $key    = isset($pairs['request_key']) ? (string) $pairs['request_key'] : '';
            $type   = isset($pairs['request_type']) ? (string) $pairs['request_type'] : '';
            $asked  = isset($pairs['asked_on']) ? (string) $pairs['asked_on'] : '';
            $quote  = isset($pairs['quote']) ? (string) $pairs['quote'] : '';
            $credit = '';

            if ($type === 'credit') {
                $found = noelclark_v1_mail_room_author_credit_from_words(isset($pairs['want']) ? (string) $pairs['want'] : '');

                if (isset($found['status']) && $found['status'] === 'clear') {
                    $credit = (string) $found['credit'];
                }

                if ($key !== '') {
                    $requests[$key] = array(
                        'request_key' => $key,
                        'credit'      => $credit,
                        'asked_on'    => $asked,
                        'quote'       => $quote,
                    );
                }
            }

            $conflict_type = $type;

            if ($type === 'sharing') {
                $level = noelclark_v1_mail_room_author_asked_level(isset($pairs['want']) ? (string) $pairs['want'] : '');

                if ($level === 'anonymous') {
                    $conflict_type = 'anonymous';
                } elseif ($level === 'named_credit') {
                    $conflict_type = 'restore';
                } else {
                    $conflict_type = 'take_down';
                }
            }

            $others[] = array(
                'type'        => $conflict_type,
                'request_key' => $key,
                'credit'      => $credit,
                'asked_on'    => $asked,
            );
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED) {
            $confirmations[] = array(
                'status'      => isset($pairs['status']) ? (string) $pairs['status'] : '',
                'user_id'     => isset($pairs['user_id']) ? (string) $pairs['user_id'] : '',
                'request_key' => isset($pairs['request_key']) ? (string) $pairs['request_key'] : '',
                'credit'      => isset($pairs['credit']) ? (string) $pairs['credit'] : '',
                'asked_on'    => isset($pairs['asked_on']) ? (string) $pairs['asked_on'] : '',
                'evidence'    => isset($pairs['evidence']) ? (string) $pairs['evidence'] : '',
                'attestation' => isset($pairs['attestation']) ? (string) $pairs['attestation'] : '',
            );
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_WITHDRAWAL_REQUESTED) {
            $legacy   = $legacy + 1;
            $others[] = array(
                'type'        => 'take_down',
                'request_key' => 'legacy-' . $legacy,
                'credit'      => '',
                'asked_on'    => '',
            );
            continue;
        }

        if ($event_type === NOELCLARK_V1_MAIL_ROOM_EVENT_PERMISSION_CHANGE_REQUESTED) {
            $requested = isset($pairs['requested']) ? (string) $pairs['requested'] : '';
            $mapped    = 'take_down';

            if ($requested === 'anonymous') {
                $mapped = 'anonymous';
            } elseif ($requested === 'named_credit') {
                $mapped = 'restore';
            }

            $legacy   = $legacy + 1;
            $others[] = array(
                'type'        => $mapped,
                'request_key' => 'legacy-' . $legacy,
                'credit'      => '',
                'asked_on'    => '',
            );
        }
    }

    return array(
        'requests'      => $requests,
        'confirmations' => $confirmations,
        'others'        => $others,
    );
}

/**
 * A broader credit is confirmed only for the logged request that asked for it.
 *
 * @param array<int, array<string, mixed>> $events Event rows.
 * @param string                           $credit Exact credit being saved.
 * @return bool
 */
function noelclark_v1_mail_room_admin_author_broadening_confirmed($events, $credit) {
    $credit = trim((string) $credit);

    if ($credit === '') {
        return false;
    }

    $parties = noelclark_v1_mail_room_admin_author_credit_parties($events);

    foreach ($parties['confirmations'] as $confirmation) {
        if (!is_array($confirmation)) {
            continue;
        }

        $key = isset($confirmation['request_key']) ? (string) $confirmation['request_key'] : '';

        if ($key === '' || !isset($parties['requests'][$key])) {
            continue;
        }

        $request = $parties['requests'][$key];

        if (!is_array($request) || (string) $request['credit'] !== $credit) {
            continue;
        }

        if (noelclark_v1_mail_room_author_credit_confirmation_valid($confirmation, $request, $parties['others'])) {
            return true;
        }
    }

    return false;
}

/**
 * The identified credit when this request has its own valid confirmation.
 *
 * @param array<int, array<string, mixed>> $events      Event rows.
 * @param string                           $words       What do they want?
 * @param string                           $request_key Logged request key.
 * @return string
 */
function noelclark_v1_mail_room_admin_author_matching_confirmation($events, $words, $request_key) {
    $found = noelclark_v1_mail_room_author_credit_from_words($words);

    if (!isset($found['status']) || $found['status'] !== 'clear') {
        return '';
    }

    $credit      = (string) $found['credit'];
    $request_key = (string) $request_key;
    $parties     = noelclark_v1_mail_room_admin_author_credit_parties($events);

    if (!isset($parties['requests'][$request_key])) {
        return '';
    }

    $request = $parties['requests'][$request_key];

    if (!is_array($request) || (string) $request['credit'] !== $credit) {
        return '';
    }

    foreach ($parties['confirmations'] as $confirmation) {
        if (!is_array($confirmation) || (string) $confirmation['request_key'] !== $request_key || (string) $confirmation['credit'] !== $credit) {
            continue;
        }

        if (noelclark_v1_mail_room_author_credit_confirmation_valid($confirmation, $request, $parties['others'])) {
            return $credit;
        }
    }

    return '';
}

/**
 * Whether the operative credit may be presented as an authorized broader name.
 *
 * Public letter pages, archives, and sharing features must call this before
 * treating operative_public_credit as an authorized broader name, then use
 * noelclark_v1_mail_room_admin_share_assessments() for the share answer.
 * The stored operative credit is not authorization on its own.
 *
 * @param array<int, array<string, mixed>> $events Event rows.
 * @param array<string, mixed>             $row    Letter row.
 * @return bool
 */
function noelclark_v1_mail_room_admin_author_broadening_valid($events, $row) {
    $operative = isset($row['operative_public_credit']) ? trim((string) $row['operative_public_credit']) : '';
    $original  = isset($row['public_credit']) ? trim((string) $row['public_credit']) : '';
    $relation  = noelclark_v1_mail_room_author_credit_relation($operative, $original, $original);

    if ($relation !== 'broader' && $relation !== 'different') {
        return true;
    }

    return noelclark_v1_mail_room_admin_author_broadening_confirmed($events, $operative);
}

/**
 * Carry out one logged request. The name they sent and their original sharing choice stay as they are.
 *
 * Nothing is written unless the existing checks pass. The caller commits or rolls back.
 *
 * @param array<string, mixed>             $begun        Open write.
 * @param string                           $mr_ref       Letter reference.
 * @param string                           $request_type Logged type.
 * @param string                           $want         What they asked for.
 * @param string                           $notes        Note about what we did.
 * @param string                           $request_key  Logged request.
 * @param array<int, array<string, mixed>> $events       Events, including the request being carried out.
 * @return array{code: string, treatment: string, credit: string}
 */
function noelclark_v1_mail_room_admin_author_apply_logged_change($begun, $mr_ref, $request_type, $want, $notes, $request_key, $events) {
    global $wpdb;

    $request_type    = (string) $request_type;
    $want            = (string) $want;
    $notes           = (string) $notes;
    $original_choice = isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '';
    $original_credit = isset($begun['row']['public_credit']) ? (string) $begun['row']['public_credit'] : '';
    $treatment       = isset($begun['row']['operative_treatment']) ? (string) $begun['row']['operative_treatment'] : '';
    $credit          = isset($begun['row']['operative_public_credit']) ? (string) $begun['row']['operative_public_credit'] : '';
    $email           = isset($begun['row']['email']) ? trim((string) $begun['row']['email']) : '';
    $contact         = isset($begun['row']['display_name']) ? trim((string) $begun['row']['display_name']) : '';
    $unchanged       = array(
        'code'      => 'invalid',
        'treatment' => $treatment,
        'credit'    => $credit,
    );

    if (!is_array($events)) {
        $events = array();
    }

    if ($request_type === 'tweak') {
        $unchanged['code'] = 'revision';

        return $unchanged;
    }

    if ($request_type === 'other') {
        $note = trim($notes) !== '' ? $notes : $want;
        $block = noelclark_v1_mail_room_author_progress_block(
            'other',
            'done',
            $want,
            '',
            $note,
            $original_choice,
            $original_credit,
            $treatment,
            $credit,
            ''
        );
        $unchanged['code'] = $block === '' ? 'ok' : $block;

        return $unchanged;
    }

    if ($request_type === 'credit') {
        $found = noelclark_v1_mail_room_author_credit_from_words($want);

        if (!isset($found['status']) || $found['status'] !== 'clear') {
            $unchanged['code'] = 'clarify';

            return $unchanged;
        }

        $next_credit = (string) $found['credit'];

        if (strlen($next_credit) > 200) {
            $unchanged['code'] = 'length';

            return $unchanged;
        }

        if ($email !== '' && strtolower($next_credit) === strtolower($email)) {
            $unchanged['code'] = 'email';

            return $unchanged;
        }

        $block = noelclark_v1_mail_room_admin_author_credit_save_block($events, $next_credit, $original_choice, $original_credit, $credit);

        if ($contact !== '' && $next_credit === $contact && $next_credit !== trim($original_credit) && $block === 'authorize') {
            $unchanged['code'] = 'contact';

            return $unchanged;
        }

        if ($block !== 'ok') {
            $unchanged['code'] = $block;

            return $unchanged;
        }

        $progress_block = noelclark_v1_mail_room_author_progress_block(
            'credit',
            'done',
            $want,
            '',
            $notes,
            $original_choice,
            $original_credit,
            $treatment,
            $next_credit,
            noelclark_v1_mail_room_admin_author_matching_confirmation($events, $want, $request_key)
        );

        if ($progress_block !== '') {
            $unchanged['code'] = $progress_block;

            return $unchanged;
        }

        if (trim($credit) === trim($next_credit)) {
            return array(
                'code'      => 'ok',
                'treatment' => $treatment,
                'credit'    => $next_credit,
            );
        }

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$begun['state']} st INNER JOIN " . noelclark_v1_mail_room_table('mr_submissions') . ' s ON s.id = st.mr_id '
                . 'SET st.operative_public_credit = %s WHERE s.mr_ref = %s',
                $next_credit,
                $mr_ref
            )
        );

        if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
            $unchanged['code'] = 'storage';

            return $unchanged;
        }

        $saved = noelclark_v1_mail_room_admin_insert_request_event(
            $begun['events_table'],
            (int) $begun['row']['mr_id'],
            NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_CREDIT_CHANGED,
            array(
                'from' => trim($credit),
                'to'   => $next_credit,
            ),
            current_time('mysql', true)
        );

        if (!$saved) {
            $unchanged['code'] = 'storage';

            return $unchanged;
        }

        return array(
            'code'      => 'ok',
            'treatment' => $treatment,
            'credit'    => $next_credit,
        );
    }

    $proposed = '';

    if ($request_type === 'take_down') {
        $proposed = 'not_public';
    } elseif ($request_type === 'anonymous') {
        $proposed = 'anonymous';
    } elseif ($request_type === 'restore' || $request_type === 'sharing') {
        $proposed = noelclark_v1_mail_room_author_asked_level($want);

        if ($proposed === '') {
            $unchanged['code'] = 'clarify';

            return $unchanged;
        }
    } else {
        return $unchanged;
    }

    $allowed = noelclark_v1_mail_room_admin_operative_choices($original_choice, $original_credit);

    if (!isset($allowed[$proposed])) {
        $unchanged['code'] = 'consent';

        return $unchanged;
    }

    $lock = noelclark_v1_mail_room_author_operative_save_block(
        $treatment,
        $proposed,
        $original_credit,
        noelclark_v1_mail_room_admin_author_withdrawal_items($events, $begun['row'])
    );

    if ($lock !== '') {
        $unchanged['code'] = $lock;

        return $unchanged;
    }

    $progress_block = noelclark_v1_mail_room_author_progress_block(
        $request_type,
        'done',
        $want,
        '',
        $notes,
        $original_choice,
        $original_credit,
        $proposed,
        $credit,
        ''
    );

    if ($progress_block !== '') {
        $unchanged['code'] = $progress_block;

        return $unchanged;
    }

    if ($treatment === $proposed) {
        return array(
            'code'      => 'ok',
            'treatment' => $proposed,
            'credit'    => $credit,
        );
    }

    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$begun['state']} st INNER JOIN " . noelclark_v1_mail_room_table('mr_submissions') . ' s ON s.id = st.mr_id '
            . 'SET st.operative_treatment = %s WHERE s.mr_ref = %s',
            $proposed,
            $mr_ref
        )
    );

    if ($updated === false || $wpdb->last_error !== '' || (int) $updated < 1) {
        $unchanged['code'] = 'storage';

        return $unchanged;
    }

    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_OPERATIVE_TREATMENT_CHANGED,
        array(
            'from' => $treatment,
            'to'   => $proposed,
        ),
        current_time('mysql', true)
    );

    if (!$saved) {
        $unchanged['code'] = 'storage';

        return $unchanged;
    }

    return array(
        'code'      => 'ok',
        'treatment' => $proposed,
        'credit'    => $credit,
    );
}

/**
 * The old name box cannot change the name. A request has to be written down first.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_operative_credit() {
    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_operative_credit_' . $mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'credit', 'error', 'closed');
}
add_action('admin_post_noelclark_v1_mail_room_set_operative_credit', 'noelclark_v1_mail_room_admin_handle_operative_credit');

/**
 * Record our check that one logged request asked for one exact public credit.
 *
 * The saved credit and the original consent are not written.
 *
 * @return void
 */
function noelclark_v1_mail_room_admin_handle_author_credit_confirmation() {
    global $wpdb;

    if (!noelclark_v1_mail_room_admin_can_read()) {
        wp_die(esc_html('Sorry, you are not allowed to access this page.'), '', array('response' => 403));
    }

    $mr_ref = noelclark_v1_mail_room_admin_normalize_ref(noelclark_v1_mail_room_admin_post_string('mr'));

    if ($mr_ref === null) {
        wp_die(esc_html('Invalid reference ID.'), '', array('response' => 400));
    }

    check_admin_referer('noelclark_v1_mail_room_author_credit_confirm_' . $mr_ref);

    $credit      = sanitize_text_field(noelclark_v1_mail_room_admin_post_string('author_credit_confirmed_name'));
    $request_key = noelclark_v1_mail_room_admin_post_string('author_request_key');
    $attested    = noelclark_v1_mail_room_admin_post_string('author_credit_attested');

    if (!preg_match('/^[a-f0-9]{16}$/', $request_key)) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'request');
    }

    if ($credit === '' || strlen($credit) > 200) {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', $credit === '' ? 'invalid' : 'length');
    }

    if ($attested !== '1') {
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'attest');
    }

    $begun = noelclark_v1_mail_room_admin_request_write_or_redirect(
        noelclark_v1_mail_room_admin_begin_author_write($mr_ref),
        $mr_ref,
        'cconf'
    );

    if ($begun === null) {
        return;
    }

    $logged = noelclark_v1_mail_room_admin_author_logged_payload($begun['events'], $request_key);

    if ($logged === null || (isset($logged['request_type']) ? (string) $logged['request_type'] : '') !== 'credit') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'request');
    }

    $found = noelclark_v1_mail_room_author_credit_from_words(isset($logged['want']) ? (string) $logged['want'] : '');

    if (!isset($found['status']) || $found['status'] !== 'clear' || (string) $found['credit'] !== $credit) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', (!isset($found['status']) || $found['status'] !== 'clear') ? 'clarify' : 'mismatch');
    }

    $quote    = isset($logged['quote']) ? (string) $logged['quote'] : '';
    $asked_on = isset($logged['asked_on']) ? (string) $logged['asked_on'] : '';

    if (trim($quote) === '') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'words');
    }

    if (!noelclark_v1_mail_room_admin_author_valid_date($asked_on)) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'date');
    }

    $original = isset($begun['row']['public_credit']) ? trim((string) $begun['row']['public_credit']) : '';
    $email    = isset($begun['row']['email']) ? trim((string) $begun['row']['email']) : '';
    $choice   = isset($begun['row']['sharing_choice']) ? (string) $begun['row']['sharing_choice'] : '';
    $relation = noelclark_v1_mail_room_author_credit_relation($credit, $original, $original);

    if ($relation !== 'broader' && $relation !== 'different') {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'narrow');
    }

    if ($email !== '' && strtolower($credit) === strtolower($email)) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'email');
    }

    if (noelclark_v1_mail_room_author_credit_consent_conflict($choice, $original, $credit)) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'consent');
    }

    $user_id = (int) get_current_user_id();

    if ($user_id < 1) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'user');
    }

    $parties = noelclark_v1_mail_room_admin_author_credit_parties(
        isset($begun['events']) && is_array($begun['events']) ? $begun['events'] : array()
    );
    $prospective = array(
        'status'      => 'confirmed',
        'user_id'     => (string) $user_id,
        'request_key' => $request_key,
        'credit'      => $credit,
        'asked_on'    => $asked_on,
        'evidence'    => $quote,
        'attestation' => noelclark_v1_mail_room_author_credit_attestation($credit),
    );
    $bound_request = array(
        'request_key' => $request_key,
        'credit'      => $credit,
        'asked_on'    => $asked_on,
        'quote'       => $quote,
    );

    if (!noelclark_v1_mail_room_author_credit_confirmation_valid($prospective, $bound_request, $parties['others'])) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'conflict');
    }

    $confirmed_on = current_time('Y-m-d', true);
    $now          = current_time('mysql', true);
    $confirmation = array(
        'request_key'  => $request_key,
        'credit'       => $credit,
        'status'       => 'confirmed',
        'asked_on'     => $asked_on,
        'confirmed_on' => $confirmed_on,
        'evidence'     => $quote,
        'user_id'      => (string) $user_id,
        'attestation'  => noelclark_v1_mail_room_author_credit_attestation($credit),
    );
    $saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED,
        $confirmation,
        $now
    );

    if (!$saved) {
        $wpdb->query('ROLLBACK');
        noelclark_v1_mail_room_admin_log('request_write_error');
        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'storage');
    }

    $events = isset($begun['events']) && is_array($begun['events']) ? $begun['events'] : array();
    $events[] = array(
        'event_type'   => NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_CREDIT_CONFIRMED,
        'payload_json' => wp_json_encode($confirmation),
    );
    $result = noelclark_v1_mail_room_admin_author_apply_logged_change(
        $begun,
        $mr_ref,
        'credit',
        isset($logged['want']) ? (string) $logged['want'] : '',
        '',
        $request_key,
        $events
    );

    if (!isset($result['code']) || $result['code'] !== 'ok') {
        $wpdb->query('ROLLBACK');

        if (isset($result['code']) && $result['code'] === 'storage') {
            noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'storage');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'refused');
    }

    $progress_saved = noelclark_v1_mail_room_admin_insert_request_event(
        $begun['events_table'],
        (int) $begun['row']['mr_id'],
        NOELCLARK_V1_MAIL_ROOM_EVENT_AUTHOR_REQUEST_PROGRESS,
        array(
            'request_key'        => $request_key,
            'progress'           => 'done',
            'handled_on'         => $confirmed_on,
            'notes'              => '',
            'credit_on_file'     => isset($result['credit']) ? (string) $result['credit'] : '',
            'permission_on_file' => isset($result['treatment']) ? (string) $result['treatment'] : '',
        ),
        $now
    );

    if (!$progress_saved || !noelclark_v1_mail_room_admin_commit_request_write()) {
        if (!$progress_saved) {
            $wpdb->query('ROLLBACK');
            noelclark_v1_mail_room_admin_log('request_write_error');
        }

        noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'cconf', 'error', 'storage');
    }

    noelclark_v1_mail_room_publication_sync_after_permission($mr_ref);
    noelclark_v1_mail_room_admin_redirect_request($mr_ref, 'areq', 'done');
}
add_action('admin_post_noelclark_v1_mail_room_confirm_author_credit', 'noelclark_v1_mail_room_admin_handle_author_credit_confirmation');

/**
 * Sharing Permissions, including the credit we are using now.
 *
 * @param array<string, mixed> $row Letter row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_author_sharing($row, $parsed) {
    $credit = isset($row['public_credit']) ? (string) $row['public_credit'] : '';
    $original = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $current = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $current_credit = isset($row['operative_public_credit']) ? (string) $row['operative_public_credit'] : '';
    $has_state = isset($row['editorial_status']) && $row['editorial_status'] !== null && $row['editorial_status'] !== '';

    echo '<details class="fold nc-mr-admin__sharing-fold">';
    echo '<summary>' . esc_html('Sharing') . '</summary>';
    echo '<p class="muted nc-mr-admin__request-help">' . esc_html('This is how we share it now. The name they sent, and how they allowed it to be shared, stay as they sent them.') . '</p>';
    echo '<dl class="kv nc-mr-admin__permission-facts">';
    echo '<dt>' . esc_html('How they allowed it to be shared') . '</dt>';
    echo '<dd>' . esc_html(noelclark_v1_mail_room_admin_permission_label($original, $credit)) . '</dd>';
    echo '<dt>' . esc_html('How we share it now') . '</dt><dd>';
    noelclark_v1_mail_room_admin_render_current_permission($row, $parsed, $has_state, $original, $credit, $current);
    echo '</dd>';
    echo '<dt>' . esc_html('The name they sent') . '</dt>';
    echo '<dd>' . ($credit !== '' ? esc_html($credit) : noelclark_v1_mail_room_admin_muted_html('None')) . '</dd>';
    echo '<dt>' . esc_html('The name we use now') . '</dt><dd>';
    echo $current_credit !== '' ? esc_html($current_credit) : noelclark_v1_mail_room_admin_muted_html('None');

    $credit_relation = noelclark_v1_mail_room_author_credit_relation(trim($current_credit), trim($credit), trim($credit));

    if ($credit_relation === 'broader' || $credit_relation === 'different') {
        if (!empty($row['permission_events_unconfirmed'])) {
            echo '<p class="muted">' . esc_html("We couldn't check this credit, so we're not treating the broader name as authorized.") . '</p>';
        } elseif (!isset($row['author_credit_broadening_valid']) || $row['author_credit_broadening_valid'] !== true) {
            $original_name = trim($credit);

            if ($original_name !== '') {
                echo '<p class="muted">' . esc_html('Not confirmed yet. Share as ' . $original_name . ' for now.') . '</p>';
            } else {
                echo '<p class="muted">' . esc_html("Not confirmed yet. We can't use a public name for now.") . '</p>';
            }
        }
    }

    echo '</dd></dl>';
    echo '</details>';
}

/**
 * Confirmation for one broader credit request. Their words stay beside the fields.
 *
 * @param array<string, mixed> $entry Log row.
 * @param array<string, mixed> $letter Letter row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @param array<int, array<string, mixed>> $event_rows Event rows.
 * @return bool Whether this is the one Save that checks the name and uses it.
 */
function noelclark_v1_mail_room_admin_render_author_credit_confirmation($entry, $letter, $parsed, $event_rows) {
    if ((string) $entry['source'] !== 'log' || (string) $entry['type'] !== 'credit' || (string) $entry['key'] === '') {
        return false;
    }

    $found = noelclark_v1_mail_room_author_credit_from_words((string) $entry['want']);

    if (!isset($found['status']) || $found['status'] !== 'clear') {
        return false;
    }

    $credit   = (string) $found['credit'];
    $original = isset($letter['public_credit']) ? (string) $letter['public_credit'] : '';
    $choice   = isset($letter['sharing_choice']) ? (string) $letter['sharing_choice'] : '';

    if (noelclark_v1_mail_room_author_credit_consent_conflict($choice, $original, $credit)) {
        return false;
    }

    $relation = noelclark_v1_mail_room_author_credit_relation($credit, $original, $original);

    if ($relation !== 'broader' && $relation !== 'different') {
        return false;
    }

    $quote = (string) $entry['quote'];
    $key   = (string) $entry['key'];
    $mr_ref = (string) $letter['mr_ref'];

    echo '<div class="nc-mr-admin__request-detail">';
    echo '<p class="muted">' . esc_html('Their words') . '</p>';
    echo '<p>' . ($quote !== '' ? esc_html($quote) : noelclark_v1_mail_room_admin_muted_html('Not recorded')) . '</p>';

    if (trim($quote) === '') {
        echo '<p class="muted">' . esc_html('We don\'t have their words on this request, so we can\'t confirm a broader credit.') . '</p>';
        echo '</div>';

        return false;
    }

    if (!noelclark_v1_mail_room_admin_author_valid_date((string) $entry['asked_on'])) {
        echo '<p class="muted">' . esc_html('We don\'t have a usable date for when they asked, so we can\'t confirm a broader credit.') . '</p>';
        echo '</div>';

        return false;
    }

    $parties = noelclark_v1_mail_room_admin_author_credit_parties($event_rows);

    if (noelclark_v1_mail_room_author_credit_later_conflict((string) $entry['asked_on'], $credit, $key, $parties['others'])) {
        echo '<p class="muted">' . esc_html("Another request from the writer is dated the same day or later, so we can't use a confirmation to broaden the credit.") . '</p>';
        echo '</div>';

        return false;
    }

    if (noelclark_v1_mail_room_admin_author_matching_confirmation($event_rows, (string) $entry['want'], $key) === $credit) {
        echo '<p>' . esc_html('We confirmed this exact credit for this request: ' . $credit . '.') . '</p>';
        echo '</div>';

        return false;
    }

    echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('noelclark_v1_mail_room_author_credit_confirm_' . $mr_ref);
    echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_confirm_author_credit">';
    echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
    echo '<input type="hidden" name="author_request_key" value="' . esc_attr($key) . '">';
    noelclark_v1_mail_room_admin_render_return_inputs($parsed);
    echo '<label for="nc-mr-confirm-credit-' . esc_attr($key) . '">' . esc_html('We read their words above, and they asked for exactly this credit:') . '</label>';
    echo '<input id="nc-mr-confirm-credit-' . esc_attr($key) . '" type="text" name="author_credit_confirmed_name" maxlength="200" value="" autocomplete="off" required>';
    echo '<label><input type="checkbox" name="author_credit_attested" value="1" required> ' . esc_html('We read their words above, and they asked for exactly this credit.') . '</label>';
    echo '<p class="muted">' . esc_html('This checks their words, then uses this name. The name they sent stays as they sent it.') . '</p>';
    echo '<button type="submit" class="btn">' . esc_html('Save') . '</button>';
    echo '</form></div>';

    return true;
}

/**
 * @param array<string, mixed> $entry Log row.
 * @param array<string, mixed> $letter Letter row.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_author_entry($entry, $letter, $parsed, $event_rows = array()) {
    $channels = noelclark_v1_mail_room_admin_author_channels();
    $labels = noelclark_v1_mail_room_admin_author_progress_labels();
    $mr_ref = (string) $letter['mr_ref'];
    $asked = noelclark_v1_mail_room_admin_author_ask_text((string) $entry['type'], (string) $entry['want']);
    $original_name = trim((string) $entry['consent_credit']);
    $current_name = isset($letter['operative_public_credit']) ? trim((string) $letter['operative_public_credit']) : '';

    echo '<details class="nc-mr-admin__request-line">';
    echo '<summary>' . esc_html(noelclark_v1_mail_room_admin_author_compact_line($entry)) . '</summary>';
    echo '<div class="nc-mr-admin__request-detail">';

    if ($entry['source'] !== 'log') {
        echo '<p class="muted">' . esc_html('This is an older request. The date is when we recorded it. We don\'t have a separate date for when they asked.') . '</p>';
    }

    if ($entry['note'] !== '') {
        echo '<p class="muted">' . esc_html((string) $entry['note']) . '</p>';
    }

    if ($entry['type'] === 'credit') {
        $found = noelclark_v1_mail_room_author_credit_from_words((string) $entry['want']);

        if (!isset($found['status']) || $found['status'] !== 'clear') {
            echo '<p class="muted">' . esc_html('We can\'t tell which name this asks for, so this stays No.') . '</p>';
        } elseif (noelclark_v1_mail_room_author_credit_consent_conflict(
            isset($letter['sharing_choice']) ? (string) $letter['sharing_choice'] : '',
            isset($letter['public_credit']) ? (string) $letter['public_credit'] : '',
            (string) $found['credit']
        )) {
            echo '<p class="muted">' . esc_html('They didn\'t allow a public name, so this stays No.') . '</p>';
        }
    }

    $channel = isset($channels[$entry['channel']]) ? $channels[$entry['channel']] : '';

    echo '<dl class="kv">';
    echo '<dt>' . esc_html('Originally') . '</dt><dd>' . esc_html('The name they sent: ' . ($original_name !== '' ? $original_name : 'None')) . '<br>' . esc_html('How they allowed it to be shared: ' . noelclark_v1_mail_room_admin_permission_label((string) $entry['consent_choice'], (string) $entry['consent_credit'])) . '</dd>';
    echo '<dt>' . esc_html('When they contacted me') . '</dt><dd>' . ($entry['asked_on'] !== '' ? esc_html((string) $entry['asked_on']) : noelclark_v1_mail_room_admin_muted_html('Not recorded')) . '</dd>';
    echo '<dt>' . esc_html('How they contacted me') . '</dt><dd>' . ($channel !== '' ? esc_html($channel) : noelclark_v1_mail_room_admin_muted_html('Not recorded')) . '</dd>';
    echo '<dt>' . esc_html('What they said') . '</dt><dd>' . ($entry['quote'] !== '' ? esc_html((string) $entry['quote']) : noelclark_v1_mail_room_admin_muted_html('Not recorded')) . '</dd>';
    echo '<dt>' . esc_html('They asked for') . '</dt><dd>' . ($asked !== '' ? esc_html($asked) : noelclark_v1_mail_room_admin_muted_html('Not recorded')) . '</dd>';
    echo '<dt>' . esc_html('Made the change?') . '</dt><dd>' . esc_html(isset($labels[$entry['progress']]) ? $labels[$entry['progress']] : 'No') . '</dd>';
    echo '<dt>' . esc_html('Now') . '</dt><dd>' . esc_html('The name we use now: ' . ($current_name !== '' ? $current_name : 'None')) . '<br>' . esc_html('How we share it now: ' . noelclark_v1_mail_room_admin_permission_label(isset($letter['operative_treatment']) ? (string) $letter['operative_treatment'] : '', $current_name)) . '</dd>';
    echo '</dl>';

    if ($entry['progress'] === 'done' && ($entry['type'] === 'credit' || $entry['type'] === 'take_down')) {
        echo '<p>' . esc_html('Did we catch it everywhere? The letter page, the archive, anything we shared.') . '</p>';
    }

    $confirm_form = noelclark_v1_mail_room_admin_render_author_credit_confirmation($entry, $letter, $parsed, $event_rows);

    if (!empty($entry['legacy_open']) && $entry['source'] === 'legacy_withdrawal') {
        noelclark_v1_mail_room_admin_render_decision_form(
            $mr_ref,
            $parsed,
            'noelclark_v1_mail_room_record_withdrawal_decision',
            'noelclark_v1_mail_room_withdrawal_decision_'
        );
    } elseif (!empty($entry['legacy_open']) && $entry['source'] === 'legacy_permission') {
        noelclark_v1_mail_room_admin_render_decision_form(
            $mr_ref,
            $parsed,
            'noelclark_v1_mail_room_record_permission_decision',
            'noelclark_v1_mail_room_permission_decision_',
            !empty($entry['allow_grant'])
        );
    } elseif ($entry['source'] === 'log' && $entry['key'] !== '') {
        $block = noelclark_v1_mail_room_author_progress_block(
            (string) $entry['type'],
            'done',
            (string) $entry['want'],
            (string) $entry['quote'],
            (string) $entry['notes'],
            isset($letter['sharing_choice']) ? (string) $letter['sharing_choice'] : '',
            isset($letter['public_credit']) ? (string) $letter['public_credit'] : '',
            isset($letter['operative_treatment']) ? (string) $letter['operative_treatment'] : '',
            isset($letter['operative_public_credit']) ? (string) $letter['operative_public_credit'] : '',
            noelclark_v1_mail_room_admin_author_matching_confirmation($event_rows, (string) $entry['want'], (string) $entry['key'])
        );

        if ($block !== '' && !($confirm_form === true && $block === 'authorize')) {
            echo '<p class="muted">' . esc_html(noelclark_v1_mail_room_admin_author_block_message($block)) . '</p>';
        }

        echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('noelclark_v1_mail_room_author_progress_' . $mr_ref);
        echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_progress_author_request">';
        echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
        echo '<input type="hidden" name="author_request_key" value="' . esc_attr((string) $entry['key']) . '">';
        noelclark_v1_mail_room_admin_render_return_inputs($parsed);
        echo '<label for="nc-mr-progress-' . esc_attr((string) $entry['key']) . '">' . esc_html('Made the change?') . '</label>';
        echo '<select id="nc-mr-progress-' . esc_attr((string) $entry['key']) . '" name="author_progress">';
        $showed_yes = false;

        foreach ($labels as $stored => $label) {
            if ($stored === 'done' && $block !== '' && $block !== 'notes' && (string) $entry['progress'] !== 'done') {
                continue;
            }

            if ($stored === 'done') {
                $showed_yes = true;
            }

            echo '<option value="' . esc_attr($stored) . '"' . selected($stored, (string) $entry['progress'], false) . '>' . esc_html($label) . '</option>';
        }

        if (!$showed_yes && $confirm_form !== true) {
            echo '<option value="done">' . esc_html('Yes') . '</option>';
        }

        echo '</select>';
        echo '<button type="submit" class="btn">' . esc_html('Save') . '</button>';
        echo '</form>';
    }

    echo '</div></details>';
}

/**
 * The request log, collapsed, directly above History.
 *
 * @param array<string, mixed> $row    Letter row.
 * @param array<string, mixed> $events Event loader result.
 * @param array{filters: array{status: string, share: string, ref: string}, page: int, ref_invalid: bool, notices: string[]} $parsed Parsed request.
 * @return void
 */
function noelclark_v1_mail_room_admin_render_author_log($row, $events, $parsed) {
    $event_rows = (!empty($events['ok']) && isset($events['rows']) && is_array($events['rows'])) ? $events['rows'] : array();
    $log_rows = noelclark_v1_mail_room_admin_author_log_rows($event_rows, $row);
    $heading = $log_rows === array()
        ? 'Requests from the letter author'
        : 'The letter author asked us to change something';
    $has_state = isset($row['editorial_status']) && $row['editorial_status'] !== null && $row['editorial_status'] !== '';
    $mr_ref = (string) $row['mr_ref'];
    $types = noelclark_v1_mail_room_admin_author_type_choices();
    $channels = noelclark_v1_mail_room_admin_author_channels();
    $original = isset($row['sharing_choice']) ? (string) $row['sharing_choice'] : '';
    $original_credit = isset($row['public_credit']) ? (string) $row['public_credit'] : '';
    $current = isset($row['operative_treatment']) ? (string) $row['operative_treatment'] : '';
    $current_credit = isset($row['operative_public_credit']) ? (string) $row['operative_public_credit'] : '';
    $share_choices = noelclark_v1_mail_room_admin_operative_choices($original, $original_credit);
    $share_words = array(
        'not_public'   => 'Keep it private',
        'anonymous'    => 'Anonymously',
        'named_credit' => 'With their name',
    );

    echo '<details class="fold">';
    echo '<summary>' . esc_html($heading) . '</summary>';
    echo '<div class="nc-mr-admin__requests">';

    if (!$has_state) {
        echo '<p class="muted">' . esc_html('This letter has no status record, so a request cannot be saved.') . '</p>';
        echo '</div></details>';

        return;
    }

    if ($log_rows === array()) {
        echo '<p class="nc-mr-admin__request-answer">' . esc_html("Nothing yet. They're happy with how it is.") . '</p>';
    } else {
        if (noelclark_v1_mail_room_admin_author_log_conflicts($log_rows)) {
            echo '<p>' . esc_html('These requests conflict. We\'re following the strictest one until the others are resolved.') . '</p>';
        }

        foreach ($log_rows as $entry) {
            noelclark_v1_mail_room_admin_render_author_entry($entry, $row, $parsed, $event_rows);
        }
    }

    echo '<div class="nc-mr-admin__new-request">';
    echo '<input type="checkbox" id="nc-mr-log-request" class="nc-mr-admin__new-request-toggle">';
    echo '<label class="btn" for="nc-mr-log-request">' . esc_html('Add a request') . '</label>';
    echo '<div class="nc-mr-admin__new-request-forms">';
    echo '<form class="nc-mr-admin__request-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('noelclark_v1_mail_room_author_log_' . $mr_ref);
    echo '<input type="hidden" name="action" value="noelclark_v1_mail_room_log_author_request">';
    echo '<input type="hidden" name="mr" value="' . esc_attr($mr_ref) . '">';
    noelclark_v1_mail_room_admin_render_return_inputs($parsed);
    echo '<dl class="kv">';
    echo '<dt>' . esc_html('Originally') . '</dt><dd>' . esc_html('The name they sent: ' . (trim($original_credit) !== '' ? trim($original_credit) : 'None')) . '<br>' . esc_html('How they allowed it to be shared: ' . noelclark_v1_mail_room_admin_permission_label($original, $original_credit)) . '</dd>';
    echo '</dl>';
    echo '<label for="nc-mr-author-asked">' . esc_html('When they contacted me') . '</label>';
    echo '<input id="nc-mr-author-asked" type="date" name="author_asked_on" required>';
    echo '<label for="nc-mr-author-channel">' . esc_html('How they contacted me') . '</label>';
    echo '<select id="nc-mr-author-channel" name="author_channel">';

    foreach ($channels as $stored => $label) {
        echo '<option value="' . esc_attr($stored) . '">' . esc_html($label) . '</option>';
    }

    echo '</select>';
    echo '<label for="nc-mr-author-quote">' . esc_html('What they said') . '</label>';
    echo '<textarea id="nc-mr-author-quote" name="author_quote" rows="4" placeholder="' . esc_attr('Paste their words here.') . '"></textarea>';
    echo '<label for="nc-mr-author-type">' . esc_html('What is this about?') . '</label>';
    echo '<select id="nc-mr-author-type" name="author_type">';

    foreach ($types as $stored => $label) {
        echo '<option value="' . esc_attr($stored) . '">' . esc_html($label) . '</option>';
    }

    echo '</select>';
    echo '<p id="nc-mr-author-ask-label">' . esc_html('They asked for') . '</p>';
    echo '<div class="nc-mr-admin__ask" data-ask="credit">';
    echo '<label for="nc-mr-author-name">' . esc_html('The name they want') . '</label>';
    echo '<input id="nc-mr-author-name" type="text" name="author_name" maxlength="200" placeholder="' . esc_attr('Tom A.') . '">';
    echo '</div>';
    echo '<div class="nc-mr-admin__ask" data-ask="sharing restore">';
    echo '<label for="nc-mr-author-share">' . esc_html('How they want it shared') . '</label>';
    echo '<select id="nc-mr-author-share" name="author_share_choice">';

    foreach ($share_choices as $stored => $unused_label) {
        if (!isset($share_words[$stored])) {
            continue;
        }

        echo '<option value="' . esc_attr($stored) . '">' . esc_html($share_words[$stored]) . '</option>';
    }

    echo '</select></div>';
    echo '<div class="nc-mr-admin__ask" data-ask="take_down">';
    echo '<p>' . esc_html('Keep it private') . '</p>';
    echo '</div>';
    echo '<div class="nc-mr-admin__ask" data-ask="tweak other">';
    echo '<label for="nc-mr-author-want">' . esc_html('What they want') . '</label>';
    echo '<input id="nc-mr-author-want" type="text" name="author_want" maxlength="500">';
    echo '</div>';
    echo '<label for="nc-mr-author-made">' . esc_html('Made the change?') . '</label>';
    echo '<select id="nc-mr-author-made" name="author_made_change">';
    echo '<option value="no">' . esc_html('No') . '</option>';
    echo '<option value="yes">' . esc_html('Yes') . '</option>';
    echo '</select>';
    echo '<dl class="kv">';
    echo '<dt>' . esc_html('Now') . '</dt><dd>' . esc_html('The name we use now: ' . (trim($current_credit) !== '' ? trim($current_credit) : 'None')) . '<br>' . esc_html('How we share it now: ' . noelclark_v1_mail_room_admin_permission_label($current, $current_credit)) . '</dd>';
    echo '</dl>';
    echo '<button type="submit" class="btn">' . esc_html('Save this request') . '</button>';
    echo '</form>';
    echo '<script>(function () { var type = document.getElementById("nc-mr-author-type"); if (!type) { return; } var blocks = document.querySelectorAll(".nc-mr-admin__ask"); function show() { var value = type.value; Array.prototype.forEach.call(blocks, function (block) { var kinds = (block.getAttribute("data-ask") || "").split(" "); block.hidden = kinds.indexOf(value) === -1; }); } type.addEventListener("change", show); show(); }());</script>';
    echo '</div></div></div></details>';
}
