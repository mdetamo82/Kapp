<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Create dedicated immutable Super Admin governance identity.
 *
 * This table is intentionally separate from Ion Auth groups.
 *
 * Governance identity:
 *
 *     users.is_immutable
 *              +
 *     super_admin_identity
 *
 * Both must agree.
 */
class Migration_Create_Super_Admin_Identity extends CI_Migration
{
    private $table = 'super_admin_identity';

    public function up()
    {
        /*
         * ---------------------------------------------------------------
         * 1. Create governance identity table
         * ---------------------------------------------------------------
         */

        if (!$this->db->table_exists($this->table)) {

            $this->db->query("
                CREATE TABLE `super_admin_identity` (

                    `user_id` INT UNSIGNED NOT NULL,

                    `granted_by` INT UNSIGNED NULL,

                    `grant_method` ENUM(
                        'migration_bootstrap',
                        'admin_action'
                    ) NOT NULL,

                    `created_at` DATETIME NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    PRIMARY KEY (`user_id`),

                    CONSTRAINT `fk_super_admin_identity_user`
                        FOREIGN KEY (`user_id`)
                        REFERENCES `users` (`id`)
                        ON DELETE RESTRICT
                        ON UPDATE CASCADE,

                    CONSTRAINT `fk_super_admin_identity_granted_by`
                        FOREIGN KEY (`granted_by`)
                        REFERENCES `users` (`id`)
                        ON DELETE SET NULL
                        ON UPDATE CASCADE

                )
                ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        /*
         * ---------------------------------------------------------------
         * 2. Bootstrap existing immutable identity
         * ---------------------------------------------------------------
         *
         * We do NOT create another user.
         *
         * We locate the already immutable administrator and register
         * that identity in the dedicated governance table.
         */

        $query = $this->db
            ->select('id, is_immutable')
            ->from('users')
            ->where('username', 'administrator')
            ->where('email', 'admin@admin.com')
            ->where('is_immutable', 1)
            ->limit(1)
            ->get();

        if (!$query || $query->num_rows() !== 1) {
            throw new RuntimeException(
                'Migration aborted: exactly one immutable administrator identity was expected.'
            );
        }

        $super_admin = $query->row();

        /*
         * ---------------------------------------------------------------
         * 3. Verify governance table contains zero or one row
         * ---------------------------------------------------------------
         */

        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from($this->table)
            ->get();

        if (!$query) {
            throw new RuntimeException(
                'Migration aborted: unable to inspect Super Admin identity table.'
            );
        }

        $identity_count = (int) $query->row()->total;

        if ($identity_count > 1) {
            throw new RuntimeException(
                'Migration aborted: more than one Super Admin governance identity exists.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 4. Register immutable identity if not already registered
         * ---------------------------------------------------------------
         */

        if ($identity_count === 0) {

            $inserted = $this->db->insert($this->table, [
                'user_id'      => (int) $super_admin->id,
                'granted_by'   => null,
                'grant_method' => 'migration_bootstrap',
            ]);

            if (!$inserted) {
                throw new RuntimeException(
                    'Migration aborted: unable to register immutable governance identity.'
                );
            }
        }

        /*
         * ---------------------------------------------------------------
         * 5. Verify exact synchronization
         * ---------------------------------------------------------------
         */

        $query = $this->db
            ->select('s.user_id, u.is_immutable')
            ->from('super_admin_identity s')
            ->join('users u', 'u.id = s.user_id')
            ->get();

        if (!$query || $query->num_rows() !== 1) {
            throw new RuntimeException(
                'Migration failed: Super Admin governance identity is not unique.'
            );
        }

        $identity = $query->row();

        if ((int) $identity->is_immutable !== 1) {
            throw new RuntimeException(
                'Migration failed: governance identity does not correspond to an immutable user.'
            );
        }

        /*
         * ---------------------------------------------------------------
         * 6. BEFORE INSERT
         * ---------------------------------------------------------------
         *
         * Rules:
         *
         *  - only one row may ever exist
         *  - referenced user must be immutable
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_insert`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_super_admin_identity_insert`
            BEFORE INSERT ON `super_admin_identity`
            FOR EACH ROW
            BEGIN

                IF EXISTS (
                    SELECT 1
                    FROM `super_admin_identity`
                ) THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Only one Super Admin governance identity is permitted';

                END IF;

                IF NOT EXISTS (
                    SELECT 1
                    FROM `users`
                    WHERE `id` = NEW.user_id
                      AND `is_immutable` = 1
                ) THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Super Admin governance identity requires an immutable user';

                END IF;

            END
        ");

        /*
         * ---------------------------------------------------------------
         * 7. BEFORE UPDATE
         * ---------------------------------------------------------------
         *
         * This table is an immutable governance registry.
         *
         * There is deliberately no normal UPDATE path.
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_update`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_super_admin_identity_update`
            BEFORE UPDATE ON `super_admin_identity`
            FOR EACH ROW
            BEGIN

                IF NOT (OLD.user_id <=> NEW.user_id) THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Super Admin governance identity cannot be reassigned';

                END IF;

                IF NOT (OLD.granted_by <=> NEW.granted_by)
                   OR NOT (OLD.grant_method <=> NEW.grant_method)
                   OR NOT (OLD.created_at <=> NEW.created_at) THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Super Admin governance identity is immutable';

                END IF;

                IF NOT EXISTS (
                    SELECT 1
                    FROM `users`
                    WHERE `id` = NEW.user_id
                      AND `is_immutable` = 1
                ) THEN

                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT =
                        'Super Admin governance identity requires an immutable user';

                END IF;

            END
        ");

        /*
         * ---------------------------------------------------------------
         * 8. BEFORE DELETE
         * ---------------------------------------------------------------
         *
         * There is intentionally no normal delete path.
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_delete`
        ");

        $this->db->query("
            CREATE TRIGGER `trg_super_admin_identity_delete`
            BEFORE DELETE ON `super_admin_identity`
            FOR EACH ROW
            BEGIN

                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT =
                    'Super Admin governance identity cannot be deleted';

            END
        ");

        /*
         * ---------------------------------------------------------------
         * 9. Final verification
         * ---------------------------------------------------------------
         */

        $query = $this->db
            ->select('COUNT(*) AS total')
            ->from($this->table)
            ->get();

        if (!$query || (int) $query->row()->total !== 1) {
            throw new RuntimeException(
                'Migration failed: exactly one Super Admin governance identity is required.'
            );
        }
    }


    public function down()
    {
        /*
         * We intentionally do not silently destroy the governance
         * identity.
         *
         * A rollback of this migration is a security-sensitive
         * break-glass operation.
         */

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_insert`
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_update`
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS
                `trg_super_admin_identity_delete`
        ");

        /*
         * Do not automatically DROP the table.
         *
         * Removing the governance registry while users.is_immutable
         * remains active would create a security inconsistency.
         *
         * An explicit DBA break-glass procedure must handle this.
         */
    }
}