<?php
/**
 * Mail Room opaque reference generation — Slice 2.
 *
 * Internal AUTO_INCREMENT ids are storage keys only. These helpers produce
 * non-sequential application references. Sequential PKs must not be exposed.
 *
 * @package NoelClark_V1
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var int Opaque *_ref length (hex characters). Fits schema varchar(40). */
const NOELCLARK_V1_MAIL_ROOM_REF_LENGTH = 32;

/**
 * Generate one opaque non-sequential reference.
 *
 * @return string 32-character lowercase hex.
 */
function noelclark_v1_mail_room_generate_opaque_ref() {
    return bin2hex(random_bytes(16));
}
