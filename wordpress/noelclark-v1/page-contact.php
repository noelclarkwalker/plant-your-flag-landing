<?php
/**
 * Template Name: Contact
 * Contact page — approved frontend fidelity with Development routing/validation POC.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

set_query_var('noelclark_nav_current', 'contact');

$contact_admin_poc  = noelclark_v1_contact_admin_poc_allowed();
$contact_stage      = $contact_admin_poc ? noelclark_v1_contact_get_stage() : '';
$contact_stage_copy = $contact_stage !== '' ? noelclark_v1_contact_stage_message($contact_stage) : '';
$contact_permalink  = get_permalink();

if (!$contact_permalink) {
    $contact_permalink = home_url('/contact/');
}

get_header();
?>

<main class="contact" id="contact">
    <div class="contact__inner">
        <header class="contact__intro" aria-labelledby="contact-heading">
            <h1 class="contact__title" id="contact-heading">Contact</h1>
            <p class="contact__tagline">General inquiries.</p>
            <div class="contact__lead">
                <p>
                    Have a question, idea, opportunity, or something else you'd like to talk about? Send
                    me a message below.
                </p>
            </div>
            <p class="contact__mail-room-note">
                Want to send something to <a href="<?php echo esc_url(home_url('/mail-room/')); ?>">The Mail Room</a> instead? That's a
                different door.
            </p>
        </header>

        <form
            class="contact-form"
            id="contact-form"
            action="<?php echo esc_url($contact_permalink); ?>"
            method="post"
            aria-describedby="contact-form-status"
            novalidate
        >
            <?php wp_nonce_field(NOELCLARK_CONTACT_NONCE_ACTION, NOELCLARK_CONTACT_NONCE_FIELD); ?>
            <input type="hidden" name="<?php echo esc_attr(NOELCLARK_CONTACT_INTENT_FIELD); ?>" value="1">

            <div class="visually-hidden" aria-hidden="true">
                <input
                    type="text"
                    name="<?php echo esc_attr(NOELCLARK_CONTACT_HONEYPOT_FIELD); ?>"
                    value=""
                    tabindex="-1"
                    autocomplete="off"
                >
            </div>

            <?php if ($contact_stage_copy !== '') : ?>
                <p
                    class="contact-form__status"
                    id="contact-form-status"
                    role="status"
                    aria-live="polite"
                >
                    <?php echo esc_html($contact_stage_copy); ?>
                </p>
            <?php else : ?>
                <p class="contact-form__status visually-hidden" id="contact-form-status">
                    Form submission is not yet connected. Visual prototype only.
                </p>
            <?php endif; ?>

            <div class="contact-form__field">
                <label class="contact-form__label" for="contact-name">Name</label>
                <input
                    class="contact-form__input"
                    type="text"
                    id="contact-name"
                    name="contact_name"
                    autocomplete="name"
                    required
                />
            </div>

            <div class="contact-form__field">
                <label class="contact-form__label" for="contact-email">Email</label>
                <input
                    class="contact-form__input"
                    type="email"
                    id="contact-email"
                    name="email"
                    autocomplete="email"
                    inputmode="email"
                    spellcheck="false"
                    required
                />
            </div>

            <div class="contact-form__field">
                <label class="contact-form__label" for="contact-topic">I'm reaching out about</label>
                <div class="contact-form__select-wrap">
                    <select class="contact-form__select" id="contact-topic" name="topic" required>
                        <option value="" disabled selected>Select a topic</option>
                        <option value="general">General Inquiry</option>
                        <option value="press">Press &amp; Media</option>
                        <option value="collaboration">Collaboration / Business</option>
                        <option value="speaking">Speaking &amp; Events</option>
                        <option value="rights">Rights &amp; Permissions</option>
                        <option value="website">NoelClark.com / Website</option>
                    </select>
                </div>
            </div>

            <div class="contact-form__field">
                <label class="contact-form__label" for="contact-subject">Subject</label>
                <input
                    class="contact-form__input"
                    type="text"
                    id="contact-subject"
                    name="subject"
                    autocomplete="off"
                    required
                />
            </div>

            <div class="contact-form__field">
                <label class="contact-form__label" for="contact-message">Message</label>
                <textarea
                    class="contact-form__textarea"
                    id="contact-message"
                    name="message"
                    rows="6"
                    required
                ></textarea>
            </div>

            <button
                type="submit"
                class="contact-form__submit"
                <?php if (!$contact_admin_poc) : ?>
                    disabled
                    aria-disabled="true"
                <?php endif; ?>
            >
                Send Message &rarr;
            </button>
        </form>
    </div>
</main>

<?php
get_footer();
