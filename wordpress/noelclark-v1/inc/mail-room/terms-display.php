<?php
/**
 * Mail Room Submission Terms — visitor display.
 *
 * Read-only. Displays the exact stored snapshot for the server-enforced
 * version inside the Write to the Mail Room dialog. Never selects “latest.”
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load the stored Terms body for the server-enforced version.
 *
 * Zero or many rows, or an empty body, is a failure.
 *
 * @return string|null Exact body_snapshot, or null when unavailable.
 */
function noelclark_v1_mail_room_load_submission_terms_body() {
    global $wpdb;

    if (
        !defined('NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE')
        || !defined('NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION')
        || !function_exists('noelclark_v1_mail_room_table')
    ) {
        return null;
    }

    $table = noelclark_v1_mail_room_table('legal_documents');

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT version_label, body_snapshot FROM {$table} WHERE doc_type = %s AND version_label = %s",
            NOELCLARK_V1_MAIL_ROOM_LEGAL_DOC_TYPE,
            NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION
        )
    );

    if (!is_array($rows) || count($rows) !== 1) {
        return null;
    }

    if ((string) $rows[0]->version_label !== NOELCLARK_V1_MAIL_ROOM_SUBMIT_LEGAL_VERSION) {
        return null;
    }

    $body = (string) $rows[0]->body_snapshot;

    if (trim($body) === '') {
        return null;
    }

    return $body;
}

/**
 * Convert the Terms snapshot to HTML, structure only.
 *
 * Supports "#", "##", "###" headings, blank-line-separated paragraphs, and
 * "* " / "- " list items. All text is escaped; no inline markup is
 * interpreted, so any other characters display exactly as stored.
 *
 * @param string $body Stored body_snapshot.
 * @return string Escaped HTML.
 */
function noelclark_v1_mail_room_terms_to_html($body) {
    $lines     = preg_split('/\r\n|\r|\n/', (string) $body);
    $html      = '';
    $paragraph = array();
    $list      = array();

    $flush = function () use (&$html, &$paragraph, &$list) {
        if ($paragraph) {
            $html     .= '<p>' . esc_html(implode("\n", $paragraph)) . "</p>\n";
            $paragraph = array();
        }

        if ($list) {
            $html .= "<ul>\n";

            foreach ($list as $item) {
                $html .= '<li>' . esc_html($item) . "</li>\n";
            }

            $html .= "</ul>\n";
            $list  = array();
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            $flush();
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.+)$/u', $trimmed, $match)) {
            $flush();
            $level = strlen($match[1]) + 1;
            $html .= sprintf("<h%d>%s</h%d>\n", $level, esc_html(trim($match[2])), $level);
            continue;
        }

        if (preg_match('/^[*\-]\s+(.+)$/u', $trimmed, $match)) {
            if ($paragraph) {
                $flush();
            }

            $list[] = trim($match[1]);
            continue;
        }

        if ($list) {
            $flush();
        }

        $paragraph[] = $trimmed;
    }

    $flush();

    return $html;
}

/**
 * Render the in-dialog Terms panel.
 *
 * When the Terms cannot be loaded the panel is rendered with
 * data-terms-status="unavailable" and no content; the submit script then
 * shows an error and blocks sending.
 */
function noelclark_v1_mail_room_render_terms_panel() {
    $body    = noelclark_v1_mail_room_load_submission_terms_body();
    $content = $body !== null ? noelclark_v1_mail_room_terms_to_html($body) : '';
    $status  = $content !== '' ? 'ok' : 'unavailable';
    ?>
    <section class="mail-submit__terms" id="mail-submit-terms" data-terms-status="<?php echo esc_attr($status); ?>" aria-label="Mail Room Submission Terms" hidden>
        <div class="mail-submit__terms-nav">
            <button type="button" class="mail-submit__terms-back">← BACK TO REVIEW &amp; SEND</button>
        </div>
        <div class="mail-submit__terms-content" id="mail-submit-terms-content" tabindex="-1">
            <?php
            // Escaped per element in noelclark_v1_mail_room_terms_to_html().
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
        <div class="mail-submit__terms-nav mail-submit__terms-nav--end">
            <button type="button" class="mail-submit__terms-back">← BACK TO REVIEW &amp; SEND</button>
        </div>
    </section>
    <?php
}
