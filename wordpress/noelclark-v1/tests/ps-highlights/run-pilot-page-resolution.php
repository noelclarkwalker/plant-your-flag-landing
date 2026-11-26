<?php
/**
 * Pilot essay page-resolution regression checks (mocked WP; no live DB/POSTs).
 *
 * Usage: php tests/ps-highlights/run-pilot-page-resolution.php
 *
 * Mirrors noelclark_v1_ps_highlights_pilot_page_id() /
 * noelclark_v1_ps_highlights_is_allowed_page_id() from inc/ps-highlights.php.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$fail = 0;
$php  = file_get_contents($root . '/inc/ps-highlights.php');

function assert_true(bool $cond, string $label): void {
    global $fail;
    if ($cond) {
        echo "PASS  {$label}\n";
    } else {
        echo "FAIL  {$label}\n";
        $fail++;
    }
}

echo "=== Source contracts ===\n";
assert_true(
    strpos($php, "function noelclark_v1_ps_highlights_pilot_page_paths") !== false,
    'pilot_page_paths helper present'
);
assert_true(
    strpos($php, "'ps/returning-to-the-open-journal'") !== false,
    'nested /ps/ path candidate present'
);
assert_true(
    strpos($php, "'returning-to-the-open-journal'") !== false,
    'top-level path candidate present'
);
assert_true(
    (int) strpos($php, "'ps/returning-to-the-open-journal'") <
        (int) strpos($php, "'returning-to-the-open-journal'", strpos($php, 'pilot_page_paths')),
    'nested path tried before top-level inside paths helper'
);
assert_true(
    strpos($php, 'foreach (noelclark_v1_ps_highlights_pilot_page_paths()') !== false,
    'pilot_page_id iterates path candidates'
);
assert_true(
    strpos($php, "get_page_template_slug(\$page) === \$template") !== false
        || strpos($php, 'get_page_template_slug($page) === $template') !== false
        || preg_match(
            '/foreach\s*\(\s*noelclark_v1_ps_highlights_pilot_page_paths[\s\S]{0,400}get_page_template_slug/',
            $php
        ) === 1,
    'each path still requires pilot template'
);
assert_true(
    strpos($php, "get_page_template_slug(\$page) !== noelclark_v1_ps_highlights_pilot_template()") !== false,
    'is_allowed_page_id still requires pilot template'
);
assert_true(
    strpos($php, 'return $pilot_id > 0 && $page_id === $pilot_id;') !== false,
    'is_allowed_page_id still requires exact pilot id match'
);
assert_true(
    strpos($php, "'code' => 'forbidden_page'") !== false,
    'forbidden_page response retained'
);
assert_true(
    strpos($php, 'wp_verify_nonce') !== false,
    'nonce check retained'
);
assert_true(
    strpos($php, 'noelclark_v1_ps_highlights_request_is_same_site') !== false,
    'same-site / origin check retained'
);
assert_true(
    strpos($php, 'context_matches_essay') !== false
        || strpos($php, 'noelclark_v1_ps_highlights_context_matches') !== false
        || strpos($php, 'passage_mismatch') !== false,
    'passage/context validation retained'
);

/* --- Mocked WordPress page store --- */
final class WP_Post {
    /** @var int */
    public $ID;
    /** @var string */
    public $post_type;

    public function __construct(int $id, string $post_type = 'page') {
        $this->ID        = $id;
        $this->post_type = $post_type;
    }
}

/** @var array<string, WP_Post> */
$PAGES_BY_PATH = array();
/** @var array<int, WP_Post> */
$POSTS = array();
/** @var array<int, string> */
$TEMPLATES = array();
$QUERIED_ID = 0;
$IS_PILOT_TEMPLATE_VIEW = false;

const PILOT_TEMPLATE = 'page-ps-returning-to-the-open-journal.php';

function reset_store(): void {
    global $PAGES_BY_PATH, $POSTS, $TEMPLATES, $QUERIED_ID, $IS_PILOT_TEMPLATE_VIEW;
    $PAGES_BY_PATH         = array();
    $POSTS                 = array();
    $TEMPLATES             = array();
    $QUERIED_ID            = 0;
    $IS_PILOT_TEMPLATE_VIEW = false;
}

function seed_page(int $id, string $path, string $template): void {
    global $PAGES_BY_PATH, $POSTS, $TEMPLATES;
    $post                 = new WP_Post($id, 'page');
    $PAGES_BY_PATH[$path] = $post;
    $POSTS[$id]           = $post;
    $TEMPLATES[$id]       = $template;
}

function get_page_by_path(string $path) {
    global $PAGES_BY_PATH;
    return $PAGES_BY_PATH[$path] ?? null;
}

function get_post($id) {
    global $POSTS;
    $id = (int) $id;
    return $POSTS[$id] ?? null;
}

function get_page_template_slug($page): string {
    global $TEMPLATES;
    if (!$page instanceof WP_Post) {
        return '';
    }
    return $TEMPLATES[$page->ID] ?? '';
}

function get_queried_object_id(): int {
    global $QUERIED_ID;
    return (int) $QUERIED_ID;
}

function is_page_template(string $template): bool {
    global $IS_PILOT_TEMPLATE_VIEW;
    return $IS_PILOT_TEMPLATE_VIEW && $template === PILOT_TEMPLATE;
}

