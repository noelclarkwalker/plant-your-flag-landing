<?php
/**
 * Mail Room publication rules.
 *
 * One operational row per letter will store a working draft and the last
 * approved copy. These functions decide the row. They do not render a page.
 *
 * Permission comes from noelclark_v1_mail_room_admin_letter_view(). This file
 * reads that view's mode and credit. It does not rank sharing choices, and it
 * does not read the original submission.
 *
 * mode anonymous — the letter view allows anonymous publication.
 * mode named — the letter view allows the credit it returns.
 * mode no — the letter view does not allow publication, including a pending
 * withdrawal, a private choice, or an unreadable permission.
 * mode yes — unused for letters. Treated as not publishable.
 *
 * History is an append-only mr_events row. created_at on that row is when.
 * A failed history write does not decide visibility. publication_public_view()
 * does, and it does not read permission_hold_reason.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Append-only approval of the working draft as the live copy. */
const NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_APPROVED = 'publication_approved';

/** @var string Append-only manual unpublish. The payload carries the reason. */
const NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_UNPUBLISHED = 'publication_unpublished';

/** @var string Append-only note that current permission hides the approved copy. */
const NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_HELD = 'publication_held';

/**
 * Trimmed string. Empty after trim means missing.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function noelclark_v1_mail_room_publication_text($value) {
    return trim((string) $value);
}

/**
 * String without trimming.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function noelclark_v1_mail_room_publication_string($value) {
    return (string) $value;
}

/**
 * Copy a publication state and keep the audience public.
 *
 * @param array<string, mixed> $publication Publication state.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_copy($publication) {
    $next = $publication;
    $next['audience'] = 'everyone';

    return $next;
}

/**
 * Empty working draft. No approved copy, and no public page.
 *
 * @param string $public_slug Permanent neutral identifier, or empty until one is assigned.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_blank($public_slug) {
    $result = array();
    $result['public_slug'] = noelclark_v1_mail_room_publication_string($public_slug);
    $result['draft_title'] = '';
    $result['draft_body'] = '';
    $result['draft_published_on'] = '';
    $result['live_title'] = '';
    $result['live_body'] = '';
    $result['live_published_on'] = '';
    $result['live_approval_mode'] = '';
    $result['visibility'] = 'draft';
    $result['audience'] = 'everyone';
    $result['unpublished_reason'] = '';
    $result['permission_hold_reason'] = '';

    return $result;
}

/**
 * Keep an existing identifier. Otherwise use the generated neutral identifier.
 *
 * The generated value must come from noelclark_v1_mail_room_generate_opaque_ref().
 * This function does not accept a name or a private reference.
 *
 * @param string $existing_slug  Identifier already stored for this letter.
 * @param string $generated_slug New neutral identifier.
 * @return string
 */
function noelclark_v1_mail_room_publication_assign_slug($existing_slug, $generated_slug) {
    $existing_slug = noelclark_v1_mail_room_publication_text($existing_slug);

    if ($existing_slug !== '') {
        return $existing_slug;
    }

    return noelclark_v1_mail_room_publication_text($generated_slug);
}

/**
 * Assign the permanent identifier once.
 *
 * @param string $existing_slug Identifier already stored for this letter.
 * @return string
 */
function noelclark_v1_mail_room_publication_new_slug($existing_slug) {
    $generated = '';

    if (function_exists('noelclark_v1_mail_room_generate_opaque_ref')) {
        $generated = noelclark_v1_mail_room_generate_opaque_ref();
    }

    return noelclark_v1_mail_room_publication_assign_slug($existing_slug, $generated);
}

/**
 * Save the working draft. The approved copy and its identifier stay as they are.
 *
 * @param array<string, mixed> $publication  Publication state.
 * @param string               $title        Draft title.
 * @param string               $body         Draft letter text.
 * @param string               $published_on Draft publication date.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_save_draft($publication, $title, $body, $published_on) {
    $next = noelclark_v1_mail_room_publication_copy($publication);
    $next['draft_title'] = noelclark_v1_mail_room_publication_string($title);
    $next['draft_body'] = noelclark_v1_mail_room_publication_string($body);
    $next['draft_published_on'] = noelclark_v1_mail_room_publication_text($published_on);

    return $next;
}

/**
 * Copy the original letter text into the working draft only.
 *
 * The caller keeps the original string. This function does not write
 * nc_mr_submissions.
 *
 * @param array<string, mixed> $publication Publication state.
 * @param string               $letter_body Original letter text.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_copy_original_into_draft($publication, $letter_body) {
    $next = noelclark_v1_mail_room_publication_copy($publication);
    $next['draft_body'] = noelclark_v1_mail_room_publication_string($letter_body);

    return $next;
}

/**
 * A refused publication action.
 *
 * @param string $code Refusal code.
 * @return array{ok: false, code: string}
 */
