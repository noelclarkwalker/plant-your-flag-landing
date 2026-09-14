<?php
/**
 * Mail Room capability foundation — Slice 1 only.
 *
 * Dedicated capability: noelclark_manage_mail_room
 *
 * Ordinary edit_posts / publish_posts do not imply this capability.
 * Editor, Author, Contributor, and Subscriber are not auto-granted it.
 * Custom / intentionally authorized roles are not stripped.
 * No admin inbox/UI is registered here.
 *
 * Capability setup is versioned and runs only when the installed
 * capability version is behind. Ordinary frontend requests do not
 * mutate roles.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var string Dedicated Mail Room private-data capability. */
const NOELCLARK_V1_MAIL_ROOM_CAPABILITY = 'noelclark_manage_mail_room';

/** @var int Theme-owned Mail Room capability foundation version. */
const NOELCLARK_V1_MAIL_ROOM_CAPABILITY_VERSION = 1;

/** @var string wp_options key for the installed capability version. */
const NOELCLARK_V1_MAIL_ROOM_CAPABILITY_OPTION = 'noelclark_v1_mail_room_capability_version';

/**
 * Ordinary WordPress roles that this foundation must not auto-grant.
 *
 * Custom roles are not listed and are never stripped.
 *
 * @return string[]
 */
function noelclark_v1_mail_room_excluded_roles() {
    return array(
        'editor',
        'author',
        'contributor',
        'subscriber',
    );
}

/**
 * Installed capability version, or 0 when unset.
 *
 * @return int
 */
function noelclark_v1_mail_room_installed_capability_version() {
    return (int) get_option(NOELCLARK_V1_MAIL_ROOM_CAPABILITY_OPTION, 0);
}

/**
 * Apply the dedicated capability once per capability-version advance.
 *
 * Administrator receives noelclark_manage_mail_room.
 * Editor / Author / Contributor / Subscriber are not granted it.
 * Other roles are not mutated.
 *
 * @return void
 */
function noelclark_v1_mail_room_maybe_register_capabilities() {
    $installed = noelclark_v1_mail_room_installed_capability_version();

    if ($installed >= NOELCLARK_V1_MAIL_ROOM_CAPABILITY_VERSION) {
        return;
    }

    $administrator = get_role('administrator');

    if (!$administrator) {
        return;
    }

    if (!$administrator->has_cap(NOELCLARK_V1_MAIL_ROOM_CAPABILITY)) {
        $administrator->add_cap(NOELCLARK_V1_MAIL_ROOM_CAPABILITY);
    }

    foreach (noelclark_v1_mail_room_excluded_roles() as $role_name) {
        $role = get_role($role_name);

        if ($role && $role->has_cap(NOELCLARK_V1_MAIL_ROOM_CAPABILITY)) {
            $role->remove_cap(NOELCLARK_V1_MAIL_ROOM_CAPABILITY);
        }
    }

    if (!$administrator->has_cap(NOELCLARK_V1_MAIL_ROOM_CAPABILITY)) {
        return;
    }

    update_option(
        NOELCLARK_V1_MAIL_ROOM_CAPABILITY_OPTION,
        NOELCLARK_V1_MAIL_ROOM_CAPABILITY_VERSION,
        true
    );
}
add_action('init', 'noelclark_v1_mail_room_maybe_register_capabilities', 4);
add_action('after_switch_theme', 'noelclark_v1_mail_room_maybe_register_capabilities');
