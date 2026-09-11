<?php
/**
 * Minimal WordPress fallback template.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<p><?php esc_html_e('No content matched this request.', 'noelclark-v1'); ?></p>
<?php wp_footer(); ?>
</body>
</html>