function noelclark_v1_mail_room_publication_refusal($code) {
    $result = array();
    $result['ok'] = false;
    $result['code'] = noelclark_v1_mail_room_publication_string($code);

    return $result;
}

/**
 * Approve the working draft as the live copy when current permission allows it.
 *
 * Refuses an empty title or an empty letter. Permission mode and credit must
 * already be the letter view. Anonymous approval stores no credit.
 *
 * @param array<string, mixed> $publication       Publication state.
 * @param string               $permission_mode   Letter-view mode.
 * @param string               $permission_credit Letter-view credit.
 * @param string               $user_id           Administrator who approved.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_approve($publication, $permission_mode, $permission_credit, $user_id) {
    $title = noelclark_v1_mail_room_publication_text($publication['draft_title']);
    $body = noelclark_v1_mail_room_publication_text($publication['draft_body']);
    $mode = noelclark_v1_mail_room_publication_text($permission_mode);
    $credit = noelclark_v1_mail_room_publication_text($permission_credit);
    $published_on = noelclark_v1_mail_room_publication_text($publication['draft_published_on']);

    if ($title === '') {
        return noelclark_v1_mail_room_publication_refusal('empty_title');
    }

    if ($body === '') {
        return noelclark_v1_mail_room_publication_refusal('empty_body');
    }

    if ($mode !== 'anonymous' && $mode !== 'named') {
        return noelclark_v1_mail_room_publication_refusal('permission');
    }

    if ($mode === 'named' && $credit === '') {
        return noelclark_v1_mail_room_publication_refusal('permission');
    }

    $stored_credit = '';

    if ($mode === 'named') {
        $stored_credit = $credit;
    }

    $next = noelclark_v1_mail_room_publication_copy($publication);
    $next['draft_title'] = $title;
    $next['draft_body'] = $body;
    $next['draft_published_on'] = $published_on;
    $next['live_title'] = $title;
    $next['live_body'] = $body;
    $next['live_published_on'] = $published_on;
    $next['live_approval_mode'] = $mode;
    $next['visibility'] = 'approved';
    $next['unpublished_reason'] = '';
    $next['permission_hold_reason'] = '';

    $payload = array();
    $payload['user_id'] = noelclark_v1_mail_room_publication_string($user_id);
    $payload['permission_mode'] = $mode;
    $payload['credit'] = $stored_credit;
    $payload['public_slug'] = noelclark_v1_mail_room_publication_string($publication['public_slug']);
    $payload['title'] = $title;
    $payload['published_on'] = $published_on;

    $result = array();
    $result['ok'] = true;
    $result['publication'] = $next;
    $result['event_type'] = NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_APPROVED;
    $result['payload'] = $payload;

    return $result;
}

/**
 * Hide the public letter by hand. The approved copy and the draft stay.
 *
 * @param array<string, mixed> $publication Publication state.
 * @param string               $reason      Why it was unpublished.
 * @param string               $user_id     Administrator who unpublished it.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_unpublish($publication, $reason, $user_id) {
    $reason = noelclark_v1_mail_room_publication_text($reason);
    $live_body = noelclark_v1_mail_room_publication_text($publication['live_body']);

    if ($reason === '') {
        return noelclark_v1_mail_room_publication_refusal('empty_reason');
    }

    if ($live_body === '' || (string) $publication['visibility'] === 'draft') {
        return noelclark_v1_mail_room_publication_refusal('not_published');
    }

    $next = noelclark_v1_mail_room_publication_copy($publication);
    $next['visibility'] = 'unpublished';
    $next['unpublished_reason'] = $reason;

    $payload = array();
    $payload['user_id'] = noelclark_v1_mail_room_publication_string($user_id);
    $payload['reason'] = $reason;
    $payload['public_slug'] = noelclark_v1_mail_room_publication_string($publication['public_slug']);

    $result = array();
    $result['ok'] = true;
    $result['publication'] = $next;
    $result['event_type'] = NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_UNPUBLISHED;
    $result['payload'] = $payload;

    return $result;
}

/**
 * Fields a reader may see. No other keys belong in this result.
 *
 * @param string $byline       Anonymous, or the current authorized credit.
 * @param string $title        Approved title.
 * @param string $body         Approved letter text.
 * @param string $published_on Approved publication date.
 * @return array{public: true, byline: string, title: string, body: string, published_on: string}
 */
