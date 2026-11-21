<?php
/**
 * Pure rules for the letter-author request log.
 *
 * Logging a request does not change sharing or credit. These functions only
 * read the words and the values already on file.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Collapse whitespace. The caller's words stay theirs.
 *
 * @param string $text Raw text.
 * @return string
 */
function noelclark_v1_mail_room_author_normalize_text($text) {
    $text = trim((string) $text);
    $text = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $text);

    while (strpos($text, '  ') !== false) {
        $text = str_replace('  ', ' ', $text);
    }

    return $text;
}

/**
 * Whether one character can continue a public name.
 *
 * @param string $char One character.
 * @return bool
 */
function noelclark_v1_mail_room_author_name_char($char) {
    if ($char === '') {
        return false;
    }

    $lower = strtolower($char);

    if ($lower >= 'a' && $lower <= 'z') {
        return true;
    }

    if ($char >= '0' && $char <= '9') {
        return true;
    }

    return false;
}

/**
 * The credit match ends here, rather than inside a longer name.
 *
 * A following word such as "thanks" can continue the sentence. Another name
 * token, such as the "A." after "Tom", means this was not the whole credit.
 *
 * @param string $rest Text after the exact credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_boundary($rest) {
    $rest = (string) $rest;

    if ($rest === '') {
        return true;
    }

    $first = substr($rest, 0, 1);

    if (noelclark_v1_mail_room_author_name_char($first)) {
        return false;
    }

    if ($first !== ' ') {
        return true;
    }

    $word  = '';
    $index = 0;
    $count = strlen($rest);

    while ($index < $count && substr($rest, $index, 1) === ' ') {
        $index = $index + 1;
    }

    while ($index < $count && noelclark_v1_mail_room_author_name_char(substr($rest, $index, 1))) {
        $word .= substr($rest, $index, 1);
        $index = $index + 1;
    }

    if ($word === '') {
        return true;
    }

    $stop  = array('thanks', 'thank', 'please', 'for', 'the', 'we', 'i', 'that', 'this', 'on', 'in', 'to', 'as', 'with', 'from', 'so', 'but');
    $lower = strtolower($word);
    $seen  = 0;
    $total = count($stop);

    while ($seen < $total) {
        if ($stop[$seen] === $lower) {
            return true;
        }

        $seen = $seen + 1;
    }

    return false;
}

/**
 * How many times a needle appears.
 *
 * @param string $text   Haystack.
 * @param string $needle Needle.
 * @return int
 */
function noelclark_v1_mail_room_author_count_needle($text, $needle) {
    $count = 0;
    $pos   = strpos((string) $text, (string) $needle);

    while ($pos !== false) {
        $count = $count + 1;
        $pos   = strpos((string) $text, (string) $needle, $pos + strlen((string) $needle));
    }

    return $count;
}

/**
 * Credit named by one ordinary cue, or empty when the cue is absent or muddy.
 *
 * @param string $text Original normalized wording.
 * @param string $cue  Lowercase cue, including its trailing space.
 * @return string
 */
function noelclark_v1_mail_room_author_credit_after_cue($text, $cue) {
    $lower = strtolower((string) $text);
    $cue   = (string) $cue;

    if (noelclark_v1_mail_room_author_count_needle($lower, $cue) !== 1) {
        return '';
    }

    $pos    = strpos($lower, $cue);
    $credit = trim(substr((string) $text, $pos + strlen($cue)));

    if ($credit === '' || strlen($credit) > 200) {
        return '';
    }

    $credit_lower = strtolower($credit);

    if (strpos($credit_lower, ' and ') !== false || strpos($credit_lower, ' or ') !== false) {
        return '';
    }

    return $credit;
}

/**
 * Identify the requested public credit without guessing.
 *
 * An arrow is accepted. So is an ordinary cue such as "change their credit to".
 * A bare name, or two different readings, is ambiguous.
 *
 * @param string $words What do they want?
 * @return array{status: string, credit: string}
 */
