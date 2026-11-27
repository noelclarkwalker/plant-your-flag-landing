<?php
/**
 * Template Name: P.S. — Writing Essay
 * Editor-hosted Writing essay — P.S. Writing chrome with WordPress content.
 *
 * Phase 1 Interactive Pull Quotes foundation.
 * Does not replace existing PHP-authored Writing essays.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$essay_id = 'ps-writing-essay-' . (int) get_the_ID();
?>

<main class="ps-work ps-writing ps-writing--editor" id="<?php echo esc_attr($essay_id); ?>">
    <div class="ps-writing__shell">
        <header class="ps-writing__top">
            <span class="ps-writing__brand">Personal Seal, P.S.</span>
        </header>

        <div class="ps-writing__grid">
            <div class="ps-writing__meta">
                Writing<br>
                Noèl Clark
            </div>

            <h1 class="ps-writing__title"><?php the_title(); ?></h1>

            <div class="ps-writing__essay-band ps-writing__essay-band--solo">
                <article class="ps-writing__article">
                    <div class="ps-work__body ps-writing__body ps-writing__body--editor">
                        <?php
                        while (have_posts()) {
                            the_post();
                            the_content();
                        }
                        ?>
                    </div>
                </article>
            </div>

            <nav class="ps-writing__nav" aria-label="Writing">
                <a class="ps-writing__nav-link" href="<?php echo esc_url(home_url('/ps/')); ?>">BACK TO WRITING</a>
                <span class="ps-writing__nav-slot">PREVIOUS</span>
                <span class="ps-writing__nav-slot">NEXT</span>
            </nav>
        </div>
    </div>
</main>

<?php
get_footer();