function noelclark_v1_mail_room_publication_shown_view($byline, $title, $body, $published_on) {
    $shown = array();
    $shown['public'] = true;
    $shown['byline'] = noelclark_v1_mail_room_publication_string($byline);
    $shown['title'] = noelclark_v1_mail_room_publication_string($title);
    $shown['body'] = noelclark_v1_mail_room_publication_string($body);
    $shown['published_on'] = noelclark_v1_mail_room_publication_string($published_on);

    return $shown;
}

/**
 * Nothing is public.
 *
 * @return array{public: false, byline: string, title: string, body: string, published_on: string}
 */
function noelclark_v1_mail_room_publication_hidden_view() {
    $hidden = array();
    $hidden['public'] = false;
    $hidden['byline'] = '';
    $hidden['title'] = '';
    $hidden['body'] = '';
    $hidden['published_on'] = '';

    return $hidden;
}

/**
 * What the public address may show for the current letter view.
 *
 * An approved anonymous letter stays Anonymous when permission later becomes
 * named. The returned fields do not include that credit. A named approval
 * comes down when permission is now anonymous. Any other mode is hidden,
 * including a pending withdrawal. Manual unpublish is hidden even when
 * permission would allow the letter.
 *
 * @param array<string, mixed> $publication       Publication state.
 * @param string               $permission_mode   Letter-view mode.
 * @param string               $permission_credit Letter-view credit.
 * @return array{public: bool, byline: string, title: string, body: string, published_on: string}
 */
function noelclark_v1_mail_room_publication_public_view($publication, $permission_mode, $permission_credit) {
    $visibility = noelclark_v1_mail_room_publication_string($publication['visibility']);
    $approval = noelclark_v1_mail_room_publication_text($publication['live_approval_mode']);
    $mode = noelclark_v1_mail_room_publication_text($permission_mode);
    $credit = noelclark_v1_mail_room_publication_text($permission_credit);
    $live_title = noelclark_v1_mail_room_publication_text($publication['live_title']);
    $live_body = noelclark_v1_mail_room_publication_text($publication['live_body']);
    $published_on = noelclark_v1_mail_room_publication_text($publication['live_published_on']);

    if ($visibility !== 'approved' || $live_title === '' || $live_body === '') {
        return noelclark_v1_mail_room_publication_hidden_view();
    }

    if ($approval !== 'anonymous' && $approval !== 'named') {
        return noelclark_v1_mail_room_publication_hidden_view();
    }

    if ($mode === 'anonymous' && $approval === 'anonymous') {
        return noelclark_v1_mail_room_publication_shown_view('Anonymous', $live_title, $live_body, $published_on);
    }

    if ($mode === 'named' && $approval === 'anonymous') {
        return noelclark_v1_mail_room_publication_shown_view('Anonymous', $live_title, $live_body, $published_on);
    }

    if ($mode === 'named' && $approval === 'named' && $credit !== '') {
        return noelclark_v1_mail_room_publication_shown_view($credit, $live_title, $live_body, $published_on);
    }

    return noelclark_v1_mail_room_publication_hidden_view();
}

/**
 * Whether the permission change should record a hide.
 *
 * Recording is for an approved letter that the public check now hides.
 * The public check remains the authority when this record is not written.
 * A change from anonymous approval to named permission does not record a hide.
 *
 * @param array<string, mixed> $publication       Publication state.
 * @param string               $permission_mode   Letter-view mode.
 * @param string               $permission_credit Letter-view credit.
 * @return array{record_hold: bool, reason: string}
 */
function noelclark_v1_mail_room_publication_permission_effect($publication, $permission_mode, $permission_credit) {
    $result = array();
    $result['record_hold'] = false;
    $result['reason'] = '';
    $visibility = noelclark_v1_mail_room_publication_string($publication['visibility']);

    if ($visibility !== 'approved') {
        return $result;
    }

    $view = noelclark_v1_mail_room_publication_public_view($publication, $permission_mode, $permission_credit);

    if ($view['public']) {
        return $result;
    }

    $mode = noelclark_v1_mail_room_publication_text($permission_mode);
    $approval = noelclark_v1_mail_room_publication_text($publication['live_approval_mode']);
    $result['record_hold'] = true;
    $result['reason'] = 'not_permitted';

    if ($mode === 'anonymous' && $approval === 'named') {
        $result['reason'] = 'anonymous_switch';
    }

    return $result;
}

/**
 * Store the hide note on the approved row without changing the approved copy.
 *
 * @param array<string, mixed> $publication       Publication state.
 * @param string               $permission_mode   Letter-view mode.
 * @param string               $permission_credit Letter-view credit.
 * @return array<string, mixed>
 */
