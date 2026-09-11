<?php
/**
 * Interior page footer chrome — derived from approved static site-footer.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<footer class="site-footer">
    <div class="site-footer__main">
        <p class="site-footer__brand">NOELCLARK.COM</p>
        <p class="site-footer__tagline">Follow the curiosity.</p>
    </div>
    <nav class="site-footer__utility" aria-label="Footer">
        <span class="site-footer__utility-item">Membership</span>
        <a href="<?php echo esc_url(home_url('/return-to-nature/')); ?>" class="site-footer__utility-item">Shop</a>
        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="site-footer__utility-item">Contact</a>
        <span class="site-footer__utility-item">Privacy</span>
        <span class="site-footer__utility-item">Terms</span>
    </nav>
    <p class="site-footer__copyright">&copy; 2026 No&egrave;l Clark</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
