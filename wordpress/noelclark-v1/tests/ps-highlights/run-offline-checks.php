<?php
/**
 * Offline P.S. highlights repair checks (no WordPress / no live DB).
 *
 * Usage: php tests/ps-highlights/run-offline-checks.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$fail = 0;

function assert_true(bool $cond, string $label): void {
    global $fail;
    if ($cond) {
        echo "PASS  {$label}\n";
    } else {
        echo "FAIL  {$label}\n";
        $fail++;
    }
}

echo "=== PHP syntax ===\n";
$php_files = array(
    $root . '/inc/ps-highlights.php',
    $root . '/inc/enqueue.php',
    $root . '/page-ps-returning-to-the-open-journal.php',
);
foreach ($php_files as $file) {
    $out = array();
    $code = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    assert_true($code === 0, 'php -l ' . basename($file));
}

echo "\n=== Config wiring (localize contract) ===\n";
$enqueue = file_get_contents($root . '/inc/enqueue.php');
$js      = file_get_contents($root . '/assets/js/ps-highlights.js');
$php     = file_get_contents($root . '/inc/ps-highlights.php');

assert_true(
    strpos($enqueue, "'noelclarkV1PsHighlights'") !== false,
    'enqueue localizes noelclarkV1PsHighlights'
);
assert_true(
    strpos($js, 'window.noelclarkV1PsHighlights') !== false,
    'JS reads window.noelclarkV1PsHighlights'
);
assert_true(
    strpos($js, 'window.noelclarkPsHighlights') === false,
    'JS does not read legacy noelclarkPsHighlights'
);
assert_true(
    strpos($php, "'configGlobal'") !== false && strpos($php, 'noelclarkV1PsHighlights') !== false,
    'frontend_config documents configGlobal name'
);

echo "\n=== Multiline comment sanitizer (PHP mirror) ===\n";
/* Mirror noelclark_v1_ps_highlights_note_text core rules without WP helpers. */
function mirror_note_text(string $value): string {
    $text = strip_tags($value);
    $text = str_replace(array("\r\n", "\r"), "\n", $text);
    $text = preg_replace('/[^\S\n]+/u', ' ', $text);
    $text = preg_replace('/\n{3,}/u', "\n\n", $text);
    return trim((string) $text);
}

assert_true(mirror_note_text("one line") === 'one line', 'one-line comment');
assert_true(
    mirror_note_text("para one\n\npara two") === "para one\n\npara two",
    'two paragraphs preserved'
);
assert_true(
    mirror_note_text("a\n\nb\n\nc") === "a\n\nb\n\nc",
    'several paragraphs preserved'
);
assert_true(
    mirror_note_text("a\n\n\n\nb") === "a\n\nb",
    'excess blank lines collapsed to one blank'
);
assert_true(mirror_note_text('') === '', 'empty submission invalid');
assert_true(mirror_note_text("   \n  \t  ") === '', 'whitespace-only invalid');
assert_true(
    mirror_note_text("hello   world") === 'hello world',
    'horizontal whitespace still collapsed'
);

echo "\n=== Passage normalize still collapses newlines ===\n";
function mirror_plain(string $value): string {
    $text = strip_tags($value);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
}
assert_true(
    mirror_plain("line one\nline two") === 'line one line two',
    'passage matching still collapses newlines'
);

echo "\n=== Context contiguous validation ===\n";
function mirror_strlen(string $t): int {
    return function_exists('mb_strlen') ? (int) mb_strlen($t) : strlen($t);
}
function mirror_substr(string $t, int $s, ?int $l = null): string {
    if (function_exists('mb_substr')) {
        return $l === null ? (string) mb_substr($t, $s) : (string) mb_substr($t, $s, $l);
    }
    return $l === null ? (string) substr($t, $s) : (string) substr($t, $s, $l);
}
function mirror_strpos(string $h, string $n, int $o = 0) {
    return function_exists('mb_strpos') ? mb_strpos($h, $n, $o) : strpos($h, $n, $o);
}
function mirror_context_match(string $essay, string $passage, string $prefix, string $suffix): bool {
    if ($essay === '' || $passage === '') {
        return false;
    }
    $offset = 0;
    $plen   = mirror_strlen($passage);
    while (($pos = mirror_strpos($essay, $passage, $offset)) !== false) {
        $before = mirror_substr($essay, 0, (int) $pos);
        $after  = mirror_substr($essay, (int) $pos + $plen);
        $actual_prefix = $prefix === ''
            ? ''
            : (mirror_strlen($before) >= mirror_strlen($prefix)
                ? mirror_substr($before, mirror_strlen($before) - mirror_strlen($prefix))
                : $before);
        $actual_suffix = $suffix === ''
            ? ''
            : (mirror_strlen($after) >= mirror_strlen($suffix)
                ? mirror_substr($after, 0, mirror_strlen($suffix))
                : $after);
        if ($actual_prefix === $prefix && $actual_suffix === $suffix) {
            return true;
        }
        $offset = (int) $pos + 1;
    }
    return false;
}

