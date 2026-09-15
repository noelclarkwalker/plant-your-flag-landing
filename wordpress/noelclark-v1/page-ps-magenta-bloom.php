<?php
/**
 * Template Name: P.S. — Magenta Bloom
 * Magenta Bloom — approved P.S. work-page fidelity.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();

get_header();
?>

<main class="ps-work" id="ps-work-magenta-bloom">
    <nav class="ps-work__return" aria-label="Back to P.S.">
        <a class="ps-work__return-link" href="<?php echo esc_url(home_url('/ps/')); ?>">← BACK TO P.S.</a>
    </nav>

    <div class="ps-work__seal" aria-hidden="true">
        <img
            class="ps-work__seal-mark"
            src="<?php echo esc_url($theme_uri . '/assets/images/black-wax-seal.png'); ?>"
            alt=""
            width="1024"
            height="1024"
        />
    </div>

    <figure class="ps-work__photo">
        <h1 class="visually-hidden">MAGENTA BLOOM</h1>
        <img
            class="ps-work__photo-image"
            src="<?php echo esc_url($theme_uri . '/assets/images/flower-compound.JPG'); ?>"
            alt=""
            width="5184"
            height="3456"
            fetchpriority="high"
        />
    </figure>

    <div
        class="ps-work__notation ps-work__notation--photo"
        data-ps-tags
        data-ps-work-id="ps-work-magenta-bloom"
    ></div>
</main>

<?php
get_footer();
