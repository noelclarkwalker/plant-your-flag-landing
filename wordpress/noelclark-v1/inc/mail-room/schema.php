<?php
/**
 * Mail Room private schema definitions — Slice 1 foundation only.
 *
 * No handlers, REST, notification, or admin UI live here.
 *
 * Table classes:
 * - IMMUTABLE: insert-only historical source. Do not UPDATE accepted facts.
 * - APPEND-ONLY: history rows. New facts are new rows.
 * - OPERATIONAL: current projections; later handlers may UPDATE these only.
 * - SHORT-LIVED: security/transaction support; purgeable.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Theme-owned Mail Room schema version. */
const NOELCLARK_V1_MAIL_ROOM_SCHEMA_VERSION = 1;

/** @var string wp_options key for the installed schema version. */
const NOELCLARK_V1_MAIL_ROOM_SCHEMA_OPTION = 'noelclark_v1_mail_room_schema_version';

/**
 * Prefixed table name.
 *
 * @param string $suffix Table suffix without the nc_ prefix (e.g. participants).
 * @return string
 */
function noelclark_v1_mail_room_table($suffix) {
    global $wpdb;

    return $wpdb->prefix . 'nc_' . $suffix;
}

/**
 * Canonical Mail Room table inventory, keyed by suffix.
 *
 * @return string[] Prefixed table names keyed by suffix.
 */
function noelclark_v1_mail_room_expected_tables() {
    $suffixes = array(
        'participants',
        'mr_submissions',
        'q_responses',
        'questionnaire_versions',
        'legal_documents',
        'mr_events',
        'participant_links',
        'mr_state',
        'notification_jobs',
        'questionnaires',
        'idempotency',
        'mr_association_tokens',
        'rate_limits',
    );

    $tables = array();

    foreach ($suffixes as $suffix) {
        $tables[$suffix] = noelclark_v1_mail_room_table($suffix);
    }

    return $tables;
}

/**
 * CREATE TABLE statements for dbDelta.
 *
 * Internal AUTO_INCREMENT ids are storage keys only. Application identity uses
 * opaque *_ref columns. Sequential PKs must not be exposed publicly.
 *
 * @return string[]
 */
