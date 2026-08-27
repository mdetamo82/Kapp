<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_Immutable_Super_Admin extends CI_Migration
{
    public function up()
    {
        /*
         * ---------------------------------------------------------------
         * 1. Add immutable identity flag
         * ---------------------------------------------------------------
         *
         * Idempotent: do nothing if the column already exists.
         */
        if (!$this->db->field_exists('is_immutable', 'users')) {
            $this->db->query("
                ALTER TABLE `users`
                ADD COLUMN `is_immutable` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
                AFTER `active`
            ");
        }

        /*
         * ---------------------------------------------------------------
         * 2. Verify existing immutable count
         * ---------------------------------------------------------------
         *
         * We intentionally refuse to continue if another immutable
         * identity already exists.
         */
        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from('users')
            ->where('is_immutable', 1)
            ->get();

        if (!$query) {
            throw new RuntimeException(
                'Unable to determine existing immutable identities.'
            );
        }

        $immutable_count = (int) $query->row()->total;

        if ($immutable_count > 1) {
            throw new RuntimeException(
                'Migration aborted: more than one immutable identity already exists.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 3. Locate the intended existing Super Admin identity
         * ---------------------------------------------------------------
         *
         * We identify the existing administrator by its stable identity,
         * not by user ID.
         *
         * This is migration-time bootstrapping only.
         *
         * Application authorization code MUST NEVER use user ID 1
         * as a Super Admin check.
         */
        $query = $this->db
            ->select('id, username, email, is_immutable')
            ->from('users')
            ->where('username', 'administrator')
            ->where('email', 'admin@admin.com')
            ->limit(1)
            ->get();

        if (!$query) {
            throw new RuntimeException(
                'Migration aborted: expected administrator identity was not found.'
            );
        }

        if ($query->num_rows() !== 1) {
            throw new RuntimeException(
                'Migration aborted: expected administrator identity is ambiguous.'
            );
        }

        $super_admin = $query->row();

        /*
         * ---------------------------------------------------------------
         * 4. Protect against conflicting immutable identity
         * ---------------------------------------------------------------
         */
        if ($immutable_count === 1 && (int) $super_admin->is_immutable !== 1) {
            throw new RuntimeException(
                'Migration aborted: another immutable identity already exists.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 5. Promote existing administrator identity
         * ---------------------------------------------------------------
         */
        if ((int) $super_admin->is_immutable !== 1) {

            $updated = $this->db
                ->where('id', (int) $super_admin->id)
                ->where('is_immutable', 0)
                ->update('users', [
                    'is_immutable' => 1
                ]);

            if (!$updated) {
                throw new RuntimeException(
                    'Migration aborted: unable to promote Super Admin identity.'
                );
            }
        }

        /*
         * ---------------------------------------------------------------
         * 6. Verify exactly one immutable identity now exists
         * ---------------------------------------------------------------
         */
        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from('users')
            ->where('is_immutable', 1)
            ->get();

        if (!$query || (int) $query->row()->total !== 1) {
            throw new RuntimeException(
                'Migration failed: database does not contain exactly one immutable identity.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 7. Database trigger: exactly one immutable identity
         * ---------------------------------------------------------------
         *
         * MariaDB 10.4 does not provide a partial unique index, so the
         * invariant is enforced with triggers.
         */
        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_single_immutable_insert`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_users_single_immutable_insert`
            BEFORE INSERT ON `users`
            FOR EACH ROW
            BEGIN
                IF NEW.is_immutable = 1 THEN
                    IF EXISTS (
                        SELECT 1
                        FROM `users`
                        WHERE `is_immutable` = 1
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Only one immutable Super Admin identity is permitted';
                    END IF;
                END IF;
            END
        ");

        /*
         * ---------------------------------------------------------------
         * 8. Database trigger: immutable flag cannot be removed
         * ---------------------------------------------------------------
         */
        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_update`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_users_immutable_update`
            BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN

                /*
                 * Existing immutable identity cannot be demoted.
                 */
                IF OLD.is_immutable = 1
                   AND NEW.is_immutable <> 1 THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Immutable Super Admin identity cannot be demoted';

                END IF;

                /*
                 * A second identity cannot become immutable.
                 */
                IF OLD.is_immutable = 0
                   AND NEW.is_immutable = 1 THEN

                    IF EXISTS (
                        SELECT 1
                        FROM `users`
                        WHERE `is_immutable` = 1
                    ) THEN

                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Only one immutable Super Admin identity is permitted';

                    END IF;

                END IF;

            END
        ");

        /*
         * ---------------------------------------------------------------
         * 9. Database trigger: protect immutable identity fields
         * ---------------------------------------------------------------
         *
         * These fields cannot be changed directly in SQL once the
         * identity is immutable.
         *
         * Authentication itself remains possible.
         */
        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_protected_fields`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_users_immutable_protected_fields`
            BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN

                IF OLD.is_immutable = 1 THEN

                    IF NOT (OLD.password <=> NEW.password)
                       OR NOT (OLD.email <=> NEW.email)
                       OR NOT (OLD.username <=> NEW.username)
                       OR NOT (OLD.active <=> NEW.active)
                       OR NOT (OLD.ip_address <=> NEW.ip_address)
                       OR NOT (OLD.activation_selector <=> NEW.activation_selector)
                       OR NOT (OLD.activation_code <=> NEW.activation_code)
                       OR NOT (OLD.forgotten_password_selector <=> NEW.forgotten_password_selector)
                       OR NOT (OLD.forgotten_password_code <=> NEW.forgotten_password_code)
                       OR NOT (OLD.forgotten_password_time <=> NEW.forgotten_password_time)
                       OR NOT (OLD.remember_selector <=> NEW.remember_selector)
                       OR NOT (OLD.remember_code <=> NEW.remember_code) THEN

                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT =
                            'Immutable Super Admin identity cannot be modified';

                    END IF;

                END IF;

            END
        ");

        /*
         * ---------------------------------------------------------------
         * 10. Database trigger: immutable identity cannot be deleted
         * ---------------------------------------------------------------
         */
        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_delete`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_users_immutable_delete`
            BEFORE DELETE ON `users`
            FOR EACH ROW
            BEGIN

                IF OLD.is_immutable = 1 THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Immutable Super Admin identity cannot be deleted';

                END IF;

            END
        ");
    }


    public function down()
    {
        /*
         * WARNING:
         *
         * Rolling back this migration removes the database-level
         * immutability protection.
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_delete`
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_protected_fields`
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_immutable_update`
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS `trg_users_single_immutable_insert`
        ");

        if ($this->db->field_exists('is_immutable', 'users')) {
            $this->db->query("
                ALTER TABLE `users`
                DROP COLUMN `is_immutable`
            ");
        }
    }
}