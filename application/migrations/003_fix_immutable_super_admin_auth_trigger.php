<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fix Immutable Super Admin authentication compatibility.
 *
 * Migration 001 originally created a trigger that prevented changes to
 * authentication/session fields of the immutable Super Admin.
 *
 * That conflicts with Ion Auth because Ion Auth legitimately needs to
 * update:
 *
 *     remember_selector
 *     remember_code
 *     password
 *     forgotten-password fields
 *     activation fields
 *
 * The Super Admin identity remains immutable through the governance flag:
 *
 *     users.is_immutable
 *
 * This migration removes the overly restrictive protected-fields trigger.
 *
 * The existing governance triggers remain responsible for:
 *
 *     1. Allowing only one immutable user.
 *     2. Preventing an immutable user from being demoted.
 */
class Migration_Fix_Immutable_Super_Admin_Auth_Trigger extends CI_Migration
{
    public function up()
    {
        /*
         * ---------------------------------------------------------------
         * 1. Verify required column exists
         * ---------------------------------------------------------------
         */

        if (!$this->db->field_exists('is_immutable', 'users')) {
            throw new RuntimeException(
                'Migration aborted: users.is_immutable column does not exist.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 2. Verify exactly one immutable identity exists
         * ---------------------------------------------------------------
         *
         * We do not modify the identity here.
         *
         * This migration only fixes the authentication compatibility
         * trigger.
         */

        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from('users')
            ->where('is_immutable', 1)
            ->get();

        if (!$query) {
            throw new RuntimeException(
                'Migration aborted: unable to verify immutable identity.'
            );
        }

        $immutable_count = (int) $query->row()->total;

        if ($immutable_count !== 1) {
            throw new RuntimeException(
                'Migration aborted: expected exactly one immutable Super Admin identity.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 3. Remove the overly restrictive authentication trigger
         * ---------------------------------------------------------------
         *
         * Migration 001 created:
         *
         *     trg_users_immutable_protected_fields
         *
         * That trigger prevented legitimate Ion Auth operations such as:
         *
         *     UPDATE users
         *     SET remember_selector = NULL,
         *         remember_code = NULL
         *
         * We intentionally remove it.
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_users_immutable_protected_fields`
        ");

        /*
         * ---------------------------------------------------------------
         * 4. Verify the protection trigger is gone
         * ---------------------------------------------------------------
         */

        $query = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME =
                    'trg_users_immutable_protected_fields'
        ");

        if (!$query || (int) $query->row()->total !== 0) {
            throw new RuntimeException(
                'Migration failed: obsolete immutable authentication trigger still exists.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 5. Verify the core immutable governance trigger still exists
         * ---------------------------------------------------------------
         *
         * The important protection must remain:
         *
         *     immutable user cannot be demoted
         */

        $query = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME =
                    'trg_users_immutable_update'
        ");

        if (!$query || (int) $query->row()->total !== 1) {
            throw new RuntimeException(
                'Migration failed: immutable governance trigger is missing.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 6. Final invariant verification
         * ---------------------------------------------------------------
         */

        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from('users')
            ->where('is_immutable', 1)
            ->get();

        if (!$query || (int) $query->row()->total !== 1) {
            throw new RuntimeException(
                'Migration failed: immutable Super Admin invariant was not preserved.'
            );
        }
    }

    public function down()
    {
        /*
         * ---------------------------------------------------------------
         * IMPORTANT
         * ---------------------------------------------------------------
         *
         * We deliberately do NOT recreate the old
         * trg_users_immutable_protected_fields trigger.
         *
         * Recreating it would restore the bug that prevents Ion Auth
         * from updating remember-me and authentication state.
         *
         * Therefore rolling back migration 003 simply leaves the
         * authentication-fields trigger removed.
         *
         * The migration cannot safely recreate the old broken behavior.
         */

        log_message(
            'warning',
            'Migration 003 rollback: obsolete authentication protection trigger remains removed.'
        );
    }
}