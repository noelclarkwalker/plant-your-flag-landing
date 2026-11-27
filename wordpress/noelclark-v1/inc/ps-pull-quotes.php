<?php
/**
 * Interactive Pull Quotes — Phase 1 foundation.
 *
 * Editor-hosted Writing essays + Interactive Pull Quote block registration.
 * Does not implement animated effects (Phase 2+).
 * Does not touch visitor highlighter / Quote in P.S. / Discussion.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Whether the current (or given) page uses the editor-hosted Writing Essay template.
 *
 * @param int|null $post_id Optional post ID.
 * @return bool
 */
function noelclark_v1_is_ps_writing_essay_page($post_id = null) {
    if ($post_id) {
        return get_page_template_slug((int) $post_id) === 'page-ps-writing-essay.php';
    }

    return is_page_template('page-ps-writing-essay.php');
}

/**
 * Theme supports needed for owner-composed Writing essays.
 *
 * @return void
 */
function noelclark_v1_ps_pull_quotes_theme_supports() {
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/ps-writing-essay.css');
    add_editor_style('blocks/interactive-pull-quote/editor.css');
}
add_action('after_setup_theme', 'noelclark_v1_ps_pull_quotes_theme_supports', 20);

/**
 * Body class for editor-hosted Writing essays.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function noelclark_v1_ps_pull_quotes_body_class($classes) {
    if (noelclark_v1_is_ps_writing_essay_page()) {
        $classes[] = 'ps-writing-essay-page';
    }

    return $classes;
}
add_filter('body_class', 'noelclark_v1_ps_pull_quotes_body_class');

/**
 * Front-end assets for editor-hosted Writing essays.
 *
 * @return void
 */
function noelclark_v1_enqueue_ps_writing_essay_assets() {
    if (!noelclark_v1_is_ps_writing_essay_page()) {
        return;
    }

    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'noelclark-v1-ps-writing-fonts',
        'https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,400;0,500;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'noelclark-v1-ps-writing',
        $theme_uri . '/assets/css/ps-writing.css',
        array('noelclark-v1-ps', 'noelclark-v1-ps-writing-fonts'),
        noelclark_v1_asset_version('assets/css/ps-writing.css')
    );

    wp_enqueue_style(
        'noelclark-v1-ps-writing-essay',
        $theme_uri . '/assets/css/ps-writing-essay.css',
        array('noelclark-v1-ps-writing'),
        noelclark_v1_asset_version('assets/css/ps-writing-essay.css')
    );

    wp_enqueue_style(
        'noelclark-v1-interactive-pull-quote',
        $theme_uri . '/blocks/interactive-pull-quote/style.css',
        array('noelclark-v1-ps-writing-essay'),
        noelclark_v1_asset_version('blocks/interactive-pull-quote/style.css')
    );
}
add_action('wp_enqueue_scripts', 'noelclark_v1_enqueue_ps_writing_essay_assets');

/**
 * Register the Interactive Pull Quote block and editor script.
 *
 * Phase 1 stores marks[] + effect-ready attributes in block.json;
 * animations are not executed here.
 *
 * Future mark object shape (Phase 2+; stored, not animated yet):
 * - id (string)
 * - effect: highlight | underline | circle | margin_bracket |
 *           strike_revise | expand | none
 * - text (string) exact words within this quote
 * - replacement (string) Strike & Revise only
 * - expandContent (string) Expand: author-written words to reveal
 * - expandInsertOffset (int) Expand: insertion point in quote plain text
 * - start / end (int) offsets within quote plain text for word-level marks
 *
 * Approved finished-product effects (six):
 * Highlight, Underline, Circle, Margin Bracket, Strike & Revise, Expand.
 * Multiple marks per quotation remain in finished-product scope.
 *
 * @return void
 */