function noelclark_v1_mail_room_author_credit_from_words($words) {
    $result = array();
    $result['status'] = 'ambiguous';
    $result['credit'] = '';
    $text = noelclark_v1_mail_room_author_normalize_text($words);

    if ($text === '' || strlen($text) > 500) {
        return $result;
    }

    $arrow_count = noelclark_v1_mail_room_author_count_needle($text, '→');
    $arrow_count = $arrow_count + noelclark_v1_mail_room_author_count_needle($text, '->');
    $arrow_count = $arrow_count + noelclark_v1_mail_room_author_count_needle($text, '=>');

    if ($arrow_count > 1) {
        return $result;
    }

    if ($arrow_count === 1) {
        $arrow = '→';

        if (strpos($text, '→') === false) {
            $arrow = '->';
        }

        if (strpos($text, '→') === false && strpos($text, '->') === false) {
            $arrow = '=>';
        }

        $pos   = strpos($text, $arrow);
        $left  = trim(substr($text, 0, $pos));
        $right = trim(substr($text, $pos + strlen($arrow)));
        $right_lower = strtolower($right);

        if ($left === '' || $right === '' || strlen($right) > 200) {
            return $result;
        }

        if (strpos($right_lower, ' and ') !== false || strpos($right_lower, ' or ') !== false) {
            return $result;
        }

        $result['status'] = 'clear';
        $result['credit'] = $right;

        return $result;
    }

    $cues = array(
        'change their credit to ',
        'change the credit to ',
        'change my credit to ',
        'new credit is ',
        'new credit: ',
        'public credit: ',
        'credit them as ',
        'credit me as ',
        'credit as ',
    );
    $found = '';
    $index = 0;
    $total = count($cues);

    while ($index < $total) {
        $credit = noelclark_v1_mail_room_author_credit_after_cue($text, $cues[$index]);
        $index  = $index + 1;

        if ($credit === '') {
            continue;
        }

        if ($found !== '' && $found !== $credit) {
            return $result;
        }

        $found = $credit;
    }

    $lower = strtolower($text);

    if (strpos($lower, 'from ') === 0 && noelclark_v1_mail_room_author_count_needle($lower, ' to ') === 1) {
        $to_pos = strpos($lower, ' to ');
        $credit = trim(substr($text, $to_pos + 4));
        $credit_lower = strtolower($credit);

        if ($credit !== '' && strlen($credit) <= 200 && strpos($credit_lower, ' and ') === false && strpos($credit_lower, ' or ') === false) {
            if ($found !== '' && $found !== $credit) {
                return $result;
            }

            $found = $credit;
        }
    }

    if ($found === '') {
        return $result;
    }

    $result['status'] = 'clear';
    $result['credit'] = $found;

    return $result;
}

/**
 * The shorter credit is the start of the longer one, at a word boundary.
 *
 * @param string $shorter Candidate shortening.
 * @param string $longer  Longer credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_is_shorter($shorter, $longer) {
    $shorter = trim((string) $shorter);
    $longer  = trim((string) $longer);

    if ($shorter === '' || $longer === '' || $shorter === $longer) {
        return false;
    }

    return strpos($longer, $shorter . ' ') === 0;
}

/**
 * same, narrower, broader, or different. Compared with the credits on file.
 *
 * @param string $requested Requested public credit.
 * @param string $original  Original public credit.
 * @param string $current   Credit in use now.
 * @return string
 */
function noelclark_v1_mail_room_author_credit_relation($requested, $original, $current) {
    $requested = trim((string) $requested);
    $original  = trim((string) $original);
    $current   = trim((string) $current);

    if ($requested === '') {
        return 'ambiguous';
    }

    if ($requested === $current || ($current === '' && $requested === $original)) {
        return 'same';
    }

    $narrower = noelclark_v1_mail_room_author_credit_is_shorter($requested, $original)
        || noelclark_v1_mail_room_author_credit_is_shorter($requested, $current);
    $broader = noelclark_v1_mail_room_author_credit_is_shorter($original, $requested)
        || noelclark_v1_mail_room_author_credit_is_shorter($current, $requested);

    if ($narrower && !$broader) {
        return 'narrower';
    }

    if ($broader) {
        return 'broader';
    }

    return 'different';
}

