<?php
/**
 * Mail Room schema version / migration — Slice 1 foundation only.
 *
 * dbDelta is additive and idempotent. This file never DROP/TRUNCATE tables,
 * never deletes historical rows, and never updates existing submission,
 * state, publication, or title values. Version 4 only adds nullable columns.
 *
 * Runs on init so an already-active theme still migrates when the version
 * constant advances. Also runs after theme switch.
 *
 * Phase 1 recovery note (2026-11-21):
 * Schema/install source is restored for NoelClarkDev parity, but this Cloud
 * Agent session does NOT bootstrap WordPress and does NOT execute migrations,
 * alter NoelClarkDev/Production tables, or update schema-version options.
 * Verify existing NoelClarkDev schema before any future theme deploy that
 * would allow init to run these hooks.
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
 * Whether nc_mr_state has the private editorial note column.
 *
 * Does not print or log the table name. A database error counts as missing
 * so the schema version is not recorded.
 *
 * @return bool
 */
function noelclark_v1_mail_room_state_private_note_exists() {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_state');
    $prior_error = $wpdb->last_error;
    $wpdb->last_error = '';
    $found = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE %s',
            'private_note'
        )
    );
    $column_error = $wpdb->last_error;
    $wpdb->last_error = $prior_error;

    if ($column_error !== '') {
        return false;
    }

    return $found === 'private_note';
}

/**
 * Whether nc_mr_publications has its permanent public identifier column.
 *
 * Does not print or log the table name. A database error counts as missing
 * so the schema version is not recorded.
 *
 * @return bool
 */
function noelclark_v1_mail_room_publications_slug_column_exists() {
    global $wpdb;

    $table = noelclark_v1_mail_room_table('mr_publications');
    $prior_error = $wpdb->last_error;
    $wpdb->last_error = '';
    $found = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE %s',
            'public_slug'
        )
    );
    $column_error = $wpdb->last_error;
    $wpdb->last_error = $prior_error;

    if ($column_error !== '') {
        return false;
    }

    return $found === 'public_slug';
}

/**
 * Whether one Mail Room column exists.
 *
 * A database error counts as missing so the schema version is not recorded.
 *
 * @param string $suffix Table suffix without the nc_ prefix.
 * @param string $column Column name.
 * @return bool
 */
function noelclark_v1_mail_room_table_column_exists($suffix, $column) {
    global $wpdb;

    if (!is_string($suffix) || !preg_match('/^[a-z0-9_]+$/', $suffix)) {
        return false;
    }

    if (!is_string($column) || !preg_match('/^[a-z0-9_]+$/', $column)) {
        return false;
    }

    $table = noelclark_v1_mail_room_table($suffix);
    $prior_error = $wpdb->last_error;
    $wpdb->last_error = '';
    $found = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE %s',
            $column
        )
    );
    $column_error = $wpdb->last_error;
    $wpdb->last_error = $prior_error;

    if ($column_error !== '') {
        return false;
    }

    return $found === $column;
}

/**
 * Whether schema version 4's nullable recipient and hold columns exist.
 *
 * @return bool
 */
function noelclark_v1_mail_room_schema_v4_columns_exist() {
    $required = array(
        'mr_submissions' => array(
            'recipient_original',
            'recipient_display_choice',
            'recipient_public_wording',
        ),
        'mr_state' => array(
            'operative_recipient_display',
            'operative_recipient_wording',
            'protective_hold',
        ),
    );

    foreach ($required as $suffix => $columns) {
        foreach ($columns as $column) {
            if (!noelclark_v1_mail_room_table_column_exists($suffix, $column)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Apply missing/updated table definitions when the theme schema version is newer.
 *
 * The schema version is recorded only after every expected table exists,
 * nc_mr_state.private_note exists, nc_mr_publications.public_slug exists,
 * and the version 4 recipient and hold columns exist. A missing column
 * leaves the installed version unchanged so the next request runs dbDelta
 * again. Existing row values are not updated.
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

    if (!noelclark_v1_mail_room_state_private_note_exists()) {
        return;
    }

    if (!noelclark_v1_mail_room_publications_slug_column_exists()) {
        return;
    }

    if (!noelclark_v1_mail_room_schema_v4_columns_exist()) {
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