function noelclark_v1_ps_highlights_pilot_template(): string {
    return PILOT_TEMPLATE;
}

function noelclark_v1_is_ps_highlights_pilot_page(): bool {
    return is_page_template(PILOT_TEMPLATE);
}

/** Mirrored from inc/ps-highlights.php — keep in sync. */
function noelclark_v1_ps_highlights_pilot_page_paths(): array {
    return array(
        'ps/returning-to-the-open-journal',
        'returning-to-the-open-journal',
    );
}

function noelclark_v1_ps_highlights_pilot_page_id(): int {
    if (noelclark_v1_is_ps_highlights_pilot_page()) {
        return (int) get_queried_object_id();
    }

    $template = noelclark_v1_ps_highlights_pilot_template();

    foreach (noelclark_v1_ps_highlights_pilot_page_paths() as $path) {
        $page = get_page_by_path($path);

        if ($page instanceof WP_Post && get_page_template_slug($page) === $template) {
            return (int) $page->ID;
        }
    }

    return 0;
}

function noelclark_v1_ps_highlights_is_allowed_page_id($page_id): bool {
    $page_id = (int) $page_id;

    if ($page_id <= 0) {
        return false;
    }

    $page = get_post($page_id);

    if (!$page instanceof WP_Post || $page->post_type !== 'page') {
        return false;
    }

    if (get_page_template_slug($page) !== noelclark_v1_ps_highlights_pilot_template()) {
        return false;
    }

    $pilot_id = noelclark_v1_ps_highlights_pilot_page_id();

    return $pilot_id > 0 && $page_id === $pilot_id;
}

echo "\n=== A. Nested /ps/ path with correct template ===\n";
reset_store();
seed_page(42, 'ps/returning-to-the-open-journal', PILOT_TEMPLATE);
assert_true(noelclark_v1_ps_highlights_pilot_page_id() === 42, 'A resolves nested pilot id');
assert_true(noelclark_v1_ps_highlights_is_allowed_page_id(42), 'A nested id allowed');

echo "\n=== B. Top-level path with correct template ===\n";
reset_store();
seed_page(7, 'returning-to-the-open-journal', PILOT_TEMPLATE);
assert_true(noelclark_v1_ps_highlights_pilot_page_id() === 7, 'B resolves top-level pilot id');
assert_true(noelclark_v1_ps_highlights_is_allowed_page_id(7), 'B top-level id allowed');

echo "\n=== C. Wrong template rejected ===\n";
reset_store();
seed_page(42, 'ps/returning-to-the-open-journal', 'page-default.php');
assert_true(noelclark_v1_ps_highlights_pilot_page_id() === 0, 'C wrong template → pilot_id 0');
assert_true(!noelclark_v1_ps_highlights_is_allowed_page_id(42), 'C wrong template rejected');

echo "\n=== D. Unknown page ID rejected ===\n";
reset_store();
seed_page(42, 'ps/returning-to-the-open-journal', PILOT_TEMPLATE);
assert_true(!noelclark_v1_ps_highlights_is_allowed_page_id(9999), 'D unknown id rejected');
assert_true(!noelclark_v1_ps_highlights_is_allowed_page_id(0), 'D zero id rejected');

echo "\n=== E. REST context (no front-end page query) ===\n";
reset_store();
$IS_PILOT_TEMPLATE_VIEW = false;
$QUERIED_ID = 0;
seed_page(42, 'ps/returning-to-the-open-journal', PILOT_TEMPLATE);
assert_true(!noelclark_v1_is_ps_highlights_pilot_page(), 'E not in front-end pilot view');
assert_true(noelclark_v1_ps_highlights_pilot_page_id() === 42, 'E REST resolves via /ps/ path');
assert_true(noelclark_v1_ps_highlights_is_allowed_page_id(42), 'E REST allows nested pilot id');

/* Prefer nested when both exist and match template. */
reset_store();
seed_page(42, 'ps/returning-to-the-open-journal', PILOT_TEMPLATE);
seed_page(7, 'returning-to-the-open-journal', PILOT_TEMPLATE);
assert_true(noelclark_v1_ps_highlights_pilot_page_id() === 42, 'E prefers nested path first');
assert_true(noelclark_v1_ps_highlights_is_allowed_page_id(42), 'E nested preferred id allowed');
assert_true(!noelclark_v1_ps_highlights_is_allowed_page_id(7), 'E non-preferred twin rejected');

echo "\n=== G. Counter / retry / idempotency contracts unchanged ===\n";
assert_true(strpos($php, 'clientRequestId') !== false || strpos($php, 'client_request_id') !== false, 'G clientRequestId retained');
assert_true(strpos($php, 'nc_ps_idem_') !== false, 'G idempotency prefix retained');
assert_true(strpos($php, 'GET_LOCK') !== false && strpos($php, 'RELEASE_LOCK') !== false, 'G advisory locks retained');
assert_true(strpos($php, 'CAST(meta_value AS UNSIGNED) + 1') !== false, 'G atomic counter increment retained');
assert_true(
    strpos($php, "md5(\$page_id . '|' . \$identity . '|' . \$ip)") === false,
    'G idempotency still not keyed by IP'
);

echo "\n=== Summary ===\n";
if ($fail > 0) {
    echo "FAILED: {$fail} check(s)\n";
    exit(1);
}
echo "ALL PILOT PAGE RESOLUTION CHECKS PASSED\n";
exit(0);
