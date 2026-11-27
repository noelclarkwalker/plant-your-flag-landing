<?php
/**
 * Phase 1 Interactive Pull Quotes — offline structural checks.
 * No WordPress bootstrap. No network. No mutations outside assertions.
 */

$root = dirname(__DIR__, 2);
$fail = 0;

function assert_true($cond, $message) {
    global $fail;
    if ($cond) {
        echo "PASS  {$message}\n";
        return;
    }
    $fail += 1;
    echo "FAIL  {$message}\n";
}

function assert_contains($haystack, $needle, $message) {
    assert_true(is_string($haystack) && strpos($haystack, $needle) !== false, $message);
}

function assert_not_contains($haystack, $needle, $message) {
    assert_true(is_string($haystack) && strpos($haystack, $needle) === false, $message);
}

$files = array(
    'page-ps-writing-essay.php',
    'inc/ps-pull-quotes.php',
    'assets/css/ps-writing-essay.css',
    'blocks/interactive-pull-quote/block.json',
    'blocks/interactive-pull-quote/editor.js',
    'blocks/interactive-pull-quote/logic.js',
    'blocks/interactive-pull-quote/style.css',
    'blocks/interactive-pull-quote/editor.css',
);

foreach ($files as $rel) {
    assert_true(is_readable($root . '/' . $rel), "readable {$rel}");
}

$functions = file_get_contents($root . '/functions.php');
assert_contains($functions, "inc/ps-pull-quotes.php", 'functions.php loads ps-pull-quotes.php');
assert_contains($functions, 'page-ps-writing-essay.php', 'body class recognizes Writing Essay template');

$template = file_get_contents($root . '/page-ps-writing-essay.php');
assert_contains($template, 'Template Name: P.S. — Writing Essay', 'template name present');
assert_contains($template, 'the_content()', 'template renders editor content');
assert_contains($template, 'Personal Seal, P.S.', 'Writing brand chrome present');
assert_not_contains($template, 'data-ps-pen-launch', 'no highlighter pen rail on new essay template');
assert_not_contains($template, 'data-ps-comment-form', 'no comment form on new essay template');

$inc = file_get_contents($root . '/inc/ps-pull-quotes.php');
assert_contains($inc, 'noelclark/interactive-pull-quote', 'block name registered');
assert_contains($inc, 'register_block_type', 'register_block_type used');
assert_contains($inc, 'post-thumbnails', 'featured images enabled for archive thumbnails');
assert_contains($inc, 'editor-styles', 'editor styles enabled');
assert_not_contains($inc, 'ps-highlights', 'pull-quotes include does not load highlighter');

$block = json_decode(file_get_contents($root . '/blocks/interactive-pull-quote/block.json'), true);
assert_true(is_array($block), 'block.json parses');
assert_true(($block['name'] ?? '') === 'noelclark/interactive-pull-quote', 'block.json name');
assert_true(array_key_exists('marks', $block['attributes'] ?? array()), 'marks attribute reserved');
assert_true(array_key_exists('isInteractive', $block['attributes'] ?? array()), 'isInteractive attribute reserved');
assert_true(array_key_exists('neighborHash', $block['attributes'] ?? array()), 'neighborHash attribute reserved');

$editor = file_get_contents($root . '/blocks/interactive-pull-quote/editor.js');
assert_contains($editor, 'Add as pull quote', 'owner label: Add as pull quote');
assert_contains($editor, 'Your Pull Quotes', 'owner label: Your Pull Quotes');
assert_contains($editor, 'Put these words back in the paragraph', 'owner label: restore');
assert_contains($editor, 'Preview essay', 'Phase 1 preview control (not misleading interactions UI)');
assert_not_contains($editor, 'Make Your Pull Quotes Interactive', 'no misleading Phase 2 functional control');
assert_contains($editor, 'replaceBlocks', 'conversion uses replaceBlocks (Undoable)');
assert_contains($editor, 'assessSelection', 'risky selection gate');
assert_contains($editor, 'planRestore', 'restore planner');

$essay_css = file_get_contents($root . '/assets/css/ps-writing-essay.css');
assert_not_contains(
    $essay_css,
    'wp-block-paragraph:first-child',
    'no automatic first-paragraph lede treatment'
);
assert_not_contains(
    $essay_css,
    'p:first-child {',
    'no p:first-child typography override'
);
assert_true(
    strpos($essay_css, '1.45rem') === false,
    'essay CSS does not introduce unauthorized 1.45rem lede size'
);

$style = file_get_contents($root . '/blocks/interactive-pull-quote/style.css');
assert_contains($style, 'THE PEN IS THE EFFECT', 'design direction noted in CSS');
assert_contains($style, 'font: inherit', 'quote text inherits typography');
assert_contains($style, 'ps-pull-quote__pen-label', 'pen label style present');
assert_contains($style, '#a21eff', 'brand purple hover token present');
assert_contains($style, 'hover: hover', 'genuine hover media query');
assert_not_contains($style, 'border-left: 3px', 'no decorative quote border');
assert_not_contains($style, '1.28rem', 'no enlarged quote font size');
assert_contains($style, '1.12rem', 'pull-quote text matches body size');

$render_php = file_get_contents($root . '/inc/ps-pull-quotes.php');
assert_contains($render_php, 'margin_bracket', 'Margin Bracket reserved in mark schema docs');
assert_contains($render_php, 'expandInsertOffset', 'Expand insertion point reserved');
assert_contains($render_php, 'Mark this passage', 'inert pen label in render');
assert_not_contains($render_php, '<blockquote', 'no blockquote wrapper (not a traditional quote)');

$logic_js = file_get_contents($root . '/blocks/interactive-pull-quote/logic.js');
assert_contains($logic_js, 'margin_bracket', 'logic includes margin_bracket');
assert_contains($logic_js, 'expandInsertOffset', 'logic includes expandInsertOffset');

$open = file_get_contents($root . '/page-ps-returning-to-the-open-journal.php');
$thread = file_get_contents($root . '/page-ps-the-invisible-thread.php');
assert_contains($open, 'Returning to the Open Journal', 'existing Open Journal essay untouched content marker');
assert_contains($thread, 'data-thread-ego', 'Invisible Thread EGO moment untouched');

$journey = file_get_contents($root . '/inc/ps-journey.php');
assert_contains($journey, 'noelclark_v1_ps_discover_meta_works', 'meta discovery path still present for publish→archive');
assert_contains($journey, '_ps_medium', 'archive medium meta still present');

$highlights = file_get_contents($root . '/inc/ps-highlights.php');
assert_true(strlen($highlights) > 100, 'highlighter PHP still present (untouched track)');

echo "\n";
if ($fail > 0) {
    echo "RESULT: {$fail} failure(s)\n";
    exit(1);
}

echo "RESULT: all Phase 1 offline checks passed\n";
exit(0);
