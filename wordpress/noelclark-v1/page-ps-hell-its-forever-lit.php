<?php
/**
 * Template Name: P.S. — Hell (It's Forever Lit)
 * Hell (It's Forever Lit) — approved P.S. work-page fidelity.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();
$upload_dir = wp_upload_dir();
$master_relative_path = '/ps/forever-lit-walkers-wraith.mp4';
$master_filesystem_path = trailingslashit($upload_dir['basedir']) . ltrim($master_relative_path, '/');
$hell_video_src = $theme_uri . '/assets/video/forever-lit-walkers-wraith.mp4';

if (empty($upload_dir['error']) && is_readable($master_filesystem_path)) {
    $hell_video_src = $upload_dir['baseurl'] . $master_relative_path;
}

get_header();
?>

<main class="ps-work" id="ps-work-hell-its-forever-lit">
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

    <article class="ps-work__song">
        <div class="ps-work__song-intro">
            <p>
                Every year, we have a Halloween costume party. We've coined these events
                Walker's Wraith.
            </p>
            <p>
                We make our home as immersive a haunt as possible, from the hearse parked
                outside, to a menu that's both delightfully wicked and delicious, to
                thoughtfully placed animatronics, to an original soundtrack.
            </p>
            <p>Here is one of my original songs and the video I created for it:</p>
        </div>

        <h1 class="ps-work__song-title">HELL (IT'S FOREVER LIT)</h1>
        <p class="ps-work__song-aside">Because... well, it is?</p>

        <figure class="ps-work__lyric-video">
            <video
                class="ps-work__lyric-video-player"
                src="<?php echo esc_url($hell_video_src); ?>"
                controls
                playsinline
                preload="metadata"
            ></video>
        </figure>

        <div
            class="ps-work__notation"
            data-ps-tags
            data-ps-work-id="ps-work-hell-its-forever-lit"
        ></div>
    </article>
</main>

<?php
get_footer();
