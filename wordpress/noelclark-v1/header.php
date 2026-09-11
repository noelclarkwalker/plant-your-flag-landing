<?php
/**
 * Interior page header chrome — derived from approved static site-header.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?> class="site-page">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php wp_head(); ?>
</head>
<body <?php body_class('site-page'); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <a class="site-header__brand" href="<?php echo esc_url(home_url('/')); ?>">NOELCLARK.COM</a>

    <details class="site-header__menu">
        <summary class="site-header__menu-toggle">Menu</summary>
        <div class="site-header__panel">
            <nav class="site-header__nav" aria-label="NoelClark.com">
                <?php
                noelclark_v1_render_nav_item('mail-room', 'The Mail Room', '/mail-room/');
                noelclark_v1_render_nav_item('ps', 'P.S.', '/ps/');
                noelclark_v1_render_nav_item('about', 'About', '/about/');
                noelclark_v1_render_nav_item('john-clark', 'John Clark', '/john-clark/');
                noelclark_v1_render_nav_item('contact', 'Contact', '/contact/');
                noelclark_v1_render_nav_item('shop', 'Shop', '/return-to-nature/');
                ?>
            </nav>
            <button type="button" class="site-header__membership" disabled aria-disabled="true">
                Membership
            </button>
        </div>
    </details>

    <div class="site-header__desktop">
        <nav class="site-header__nav" aria-label="NoelClark.com">
            <?php
            noelclark_v1_render_nav_item('mail-room', 'The Mail Room', '/mail-room/');
            noelclark_v1_render_nav_item('ps', 'P.S.', '/ps/');
            noelclark_v1_render_nav_item('about', 'About', '/about/');
            noelclark_v1_render_nav_item('john-clark', 'John Clark', '/john-clark/');
            noelclark_v1_render_nav_item('contact', 'Contact', '/contact/');
            noelclark_v1_render_nav_item('shop', 'Shop', '/return-to-nature/');
            ?>
        </nav>
        <button type="button" class="site-header__membership" disabled aria-disabled="true">
            Membership
        </button>
    </div>
</header>