/**
 * The words immediately before a credit cue take it back.
 *
 * @param string $prefix Text before the cue or the credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_prefix_negated($prefix) {
    $lower     = strtolower(trim((string) $prefix));
    $negations = array('do not', "don't", 'dont', 'never', 'not', 'cannot', "can't", 'cant', 'no');
    $index     = 0;
    $total     = count($negations);

    while ($index < $total) {
        $negation = $negations[$index];
        $index    = $index + 1;
        $tail     = ' ' . $negation;

        if ($lower === $negation) {
            return true;
        }

        if (strlen($lower) >= strlen($tail) && substr($lower, -strlen($tail)) === $tail) {
            return true;
        }
    }

    return false;
}

/**
 * Whether one cue in their words asks for this exact credit.
 *
 * A refusal before the cue does not count. The cue must ask for this credit,
 * not for some other action.
 *
 * @param string $quote  What they actually said.
 * @param string $cue    Lowercase cue.
 * @param string $credit Exact credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_cue_authorizes($quote, $cue, $credit) {
    $lower = strtolower((string) $quote);
    $pos   = strpos($lower, (string) $cue);
    $cue_length = strlen((string) $cue);
    $credit_length = strlen((string) $credit);

    while ($pos !== false) {
        $after = substr((string) $quote, $pos + $cue_length);

        if (substr($after, 0, $credit_length) === (string) $credit) {
            $rest = substr($after, $credit_length);

            if (noelclark_v1_mail_room_author_credit_boundary($rest) && !noelclark_v1_mail_room_author_credit_prefix_negated(substr((string) $quote, 0, $pos))) {
                return true;
            }
        }

        $pos = strpos($lower, (string) $cue, $pos + $cue_length);
    }

    return false;
}

/**
 * Phrase matching only. This does not authorize a public credit.
 *
 * Broader and different credits require a structured confirmation record.
 *
 * @param string $quote  What they actually said.
 * @param string $credit Exact requested credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_quote_authorizes($quote, $credit) {
    $quote  = noelclark_v1_mail_room_author_normalize_text($quote);
    $credit = trim((string) $credit);

    if ($quote === '' || $credit === '') {
        return false;
    }

    $cues = array(
        'credit me as ',
        'credit them as ',
        'credit as ',
        'my credit is ',
        'my credit: ',
        'my public credit is ',
        'my public name is ',
        'change my credit to ',
        'change the credit to ',
        'change their credit to ',
        'i authorize the credit ',
        'i authorise the credit ',
        'please credit me as ',
        'you can credit me as ',
    );
    $index = 0;
    $total = count($cues);

    while ($index < $total) {
        if (noelclark_v1_mail_room_author_cue_authorizes($quote, $cues[$index], $credit)) {
            return true;
        }

        $index = $index + 1;
    }

    $markers = array(' as my credit', ' as the credit', ' for the credit');
    $lower   = strtolower($quote);
    $index   = 0;
    $total   = count($markers);
    $credit_length = strlen($credit);

    while ($index < $total) {
        $marker = $markers[$index];
        $pos    = strpos($lower, $marker);
        $index  = $index + 1;

        while ($pos !== false) {
            $before = substr($quote, 0, $pos);

            if (substr($before, -$credit_length) === $credit) {
                $head = substr($before, 0, strlen($before) - $credit_length);
                $head_lower = strtolower($head);

                if (substr($head_lower, -4) === 'use ' && noelclark_v1_mail_room_author_credit_boundary(substr($quote, $pos)) && !noelclark_v1_mail_room_author_credit_prefix_negated(substr($head, 0, strlen($head) - 4))) {
                    $before_credit = substr($head, -1);

                    if ($before_credit === '' || $before_credit === ' ') {
                        return true;
                    }
                }
            }

            $pos = strpos($lower, $marker, $pos + strlen($marker));
        }
    }

    return false;
}

/**
 * A stored writer date, as YYYY-MM-DD.
 *
 * @param string $ymd Date.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_date_ok($ymd) {
    $ymd = (string) $ymd;

    if (strlen($ymd) !== 10 || substr($ymd, 4, 1) !== '-' || substr($ymd, 7, 1) !== '-') {
        return false;
    }

    $year  = substr($ymd, 0, 4);
    $month = substr($ymd, 5, 2);
    $day   = substr($ymd, 8, 2);

    if (!noelclark_v1_mail_room_author_credit_all_digits($year, 4) || !noelclark_v1_mail_room_author_credit_all_digits($month, 2) || !noelclark_v1_mail_room_author_credit_all_digits($day, 2)) {
        return false;
    }

    return checkdate($month, $day, $year);
}

/**
 * @param string $value  Digit text.
 * @param int    $length Required length.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_all_digits($value, $length) {
    $value = (string) $value;

    if (strlen($value) !== $length) {
        return false;
    }

    $index = 0;

    while ($index < $length) {
        $char  = substr($value, $index, 1);
        $index = $index + 1;

        if ($char < '0' || $char > '9') {
            return false;
        }
    }

    return true;
}

/**
 * A WordPress user id stored with a confirmation.
 *
 * @param string $value User id.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_user_ok($value) {
    $value  = (string) $value;
    $length = strlen($value);

    if ($length < 1 || $length > 20 || substr($value, 0, 1) === '0') {
        return false;
    }

    return noelclark_v1_mail_room_author_credit_all_digits($value, $length);
}

/**
 * The stable request key already used by the request log.
 *
 * @param string $value Request key.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_key_ok($value) {
    $value = (string) $value;

    if (strlen($value) !== 16) {
        return false;
    }

    $index = 0;

    while ($index < 16) {
        $char  = substr($value, $index, 1);
        $index = $index + 1;
        $digit = ($char >= '0' && $char <= '9');
        $lower = ($char >= 'a' && $char <= 'f');

        if (!$digit && !$lower) {
            return false;
        }
    }

    return true;
}

/**
 * The sentence the administrator attests. It records our check, not proof.
 *
 * @param string $credit Exact public credit.
 * @return string
 */