function noelclark_v1_mail_room_publication_apply_permission($publication, $permission_mode, $permission_credit) {
    $next = noelclark_v1_mail_room_publication_copy($publication);
    $effect = noelclark_v1_mail_room_publication_permission_effect($publication, $permission_mode, $permission_credit);
    $visibility = noelclark_v1_mail_room_publication_string($publication['visibility']);

    if ($effect['record_hold']) {
        $next['permission_hold_reason'] = $effect['reason'];
    } elseif ($visibility === 'approved') {
        $next['permission_hold_reason'] = '';
    }

    $result = array();
    $result['publication'] = $next;
    $result['record_hold'] = $effect['record_hold'];
    $result['reason'] = $effect['reason'];
    $result['event_type'] = '';

    if ($effect['record_hold']) {
        $result['event_type'] = NOELCLARK_V1_MAIL_ROOM_EVENT_PUBLICATION_HELD;
    }

    return $result;
}

/**
 * Read mode and credit from a letter view. A missing view is not publishable.
 *
 * @param mixed $permission_view Letter view, or anything else.
 * @return array{ok: bool, mode: string, credit: string}
 */
function noelclark_v1_mail_room_publication_permission_from_letter_view($permission_view) {
    $result = array();

    if (!is_array($permission_view) || !isset($permission_view['mode'])) {
        $result['ok'] = false;
        $result['mode'] = 'no';
        $result['credit'] = '';

        return $result;
    }

    $credit = '';

    if (isset($permission_view['credit'])) {
        $credit = noelclark_v1_mail_room_publication_string($permission_view['credit']);
    }

    $result['ok'] = true;
    $result['mode'] = noelclark_v1_mail_room_publication_string($permission_view['mode']);
    $result['credit'] = $credit;

    return $result;
}

/**
 * Byline a saved draft would use if approved under the current letter view.
 *
 * Anonymous permission returns Anonymous and does not use the credit.
 * An empty result means the draft would not be public.
 *
 * @param string $permission_mode   Letter-view mode.
 * @param string $permission_credit Letter-view credit.
 * @return string
 */
function noelclark_v1_mail_room_publication_preview_byline($permission_mode, $permission_credit) {
    $mode = noelclark_v1_mail_room_publication_text($permission_mode);
    $credit = noelclark_v1_mail_room_publication_text($permission_credit);

    if ($mode === 'anonymous') {
        return 'Anonymous';
    }

    if ($mode === 'named' && $credit !== '') {
        return $credit;
    }

    return '';
}

/**
 * Draft, Published, Unpublished, or hidden because of writer permission.
 *
 * @param array<string, mixed> $publication       Publication state.
 * @param string               $permission_mode   Letter-view mode.
 * @param string               $permission_credit Letter-view credit.
 * @return string
 */
function noelclark_v1_mail_room_publication_status_label($publication, $permission_mode, $permission_credit) {
    $visibility = noelclark_v1_mail_room_publication_string($publication['visibility']);

    if ($visibility === 'unpublished') {
        return 'Unpublished';
    }

    if ($visibility !== 'approved') {
        return 'Draft';
    }

    $view = noelclark_v1_mail_room_publication_public_view($publication, $permission_mode, $permission_credit);

    if ($view['public']) {
        return 'Published';
    }

    return 'Hidden because of writer permission';
}

/**
 * Search engines may fetch the public letter page and are asked not to index it.
 *
 * The public page sends this in markup and in the HTTP header. robots.txt
 * must not disallow the page, or the crawler cannot read the noindex instruction.
 *
 * @return array{robots_meta: string, header_name: string, header_value: string, robots_txt_disallow: bool}
 */
function noelclark_v1_mail_room_publication_index_instructions() {
    $result = array();
    $result['robots_meta'] = 'noindex';
    $result['header_name'] = 'X-Robots-Tag';
    $result['header_value'] = 'noindex';
    $result['robots_txt_disallow'] = false;

    return $result;
}

/**
 * History row facts. The event table's created_at is when this happened.
 *
 * @param string               $event_type Stored event type.
 * @param array<string, mixed> $payload    Event payload.
 * @param string               $created_at GMT timestamp.
 * @return array{event_type: string, payload: array<string, mixed>, created_at: string}
 */
function noelclark_v1_mail_room_publication_event_row($event_type, $payload, $created_at) {
    $result = array();
    $result['event_type'] = noelclark_v1_mail_room_publication_string($event_type);
    $result['payload'] = $payload;
    $result['created_at'] = noelclark_v1_mail_room_publication_string($created_at);

    return $result;
}