function noelclark_v1_register_interactive_pull_quote_block() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    $editor_js = '/blocks/interactive-pull-quote/editor.js';
    $editor_css = '/blocks/interactive-pull-quote/editor.css';
    $style_css = '/blocks/interactive-pull-quote/style.css';

    $logic_js = '/blocks/interactive-pull-quote/logic.js';

    wp_register_script(
        'noelclark-v1-interactive-pull-quote-logic',
        $theme_uri . $logic_js,
        array(),
        noelclark_v1_asset_version(ltrim($logic_js, '/')),
        true
    );

    wp_register_script(
        'noelclark-v1-interactive-pull-quote-editor',
        $theme_uri . $editor_js,
        array(
            'noelclark-v1-interactive-pull-quote-logic',
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-compose',
            'wp-data',
            'wp-edit-post',
            'wp-editor',
            'wp-hooks',
            'wp-i18n',
            'wp-notices',
            'wp-plugins',
            'wp-rich-text',
            'wp-url',
        ),
        noelclark_v1_asset_version(ltrim($editor_js, '/')),
        true
    );

    wp_localize_script(
        'noelclark-v1-interactive-pull-quote-editor',
        'noelclarkV1PullQuotes',
        array(
            'blockName'       => 'noelclark/interactive-pull-quote',
            'writingTemplate' => 'page-ps-writing-essay.php',
            'previewLabel'    => 'Preview essay',
            'phase'           => 1,
        )
    );

    wp_register_style(
        'noelclark-v1-interactive-pull-quote-editor',
        $theme_uri . $editor_css,
        array('wp-edit-blocks'),
        noelclark_v1_asset_version(ltrim($editor_css, '/'))
    );

    wp_register_style(
        'noelclark-v1-interactive-pull-quote',
        $theme_uri . $style_css,
        array(),
        noelclark_v1_asset_version(ltrim($style_css, '/'))
    );

    register_block_type(
        $theme_dir . '/blocks/interactive-pull-quote',
        array(
            'editor_script'   => 'noelclark-v1-interactive-pull-quote-editor',
            'editor_style'    => 'noelclark-v1-interactive-pull-quote-editor',
            'style'           => 'noelclark-v1-interactive-pull-quote',
            'render_callback' => 'noelclark_v1_render_interactive_pull_quote',
        )
    );
}
add_action('init', 'noelclark_v1_register_interactive_pull_quote_block');

/**
 * Front-end render for Interactive Pull Quote (Phase 1: readable quote only).
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Saved inner content (unused; dynamic).
 * @return string
 */
function noelclark_v1_render_interactive_pull_quote($attributes, $content = '') {
    unset($content);

    $quote_html = isset($attributes['content']) ? (string) $attributes['content'] : '';
    $quote_id   = isset($attributes['quoteId']) ? sanitize_html_class((string) $attributes['quoteId']) : '';
    $marks      = isset($attributes['marks']) && is_array($attributes['marks']) ? $attributes['marks'] : array();
    $interactive = !empty($attributes['isInteractive']);

    $allowed = array(
        'em'     => array(),
        'i'      => array(),
        'strong' => array(),
        'b'      => array(),
        'a'      => array(
            'href'   => true,
            'title'  => true,
            'rel'    => true,
            'target' => true,
        ),
        'br'     => array(),
        'span'   => array(
            'class' => true,
            'data-mark-id' => true,
        ),
    );

    $safe = wp_kses($quote_html, $allowed);

    if ($safe === '') {
        return '';
    }

    $classes = 'ps-pull-quote';
    if ($interactive) {
        $classes .= ' ps-pull-quote--interactive';
    }

    /*
     * Inert pen placeholder for private technical testing only.
     * Not final artwork. Effects are not wired in Phase 1.
     */
    $show_pen = true;

    ob_start();
    ?>
<div
    class="<?php echo esc_attr($classes); ?>"
    <?php echo $quote_id !== '' ? 'id="' . esc_attr('ps-pq-' . $quote_id) . '"' : ''; ?>
    data-ps-pull-quote
    data-ps-quote-id="<?php echo esc_attr($quote_id); ?>"
    data-ps-marks-count="<?php echo esc_attr((string) count($marks)); ?>"
>
    <div class="ps-pull-quote__text"><?php echo $safe; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses above ?></div>
    <?php if ($show_pen) : ?>
    <button
        type="button"
        class="ps-pull-quote__pen"
        disabled
        aria-label="Mark this passage"
        title="Mark this passage"
    >
        <svg class="ps-pull-quote__pen-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
            <path
                fill="currentColor"
                d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l9.06-9.06.92.92L5.92 19.58zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
            />
        </svg>
        <span class="ps-pull-quote__pen-label">Mark this passage</span>
    </button>
    <?php endif; ?>
</div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Restrict the pull-quote editor plugin UI to Writing Essay pages in the editor.
 *
 * @return void
 */
function noelclark_v1_pull_quotes_editor_context() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
        return;
    }

    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($post_id <= 0) {
        return;
    }

    if (!noelclark_v1_is_ps_writing_essay_page($post_id)) {
        return;
    }

    wp_enqueue_script('noelclark-v1-interactive-pull-quote-editor');
    wp_enqueue_style('noelclark-v1-interactive-pull-quote-editor');
}
add_action('enqueue_block_editor_assets', 'noelclark_v1_pull_quotes_editor_context');
