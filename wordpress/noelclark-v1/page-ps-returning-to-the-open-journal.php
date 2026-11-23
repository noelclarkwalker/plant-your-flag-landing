<?php
/**
 * Template Name: P.S. — Returning to the Open Journal
 * Returning to the Open Journal — P.S. Writing Phase A chrome.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();

get_header();
?>

<main class="ps-work ps-writing" id="ps-work-returning-open-journal">
    <div class="ps-writing__shell">
        <header class="ps-writing__top">
            <span class="ps-writing__brand">Personal Seal, P.S.</span>
            <span class="ps-writing__hl-hint">Use the highlighter to leave a P.S.</span>
        </header>

        <div class="ps-writing__grid">
            <div class="ps-writing__meta">
                Writing<br>
                Noèl Clark
            </div>

            <h1 class="ps-writing__title">Returning to the Open Journal</h1>

            <?php
            $ps_hl_monogram = $theme_uri . '/assets/images/black-monogram-nc.png';
            ?>
            <button
                type="button"
                class="ps-writing__hl ps-writing__hl--control"
                id="ps-hl-toggle"
                aria-pressed="false"
                aria-label="Highlighter off. Turn on to highlight a passage and leave a P.S."
                title="Highlighter"
                data-ps-hl-toggle
                data-ps-hl-edition="nc"
            >
                <svg class="ps-writing__hl-svg" viewBox="0 0 120 36" role="img" aria-hidden="true" focusable="false">
                    <title>Highlighter</title>
                    <!-- Cap -->
                    <path fill="#2a2040" d="M8.2 11.2h14.6c1.1 0 1.7.6 1.7 1.5v10.6c0 .9-.6 1.5-1.7 1.5H8.2c-2.4 0-4.2-1.5-4.2-3.4v-6.8c0-1.9 1.8-3.4 4.2-3.4z"/>
                    <path fill="#1b1028" d="M8.2 12.4h13.2v9.2H8.2c-1.5 0-2.6-.9-2.6-2.1v-5c0-1.2 1.1-2.1 2.6-2.1z" opacity=".35"/>
                    <!-- Barrel -->
                    <rect x="22.5" y="10.2" width="72" height="15.6" rx="2.2" fill="#c9a9f5"/>
                    <rect x="22.5" y="10.2" width="72" height="5.2" rx="2.2" fill="#e8defa" opacity=".9"/>
                    <rect x="22.5" y="21.2" width="72" height="4.6" fill="#7c4dd6" opacity=".22"/>
                    <!-- Brand editions: one shown per page load (never changes mid-visit) -->
                    <g class="ps-writing__hl-brand" data-ps-hl-brand="nc">
                        <image
                            href="<?php echo esc_url($ps_hl_monogram); ?>"
                            xlink:href="<?php echo esc_url($ps_hl_monogram); ?>"
                            x="46.5"
                            y="11"
                            width="24"
                            height="20"
                            preserveAspectRatio="xMidYMid meet"
                        />
                    </g>
                    <g class="ps-writing__hl-brand" data-ps-hl-brand="masterpiece" hidden>
                        <text
                            class="ps-writing__hl-mark ps-writing__hl-mark--phrase"
                            x="58.5"
                            y="16.4"
                            text-anchor="middle"
                        >
                            <tspan x="58.5" dy="0">Life Is a</tspan>
                            <tspan x="58.5" dy="5.2">Masterpiece</tspan>
                        </text>
                    </g>
                    <g class="ps-writing__hl-brand" data-ps-hl-brand="niente" hidden>
                        <text
                            class="ps-writing__hl-mark ps-writing__hl-mark--phrase ps-writing__hl-mark--long"
                            x="58.5"
                            y="15.6"
                            text-anchor="middle"
                        >
                            <tspan x="58.5" dy="0">Niente Nella Vita</tspan>
                            <tspan x="58.5" dy="4.6">È Perfetto</tspan>
                        </text>
                    </g>
                    <g class="ps-writing__hl-brand" data-ps-hl-brand="curiosity" hidden>
                        <text
                            class="ps-writing__hl-mark ps-writing__hl-mark--phrase"
                            x="58.5"
                            y="16.4"
                            text-anchor="middle"
                        >
                            <tspan x="58.5" dy="0">Follow the</tspan>
                            <tspan x="58.5" dy="5.2">Curiosity</tspan>
                        </text>
                    </g>
                    <!-- Ferrule -->
                    <rect x="94" y="11.4" width="7.2" height="13.2" fill="#5a4632"/>
                    <rect x="94" y="11.4" width="7.2" height="3.2" fill="#8a7055" opacity=".85"/>
                    <!-- Nib -->
                    <path fill="#f0e6a8" d="M101.2 12.2h8.4c2.4 0 3.8 1.3 4.4 2.8l3.2 7.4c.2.5 0 1-.6 1H101.2V12.2z"/>
                    <path fill="#d7c56a" d="M109.4 12.4c1.7.2 2.8 1.2 3.3 2.5l3 7.1H109.4V12.4z" opacity=".75"/>
                    <path fill="#2a2040" d="M116.2 22.2l1.8 1.4h-8.4l1.1-1.4h5.5z" opacity=".55"/>
                </svg>
                <span class="ps-writing__hl-label">
                    Highlighter <span class="ps-writing__hl-state" data-ps-hl-state>Off</span>
                </span>
            </button>

            <article class="ps-writing__article">
                <div class="ps-work__body ps-writing__body" data-ps-highlights-root>
                    <p class="ps-writing__lede">I stopped blogging a few years ago.</p>

                    <p>
                        Not because I stopped writing. I do not think I have ever truly stopped
                        writing. But the climate of where we share changed. Long-form blogging
                        started to feel like a room people had stopped visiting, so I began posting
                        smaller, snack-sized reflections as Instagram captions. Little pieces of my
                        heart. Little windows into what I was learning. Little scraps from a much
                        larger journal.
                    </p>

                    <p>But before that, my blog had been my open journal since 2010.</p>

                    <p>Then 2020 happened.</p>

                    <figure class="ps-writing__figure ps-writing__figure--lavender">
                        <img
                            src="<?php echo esc_url($theme_uri . '/assets/images/lavender-on-keyboard.PNG'); ?>"
                            alt=""
                            width="2000"
                            height="1331"
                            loading="lazy"
                        />
                    </figure>

                    <p>
                        My blog was hacked in the same year I was battling my way back from a severe
                        ulcerative colitis flare. My body was weak. My site was broken. My life felt
                        like it was asking me to look carefully at every place that needed rebuilding.
                        It felt as though God was saying, "Pay attention to the weak spots. This is
                        where the restoration begins."
                    </p>

                    <p>So I started rebuilding.</p>

                    <p>
                        I researched, prayed, wrote, learned, questioned, cried, rested, listened, and
                        gathered what was helping me. I worked tirelessly to heal and to understand
                        what my body, my spirit, and my life were trying to teach me. Eventually,
                        those lessons became my testimony, and I poured them into <em>Return to Nature</em>.
                    </p>

                    <p>I finished that book in 2022.</p>

                    <p>
                        But the deeper work did not end when the book did. In many ways, the book
                        helped me see myself more clearly. I learned so much about my body, my
                        intuition, my nervous system, my anxiety, my creativity, my faith, and the way
                        God had been speaking to me through more than one language.
                    </p>

                    <p>Art became one of those languages.</p>

                    <p>
                        The more I created, the more I learned how to listen. My intuition became
                        clearer. I began noticing the small shifts before the larger changes arrived.
                        I learned what to look for. I learned how my body felt when something was
                        aligned. I learned the difference between anxiety screaming for control and
                        intuition speaking with quiet certainty.
                    </p>

                    <p>That changed everything.</p>

                    <p>
                        When I stopped leading with a mind weighed down by anxiety and fear, I was
                        able to live with a much clearer lens. I was able to love more freely, create
                        more honestly, and move through my world with more trust. In the past, I
                        would have been guarded and apprehensive, questioning every good thing until
                        fear had a chance to ruin the blessing before I could receive it. But healing
                        gave me a different way to live. It taught me how to recognize the difference
                        between fear pulling me backward and God leading me forward.
                    </p>

                    <p>
                        Sometimes we run toward what does not challenge us because it is the road we
                        already know.
                    </p>

                    <p>Even when it is depleting, shallow, unfulfilling.</p>

                    <p>
                        But when we are led by intuition over fear, we begin choosing what speaks to
                        our spirit, even when logic has a list of objections. We take chances we once
                        would have talked ourselves out of. We stop catering to wounds we did not
                        create but somehow became loyal to. We begin asking better questions.
                    </p>

                    <p>What am I obeying?</p>

                    <p>Fear or faith?</p>

                    <p>Anxiety or intuition?</p>

                    <p>An old wound or God's direction?</p>

                    <p>
                        I explained this to someone recently: we can repeatedly make decisions based
                        on a wound we may not have caused, but unless we do the active work of
                        self-reflection, we will keep making choices that stunt our growth and call it
                        personality, preference, logic, or protection.
                    </p>

                    <p>
                        I had always considered myself an introspective person, but sickness took me
                        deeper.
                    </p>

                    <p>
                        Severe illness stripped away the luxury of pretending I had already looked
                        closely enough. It made me sit with myself in a way I could not avoid. It made
                        me examine what I feared, what I carried, what I believed, what I avoided, and
                        how often I had chosen anxiety over intuition.
                    </p>

                    <p>Eventually, I had to say, "Enough."</p>

                    <p>
                        Enough missing what God was doing because I was too busy bracing for what
                        could go wrong.
                    </p>

                    <p>
                        Now, so often, I look around at my life and feel overwhelmed by gratitude. The
                        laughter filling our home. The adventures. The quiet moments of togetherness.
                        The family gatherings. The ordinary magic. The answered prayers that do not
                        always arrive loudly, but sit beside you in the room until you finally
                        recognize them.
                    </p>

                    <p>There is so much beauty here.</p>

                    <figure class="ps-writing__figure">
                        <img
                            src="<?php echo esc_url($theme_uri . '/assets/images/daytime-office.JPG'); ?>"
                            alt=""
                            width="5712"
                            height="4284"
                            loading="lazy"
                        />
                    </figure>

                    <p>
                        And it was through introspection and spirited solitude that I reached one of
                        my clearest revelations:
                    </p>

                    <p>
                        God wants me to focus my attention on what is life-giving so I can see all the
                        ways He is blessing me.
                    </p>

                    <p>Because I have a lot to be grateful for.</p>

                    <p>
                        My eyes are fixed upon Him. My heart is filled with gratitude. My life is
                        filled with love, magic, and adventure.
                    </p>

                    <p>And I am so grateful.</p>
                </div>

                <!-- data-ps-tags must follow the highlights root for passage validation -->
                <div
                    class="ps-work__notation ps-writing__notation"
                    data-ps-tags
                    data-ps-work-id="ps-work-returning-open-journal"
                ></div>
            </article>

            <section class="ps-writing__ps" aria-labelledby="ps-writing-notes-heading">
                <header class="ps-writing__ps-header">
                    <h2 id="ps-writing-notes-heading" class="ps-writing__ps-title">P.S.</h2>
                    <p class="ps-writing__ps-desc">Notes and highlights from readers.</p>
                    <span class="ps-writing__count" data-ps-notes-count>0 notes</span>
                </header>

                <div class="ps-writing__notes-empty" data-ps-notes-empty>
                    <p class="ps-writing__leave" tabindex="0" role="button">Leave a P.S.</p>
                </div>

                <ul class="ps-writing__notes" data-ps-notes-list hidden></ul>
            </section>

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