function noelclark_v1_mail_room_author_credit_attestation($credit) {
    return 'We read their words above, and they asked for exactly this credit: ' . (string) $credit;
}

/**
 * A writer request on the same day or later blocks this confirmation.
 *
 * Dates are the dates they asked, not the dates we recorded the events.
 * The more restrictive instruction wins when the order cannot be established.
 * A missing or unusable date fails closed.
 *
 * @param string                           $asked_on    Writer date on the confirmed request.
 * @param string                           $credit      Exact confirmed credit.
 * @param string                           $request_key Confirmed request key.
 * @param array<int, array<string, mixed>> $items       Other writer requests.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_later_conflict($asked_on, $credit, $request_key, $items) {
    if (!is_array($items) || !noelclark_v1_mail_room_author_credit_date_ok($asked_on)) {
        return true;
    }

    $index = 0;
    $total = count($items);

    while ($index < $total) {
        $item  = $items[$index];
        $index = $index + 1;

        if (!is_array($item)) {
            continue;
        }

        $other_key = isset($item['request_key']) ? (string) $item['request_key'] : '';

        if ($other_key !== '' && $other_key === (string) $request_key) {
            continue;
        }

        $type         = isset($item['type']) ? (string) $item['type'] : '';
        $other_credit = isset($item['credit']) ? (string) $item['credit'] : '';
        $other_asked  = isset($item['asked_on']) ? (string) $item['asked_on'] : '';
        $conflicts    = false;

        if ($type === 'take_down' || $type === 'anonymous') {
            $conflicts = true;
        } elseif ($type === 'credit' && ($other_credit === '' || $other_credit !== (string) $credit)) {
            $conflicts = true;
        }

        if (!$conflicts) {
            continue;
        }

        if ($type === 'credit' && $other_credit === '') {
            return true;
        }

        if (!noelclark_v1_mail_room_author_credit_date_ok($other_asked) || $other_asked >= (string) $asked_on) {
            return true;
        }
    }

    return false;
}

/**
 * Whether one confirmation can broaden this one request to this exact credit.
 *
 * The evidence text is compared as a snapshot. It is not read for phrases.
 *
 * @param array<string, mixed>             $confirmation Confirmation record.
 * @param array<string, mixed>             $request      Logged request snapshot.
 * @param array<int, array<string, mixed>> $items        Other writer requests.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_confirmation_valid($confirmation, $request, $items) {
    if (!is_array($confirmation) || !is_array($request) || !is_array($items)) {
        return false;
    }

    $status           = isset($confirmation['status']) ? (string) $confirmation['status'] : '';
    $user_id          = isset($confirmation['user_id']) ? (string) $confirmation['user_id'] : '';
    $confirm_key      = isset($confirmation['request_key']) ? (string) $confirmation['request_key'] : '';
    $confirm_credit   = isset($confirmation['credit']) ? (string) $confirmation['credit'] : '';
    $confirm_asked    = isset($confirmation['asked_on']) ? (string) $confirmation['asked_on'] : '';
    $confirm_evidence = isset($confirmation['evidence']) ? (string) $confirmation['evidence'] : '';
    $attestation      = isset($confirmation['attestation']) ? (string) $confirmation['attestation'] : '';
    $request_key      = isset($request['request_key']) ? (string) $request['request_key'] : '';
    $request_credit   = isset($request['credit']) ? (string) $request['credit'] : '';
    $request_asked    = isset($request['asked_on']) ? (string) $request['asked_on'] : '';
    $request_quote    = isset($request['quote']) ? (string) $request['quote'] : '';

    if ($status !== 'confirmed' || !noelclark_v1_mail_room_author_credit_user_ok($user_id)) {
        return false;
    }

    if (!noelclark_v1_mail_room_author_credit_key_ok($confirm_key) || $confirm_key !== $request_key) {
        return false;
    }

    if ($confirm_credit === '' || $confirm_credit !== $request_credit) {
        return false;
    }

    if (!noelclark_v1_mail_room_author_credit_date_ok($confirm_asked) || $confirm_asked !== $request_asked) {
        return false;
    }

    if ($request_quote === '' || $confirm_evidence !== $request_quote) {
        return false;
    }

    if ($attestation !== noelclark_v1_mail_room_author_credit_attestation($confirm_credit)) {
        return false;
    }

    if (noelclark_v1_mail_room_author_credit_later_conflict($request_asked, $confirm_credit, $confirm_key, $items)) {
        return false;
    }

    return true;
}

/**
 * A public name conflicts with original consent when they did not agree to be named.
 *
 * Asking for the original credit again is not a new consent question.
 *
 * @param string $original_choice Immutable sharing choice.
 * @param string $original_credit Immutable public credit.
 * @param string $requested       Requested public credit.
 * @return bool
 */
