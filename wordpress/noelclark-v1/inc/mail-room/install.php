<?php
/**
 * Mail Room schema version / migration — Slice 1 foundation only.
 *
 * dbDelta is additive and idempotent. This file never DROP/TRUNCATE tables
 * and never deletes historical rows.
 *
 * Runs on init so an already-active theme still migrates when the version
 * constant advances. Also runs after theme switch.
 *
 * This slice does not deploy or execute against Production.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installed schema version, or 0 when uninstalled.
 *
 * @return int
 */
function noelclark_v1_mail_room_installed_schema_version() {
    return (int) get_option(NOELCLARK_V1_MAIL_ROOM_SCHEMA_OPTION, 0);
}

/**
 * Whether every expected Mail Room table exists.
 *
 * Does not print or log table names.
 *
 * @return bool
 */
function noelclark_v1_mail_room_expected_tables_exist() {
    global $wpdb;

    foreach (noelclark_v1_mail_room_expected_tables() as $table) {
        $found = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
        );

        if ($found !== $table) {
            return false;
        }
    }

    return true;
}

/**
 * Apply missing/updated table definitions when the theme schema version is newer.
 *
 * The installed schema version advances only after every expected table exists.
 *
 * @return void
 */
function noelclark_v1_mail_room_maybe_install_schema() {
    $installed = noelclark_v1_mail_room_installed_schema_version();

    if ($installed >= NOELCLARK_V1_MAIL_ROOM_SCHEMA_VERSION) {
        return;
    }

    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    foreach (noelclark_v1_mail_room_schema_statements() as $sql) {
        dbDelta($sql);
    }

    if (!noelclark_v1_mail_room_expected_tables_exist()) {
        return;
    }

    update_option(
        NOELCLARK_V1_MAIL_ROOM_SCHEMA_OPTION,
        NOELCLARK_V1_MAIL_ROOM_SCHEMA_VERSION,
        true
    );
}
add_action('init', 'noelclark_v1_mail_room_maybe_install_schema', 5);
add_action('after_switch_theme', 'noelclark_v1_mail_room_maybe_install_schema');