$essay = 'Alpha Hello world. Middle Hello world. Omega';
assert_true(
    mirror_context_match($essay, 'Hello world.', 'Alpha ', ' Middle') === true,
    'first occurrence context accepted'
);
assert_true(
    mirror_context_match($essay, 'Hello world.', ' Middle ', ' Omega') === true,
    'second occurrence context accepted'
);
assert_true(
    mirror_context_match($essay, 'Hello world.', 'Alpha ', ' Omega') === false,
    'mismatched first-prefix + last-suffix rejected'
);
assert_true(
    mirror_context_match($essay, 'Hello world.', 'NOTHERE ', ' Middle') === false,
    'forged prefix rejected'
);

echo "\n=== Identity distinguishes repeated passages ===\n";
$id1 = hash('sha256', "Hello world.\nAlpha \n Middle");
$id2 = hash('sha256', "Hello world.\n Middle \n Omega");
assert_true($id1 !== $id2, 'distinct identities for distinct locations');

echo "\n=== Concurrency mechanism presence ===\n";
assert_true(strpos($php, 'GET_LOCK') !== false, 'GET_LOCK present');
assert_true(strpos($php, 'RELEASE_LOCK') !== false, 'RELEASE_LOCK present');
assert_true(strpos($php, 'CAST(meta_value AS UNSIGNED) + 1') !== false, 'atomic SQL increment');
assert_true(strpos($php, 'transition_comment_status') !== false, 'pending approval reconcile hook');
assert_true(strpos($php, 'noelclark_v1_ps_highlights_on_status_transition') !== false, 'reconcile callback named');
assert_true(strpos($php, 'context_matches_essay') !== false, 'server context validation');
assert_true(strpos($php, 'nc_ps_idem_') !== false, 'per-submission idempotency key');
assert_true(strpos($php, 'client_request_id') !== false || strpos($php, 'clientRequestId') !== false, 'clientRequestId contract');
assert_true(strpos($php, "md5(\$page_id . '|' . \$identity . '|' . \$ip)") === false, 'idempotency not keyed by IP');

echo "\n=== List pagination ===\n";
assert_true(strpos($php, 'afterDate') !== false, 'afterDate cursor supported');
assert_true(strpos($php, 'hasMore') !== false, 'hasMore in list response');
assert_true(strpos($php, 'nextCursor') !== false, 'nextCursor in list response');
$page = file_get_contents($root . '/page-ps-returning-to-the-open-journal.php');
assert_true(strpos($page, 'data-ps-notes-more') !== false, 'Load more control in template');
assert_true(strpos($page, 'Load more responses') !== false, 'Load more wording present');

echo "\n=== Accessibility sheet ===\n";
assert_true(strpos($page, 'role="dialog"') !== false, 'sheet role=dialog');
assert_true(strpos($page, 'Close highlight confirmation') !== false, 'close aria-label');
assert_true(strpos($page, 'Remove quotation') !== false, 'remove aria-label');
assert_true(strpos($js, 'Escape') !== false, 'Escape closes sheet');
assert_true(strpos($js, 'focusBeforeSheet') !== false, 'focus restore on close');
assert_true(strpos($js, 'is-ps-hl-open') !== false, 'body class for background lock');

echo "\n=== Selection scope (both endpoints) ===\n";
assert_true(strpos($js, 'selectionInsideScopes') !== false, 'selectionInsideScopes helper');
assert_true(strpos($js, 'nodeInScopes(sel.anchorNode)') !== false, 'anchor checked');
assert_true(strpos($js, 'nodeInScopes(sel.focusNode)') !== false, 'focus checked');
assert_true(strpos($js, 'range.startContainer') !== false, 'range start checked');
assert_true(strpos($js, 'offsetsForRange') !== false, 'context from actual range');