function noelclark_v1_mail_room_author_credit_consent_conflict($original_choice, $original_credit, $requested) {
    $requested = trim((string) $requested);
    $original_credit = trim((string) $original_credit);

    if ($requested === '' || $requested === $original_credit) {
        return false;
    }

    if ((string) $original_choice !== 'named_credit' || $original_credit === '') {
        return true;
    }

    return false;
}

/**
 * Sharing choice named in a request. A stored choice or an older phrase both count.
 *
 * @param string $words What they asked for.
 * @return string not_public, anonymous, named_credit, or empty.
 */
function noelclark_v1_mail_room_author_asked_level($words) {
    $text = strtolower(trim((string) $words));

    if ($text === 'not_public' || $text === 'anonymous' || $text === 'named_credit') {
        return $text;
    }

    return noelclark_v1_mail_room_author_restore_level($words);
}

/**
 * @param string $words What do they want?
 * @return string not_public, anonymous, named_credit, or empty when unclear.
 */
function noelclark_v1_mail_room_author_restore_level($words) {
    $lower = strtolower(noelclark_v1_mail_room_author_normalize_text($words));
    $hits  = 0;
    $level = '';

    if (strpos($lower, 'not for public sharing') !== false || strpos($lower, 'keep it private') !== false) {
        $hits  = $hits + 1;
        $level = 'not_public';
    }

    if (strpos($lower, 'anonymously') !== false) {
        $hits  = $hits + 1;
        $level = 'anonymous';
    }

    if (strpos($lower, 'with my name') !== false || strpos($lower, 'with their name') !== false) {
        $hits  = $hits + 1;
        $level = 'named_credit';
    }

    if ($hits !== 1) {
        return '';
    }

    return $level;
}

/**
 * Permission the letter follows. A broader operative value falls back to consent.
 *
 * @param string $original_choice   Immutable choice.
 * @param string $original_credit   Immutable credit.
 * @param string $current_treatment Operative treatment.
 * @param string $current_credit    Operative credit.
 * @return string
 */
function noelclark_v1_mail_room_author_effective_choice($original_choice, $original_credit, $current_treatment, $current_credit) {
    $original_rank = noelclark_v1_mail_room_permission_choice_rank($original_choice, $original_credit);
    $current_rank  = noelclark_v1_mail_room_permission_choice_rank($current_treatment, $current_credit);

    if ($current_rank > $original_rank && $original_rank >= 0) {
        return (string) $original_choice;
    }

    return (string) $current_treatment;
}

/**
 * Editorial revision does not exist yet. A later workflow can return true.
 *
 * @return bool
 */
function noelclark_v1_mail_room_author_letter_revision_ready() {
    return false;
}