function noelclark_v1_mail_room_schema_statements() {
    global $wpdb;

    $charset = $wpdb->get_charset_collate();
    $tables  = noelclark_v1_mail_room_expected_tables();

    $participants           = $tables['participants'];
    $mr_submissions         = $tables['mr_submissions'];
    $q_responses            = $tables['q_responses'];
    $questionnaire_versions = $tables['questionnaire_versions'];
    $legal_documents        = $tables['legal_documents'];
    $mr_events              = $tables['mr_events'];
    $participant_links      = $tables['participant_links'];
    $mr_state               = $tables['mr_state'];
    $notification_jobs      = $tables['notification_jobs'];
    $questionnaires         = $tables['questionnaires'];
    $idempotency            = $tables['idempotency'];
    $association_tokens     = $tables['mr_association_tokens'];
    $rate_limits            = $tables['rate_limits'];

    return array(
        // IMMUTABLE: Participant as captured at intake. No email uniqueness.
        // Later reconciliation must not rewrite these rows.
        "CREATE TABLE {$participants} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            participant_ref varchar(40) NOT NULL,
            display_name varchar(200) NOT NULL,
            email varchar(254) NOT NULL,
            age_attested tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY participant_ref (participant_ref),
            KEY email (email)
        ) {$charset};",

        // IMMUTABLE: accepted Mail Room letter / provenance.
        // Do not UPDATE letter, sharing, submitted credit, accepted_participant_id,
        // attestation, Terms relationship, timestamps, or mr_ref.
        // Operational status lives in nc_mr_state / events / notification jobs.
        "CREATE TABLE {$mr_submissions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mr_ref varchar(40) NOT NULL,
            accepted_participant_id bigint(20) unsigned NOT NULL,
            letter_body longtext NOT NULL,
            sharing_choice varchar(32) NOT NULL,
            public_credit varchar(200) DEFAULT NULL,
            age_attested tinyint(1) NOT NULL,
            legal_document_id bigint(20) unsigned DEFAULT NULL,
            legal_document_version varchar(64) DEFAULT NULL,
            terms_accepted_at datetime NOT NULL,
            accepted_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY mr_ref (mr_ref),
            KEY accepted_participant_id (accepted_participant_id),
            KEY sharing_choice (sharing_choice),
            KEY accepted_at (accepted_at)
        ) {$charset};",

        // IMMUTABLE: questionnaire response snapshot.
        // Do not UPDATE answers, version, accepted_participant_id, or associated MR.
        "CREATE TABLE {$q_responses} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            q_ref varchar(40) NOT NULL,
            accepted_participant_id bigint(20) unsigned NOT NULL,
            questionnaire_version_id bigint(20) unsigned NOT NULL,
            associated_mr_id bigint(20) unsigned DEFAULT NULL,
            answers_json longtext NOT NULL,
            legal_document_id bigint(20) unsigned DEFAULT NULL,
            legal_document_version varchar(64) DEFAULT NULL,
            age_attested tinyint(1) NOT NULL,
            accepted_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY q_ref (q_ref),
            KEY accepted_participant_id (accepted_participant_id),
            KEY questionnaire_version_id (questionnaire_version_id),
            KEY associated_mr_id (associated_mr_id)
        ) {$charset};",

        // IMMUTABLE: frozen questionnaire wording / context for one version.
        "CREATE TABLE {$questionnaire_versions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            questionnaire_id bigint(20) unsigned NOT NULL,
            version_label varchar(64) NOT NULL,
            intro_json longtext,
            questions_json longtext NOT NULL,
            legal_document_id bigint(20) unsigned DEFAULT NULL,
            released_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY questionnaire_version (questionnaire_id, version_label),
            KEY questionnaire_id (questionnaire_id)
        ) {$charset};",

        // IMMUTABLE: released Terms / questionnaire-consent body snapshots.
        "CREATE TABLE {$legal_documents} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            doc_type varchar(64) NOT NULL,
            version_label varchar(64) NOT NULL,
            body_snapshot longtext NOT NULL,
            released_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY doc_type_version (doc_type, version_label)
        ) {$charset};",

        // APPEND-ONLY: MR history. New facts are new rows.
        "CREATE TABLE {$mr_events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_ref varchar(40) NOT NULL,
            mr_id bigint(20) unsigned NOT NULL,
            event_type varchar(64) NOT NULL,
            payload_json longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_ref (event_ref),
            KEY mr_id (mr_id),
            KEY event_type (event_type)
        ) {$charset};",

        // APPEND-ONLY: verified Participant reconciliation history.
        // Must not rewrite accepted_participant_id on MR/Q rows.
        // Visitor path does not write this table in V1.
        "CREATE TABLE {$participant_links} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            link_ref varchar(40) NOT NULL,
            from_participant_id bigint(20) unsigned NOT NULL,
            canonical_participant_id bigint(20) unsigned NOT NULL,
            decision_note varchar(500) DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY link_ref (link_ref),
            KEY from_participant_id (from_participant_id),
            KEY canonical_participant_id (canonical_participant_id)
        ) {$charset};",

        // OPERATIONAL: current MR projection. Later handlers may UPDATE these columns.
        // Original sharing / credit / participant remain on nc_mr_submissions.
        "CREATE TABLE {$mr_state} (
            mr_id bigint(20) unsigned NOT NULL,
            editorial_status varchar(32) NOT NULL,
            operative_treatment varchar(32) NOT NULL,
            operative_public_credit varchar(200) DEFAULT NULL,
            withdrawal_request_status varchar(32) NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (mr_id),
            KEY editorial_status (editorial_status),
            KEY operative_treatment (operative_treatment)
        ) {$charset};",

        // OPERATIONAL: notification job / retry projection. Not the source of accept.
        "CREATE TABLE {$notification_jobs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mr_id bigint(20) unsigned NOT NULL,
            status varchar(16) NOT NULL,
            attempts int(10) unsigned NOT NULL DEFAULT 0,
            last_attempt_at datetime DEFAULT NULL,
            last_error_code varchar(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY mr_id (mr_id),
            KEY status (status)
        ) {$charset};",

        // OPERATIONAL: questionnaire definition lifecycle (draft/active/retired).
        "CREATE TABLE {$questionnaires} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            questionnaire_ref varchar(64) NOT NULL,
            title varchar(200) NOT NULL,
            purpose text,
            status varchar(16) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY questionnaire_ref (questionnaire_ref),
            KEY status (status)
        ) {$charset};",

        // SHORT-LIVED: transaction idempotency support. No handler in this slice.
        "CREATE TABLE {$idempotency} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            scope varchar(32) NOT NULL,
            idempotency_key varchar(64) NOT NULL,
            mr_id bigint(20) unsigned DEFAULT NULL,
            q_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY scope_key (scope, idempotency_key)
        ) {$charset};",

        // SHORT-LIVED: hashed association credentials. No handler in this slice.
        "CREATE TABLE {$association_tokens} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mr_id bigint(20) unsigned NOT NULL,
            token_hash char(64) NOT NULL,
            expires_at datetime NOT NULL,
            consumed_at datetime DEFAULT NULL,
            revoked_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY mr_id (mr_id),
            KEY expires_at (expires_at)
        ) {$charset};",

        // SHORT-LIVED: hashed abuse counters. Not provenance. No handler in this slice.
        "CREATE TABLE {$rate_limits} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_hash char(64) NOT NULL,
            action_key varchar(32) NOT NULL,
            window_started_at datetime NOT NULL,
            hit_count int(10) unsigned NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY client_action_window (client_hash, action_key, window_started_at)
        ) {$charset};",
    );
}