echo "\n=== Comment newlines client ===\n";
assert_true(strpos($js, 'normalizeComment') !== false, 'normalizeComment helper');
assert_true(preg_match('/function normalizeComment[\s\S]*?replace\(/', $js) === 1, 'comment-specific normalize');
/* Ensure comment path uses normalizeComment not normalizePassage for note */
assert_true(
    strpos($js, 'var note = normalizeComment(commentBody && commentBody.value)') !== false,
    'comment submit uses normalizeComment'
);

echo "\n=== Unused four-pen cleanup ===\n";
assert_true(!is_dir($root . '/assets/images/ps-pens'), 'ps-pens directory removed');
assert_true(is_file($root . '/assets/images/ps-pen-button.png'), 'approved purple button retained');

echo "\n=== Approved form wording ===\n";
assert_true(strpos($page, 'Write a comment...') !== false, 'Write a comment...');
assert_true(strpos($page, 'Name or nickname') !== false, 'Name or nickname');
assert_true(strpos($page, 'Post Comment') !== false, 'Post Comment');

echo "\n=== Contextual highlighter explanation (no-selection path) ===\n";
assert_true(strpos($page, 'data-ps-hl-explain') !== false, 'explain markup present');
assert_true(
    strpos(
        $page,
        'Highlight a passage in the article, then tap the highlighter to post it here for others to read. You can also leave a comment below.'
    ) !== false,
    'exact explain copy present'
);
assert_true(
    strpos(
        $page,
        'Select a passage in the article, then tap the purple highlighter to post it here for others to read.'
    ) === false,
    'previous explain copy absent'
);

echo "\n=== Word-boundary safeguard ===\n";
assert_true(strpos($js, 'hasIncompleteWordBoundary') !== false, 'hasIncompleteWordBoundary helper');
assert_true(strpos($js, 'isWordChar') !== false, 'isWordChar helper');
assert_true(strpos($js, 'MID_WORD_HINT') !== false, 'mid-word hint constant');
assert_true(
    strpos($js, 'Oops. Did you mean to stop mid-word? Try again.') !== false,
    'mid-word message present'
);
assert_true(
    strpos($js, 'Your selection starts or ends in the middle of a word.') === false,
    'previous mid-word warning removed'
);
assert_true(strpos($js, 'pending.midWord') !== false, 'submit blocked when midWord');
assert_true(
    strpos($js, 'normalizePassage(range.toString())') !== false,
    'quotation of record from Range.toString()'
);
$hl_css = file_get_contents($root . '/assets/css/ps-highlights.css');
assert_true(
    preg_match('/\.ps-hl-sheet__quote\s*\{[^}]*overflow-wrap:\s*break-word/s', $hl_css) === 1,
    'quote uses break-word'
);
assert_true(
    preg_match('/\.ps-hl-sheet__quote\s*\{[^}]*overflow-wrap:\s*anywhere/s', $hl_css) !== 1,
    'quote rule not anywhere'
);
assert_true(strpos($page, 'ps-writing__hl-aside') === false, 'rejected editorial aside absent');
assert_true(
    strpos($page, '*THE HIGHLIGHTER WORKS!') === false,
    'rejected aside copy absent'
);
assert_true(strpos($js, 'revealPsExplainAndScroll') !== false, 'revealPsExplainAndScroll helper');
assert_true(strpos($js, 'scrollToCommentSection') === false, 'old scrollToCommentSection removed');
assert_true(
    strpos($js, 'commentBody.focus') === false,
    'commentBody is never auto-focused'
);
assert_true(strpos($js, 'hlExplain.hidden = false') !== false, 'explain revealed by clearing hidden');
assert_true(strpos($js, 'ps-writing-notes-heading') !== false, 'scroll targets P.S. heading id');
assert_true(strpos($js, 'localStorage') === false, 'no localStorage for explain');
assert_true(strpos($js, 'sessionStorage') === false, 'no sessionStorage for explain');

echo "\n=== Summary ===\n";
if ($fail > 0) {
    echo "FAILED: {$fail} check(s)\n";
    exit(1);
}
echo "ALL OFFLINE CHECKS PASSED\n";
exit(0);