/**
 * Why Done is unavailable. Empty when Done is allowed.
 *
 * Not doing it, and leaving a request still to do, do not need these checks.
 *
 * @param string $type              Request type.
 * @param string $progress          still_to_do, done, or not_doing.
 * @param string $words             What do they want?
 * @param string $quote             What they actually said.
 * @param string $notes             Notes to us.
 * @param string $original_choice   Immutable choice.
 * @param string $original_credit   Immutable credit.
 * @param string $current_treatment Operative treatment.
 * @param string $current_credit    Operative credit.
 * @param string $confirmed_credit  Exact credit on a structured confirmation, or empty.
 * @return string
 */
function noelclark_v1_mail_room_author_progress_block($type, $progress, $words, $quote, $notes, $original_choice, $original_credit, $current_treatment, $current_credit, $confirmed_credit = '') {
    if ((string) $progress !== 'done') {
        return '';
    }

    $effective = noelclark_v1_mail_room_author_effective_choice($original_choice, $original_credit, $current_treatment, $current_credit);

    if ((string) $type === 'tweak') {
        if (noelclark_v1_mail_room_author_letter_revision_ready()) {
            return '';
        }

        return 'revision';
    }

    if ((string) $type === 'other') {
        if (trim((string) $notes) === '') {
            return 'notes';
        }

        return '';
    }

    if ((string) $type === 'take_down') {
        if ($effective === 'not_public') {
            return '';
        }

        return 'sharing';
    }

    if ((string) $type === 'anonymous') {
        if ($effective === 'anonymous') {
            return '';
        }

        return 'sharing';
    }

    if ((string) $type === 'restore' || (string) $type === 'sharing') {
        $level = noelclark_v1_mail_room_author_asked_level($words);

        if ($level === '') {
            return 'clarify';
        }

        $original_rank = noelclark_v1_mail_room_permission_choice_rank($original_choice, $original_credit);
        $level_rank    = noelclark_v1_mail_room_permission_choice_rank($level, $original_credit);

        if ($level_rank < 0 || $level_rank > $original_rank) {
            return 'consent';
        }

        if ($effective !== $level) {
            return 'sharing';
        }

        return '';
    }

    if ((string) $type === 'credit') {
        $found = noelclark_v1_mail_room_author_credit_from_words($words);

        if (!isset($found['status']) || $found['status'] !== 'clear') {
            return 'clarify';
        }

        $credit = (string) $found['credit'];
        // Done is checked after the credit in use already matches. Measure the
        // change against the original credit, or a broadening looks unchanged.
        $relation = noelclark_v1_mail_room_author_credit_relation($credit, $original_credit, $original_credit);

        if (noelclark_v1_mail_room_author_credit_consent_conflict($original_choice, $original_credit, $credit)) {
            return 'consent';
        }

        if ($relation === 'broader' || $relation === 'different') {
            if (trim((string) $confirmed_credit) !== $credit) {
                return 'authorize';
            }
        }

        if (trim((string) $current_credit) !== $credit) {
            return 'credit';
        }

        return '';
    }

    return 'invalid';
}

/**
 * Strictest open request. takedown, then anonymous, then credit.
 *
 * Put it back up does not pause and does not widen sharing.
 * An anonymity request pauses only when anonymous is narrower than the
 * permission the letter follows.
 *
 * @param array<int, string> $types              Open request types.
 * @param bool               $anonymous_reduces  Whether anonymous would narrow sharing.
 * @return string takedown, anonymous, credit, or none.
 */
function noelclark_v1_mail_room_author_request_hold($types, $anonymous_reduces) {
    $has_down   = false;
    $has_anon   = false;
    $has_credit = false;
    $index      = 0;
    $count      = is_array($types) ? count($types) : 0;

    while ($index < $count) {
        $type = (string) $types[$index];
        $index = $index + 1;

        if ($type === 'take_down') {
            $has_down = true;
        }

        if ($type === 'anonymous') {
            $has_anon = true;
        }

        if ($type === 'credit') {
            $has_credit = true;
        }
    }

    if ($has_down) {
        return 'takedown';
    }

    if ($has_anon && $anonymous_reduces) {
        return 'anonymous';
    }

    if ($has_credit) {
        return 'credit';
    }

    return 'none';
}

/**
 * Open ranked requests disagree. Tweak and something else stay out of this.
 *
 * @param array<int, array{type?: string, detail?: string}> $items Open ranked rows.
 * @return bool
 */
function noelclark_v1_mail_room_author_requests_conflict($items) {
    $seen_down     = false;
    $seen_anon     = false;
    $seen_credit   = false;
    $seen_restore  = false;
    $credit_detail = '';
    $credit_many   = false;
    $restore_detail = '';
    $restore_many  = false;
    $index         = 0;
    $count         = is_array($items) ? count($items) : 0;

    while ($index < $count) {
        $item = $items[$index];
        $index = $index + 1;

        if (!is_array($item) || !isset($item['type'])) {
            continue;
        }

        $type   = (string) $item['type'];
        $detail = isset($item['detail']) ? (string) $item['detail'] : '';

        if ($type === 'take_down') {
            $seen_down = true;
        } elseif ($type === 'anonymous') {
            $seen_anon = true;
        } elseif ($type === 'credit') {
            $seen_credit = true;

            if ($credit_detail === '') {
                $credit_detail = $detail;
            } elseif ($credit_detail !== $detail) {
                $credit_many = true;
            }
        } elseif ($type === 'restore') {
            $seen_restore = true;

            if ($restore_detail === '') {
                $restore_detail = $detail;
            } elseif ($restore_detail !== $detail) {
                $restore_many = true;
            }
        }
    }

    $kinds = 0;

    if ($seen_down) {
        $kinds = $kinds + 1;
    }

    if ($seen_anon) {
        $kinds = $kinds + 1;
    }

    if ($seen_credit) {
        $kinds = $kinds + 1;
    }

    if ($seen_restore) {
        $kinds = $kinds + 1;
    }

    if ($kinds > 1 || $credit_many || $restore_many) {
        return true;
    }

    return false;
}

/**
 * completed, pending, or clear.
 *
 * A granted withdrawal, a finished takedown, or a decision we cannot read
 * is completed. An open withdrawal or a pending reduction is pending.
 * A decline or Not doing it does not lock.
 *
 * @param array<int, array{kind?: string, state?: string}> $items Lock rows.
 * @return string
 */
function noelclark_v1_mail_room_author_withdrawal_standing($items) {
    $completed = false;
    $pending   = false;
    $index     = 0;
    $count     = is_array($items) ? count($items) : 0;

    while ($index < $count) {
        $item  = $items[$index];
        $index = $index + 1;

        if (!is_array($item)) {
            $completed = true;
            continue;
        }

        $kind  = isset($item['kind']) ? (string) $item['kind'] : '';
        $state = isset($item['state']) ? (string) $item['state'] : '';

        if ($kind === 'withdrawal' || $kind === 'takedown') {
            if ($state === 'granted' || $state === 'done' || $state === 'unreadable') {
                $completed = true;
            } elseif ($state === 'pending') {
                $pending = true;
            } elseif ($state !== 'declined' && $state !== 'not_doing') {
                $completed = true;
            }
            continue;
        }

        if ($kind === 'anonymity' || $kind === 'reduction') {
            if ($state !== 'declined' && $state !== 'not_doing' && $state !== 'clear') {
                $pending = true;
            }
            continue;
        }

        $completed = true;
    }

    if ($completed) {
        return 'completed';
    }

    if ($pending) {
        return 'pending';
    }

    return 'clear';
}

/**
 * Why an ordinary sharing save has to stop, or an empty string when it can proceed.
 *
 * A completed withdrawal allows only not for public sharing. An open withdrawal
 * or a pending reduction still allows a narrower permission. The original
 * consent ceiling is a separate check and stays in force either way.
 *
 * @param string                                            $current  Current operative treatment.
 * @param string                                            $proposed Proposed operative treatment.
 * @param string                                            $credit   Credit used to rank named sharing.
 * @param array<int, array{kind?: string, state?: string}> $items    Lock rows.
 * @return string
 */
function noelclark_v1_mail_room_author_operative_save_block($current, $proposed, $credit, $items) {
    $current_rank  = noelclark_v1_mail_room_permission_choice_rank($current, $credit);
    $proposed_rank = noelclark_v1_mail_room_permission_choice_rank($proposed, $credit);
    $standing      = noelclark_v1_mail_room_author_withdrawal_standing($items);

    if ($proposed_rank < 0) {
        return 'unreadable';
    }

    if ($standing === 'completed' && (string) $proposed !== 'not_public') {
        return 'withdrawal';
    }

    if ($current_rank < 0 && (string) $proposed !== 'not_public') {
        return 'unreadable';
    }

    if ($standing === 'pending' && $current_rank >= 0 && $proposed_rank > $current_rank) {
        return 'pause';
    }

    return '';
}
