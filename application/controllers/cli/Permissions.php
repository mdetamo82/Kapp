<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI Permission Synchronizer
 *
 * Synchronizes canonical permission definitions from:
 *
 *     application/config/permissions.php
 *
 * into:
 *
 *     permissions
 *
 * Responsibilities:
 *
 * - Validate canonical permission configuration
 * - Add missing permissions
 * - Update changed permissions
 * - Detect stale database permissions
 * - Never automatically delete permissions
 *
 * Usage:
 *
 *     php index.php cli/permissions sync
 */
class Permissions extends CI_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        /*
         * This controller is CLI-only.
         */
        if (!$this->input->is_cli_request()) {
            show_404();
            return;
        }

        $this->load->database();
    }


    /**
     * Synchronize canonical permissions.
     *
     * @return void
     */
    public function sync()
    {
        echo "\n";
        echo "Permission Synchronization\n";
        echo "==========================\n\n";

        /*
         * Load canonical configuration.
         */
        $this->config->load('permissions', true);

        $permissions = $this->config->item(
            'permissions',
            'permissions'
        );

        /*
         * Validate configuration.
         */
        if (!$this->validate_configuration($permissions)) {
            echo "\nERROR: Permission configuration is invalid.\n";
            exit(1);
        }

        /*
         * Build canonical permission map.
         */
        $canonical = [];

        foreach ($permissions as $controller => $methods) {

            foreach ($methods as $method => $description) {

                $canonical[$method] = [
                    'name'        => $method,
                    'description' => $description,
                    'controller'  => $controller,
                    'method'      => $method,
                ];
            }
        }

        /*
         * Synchronization counters.
         */
        $added = 0;
        $updated = 0;
        $unchanged = 0;
        $stale = 0;

        /*
         * ---------------------------------------------------------
         * ADD / UPDATE
         * ---------------------------------------------------------
         */

        foreach ($canonical as $method => $data) {

            $existing = $this->db
                ->where('name', $method)
                ->limit(1)
                ->get('permissions')
                ->row();

            /*
             * Permission does not exist.
             */
            if (!$existing) {

                if (!$this->db->insert('permissions', $data)) {

                    echo "[ERROR] Failed to add: {$method}\n";

                    exit(1);
                }

                echo "[ADDED] {$method}\n";

                $added++;

                continue;
            }

            /*
             * Detect changes.
             */
            $changed = (
                (string) $existing->description !==
                    (string) $data['description']

                ||

                (string) $existing->controller !==
                    (string) $data['controller']

                ||

                (string) $existing->method !==
                    (string) $data['method']
            );

            /*
             * Update changed permission.
             */
            if ($changed) {

                $updated_ok = $this->db
                    ->where('id', $existing->id)
                    ->update('permissions', $data);

                if (!$updated_ok) {

                    echo "[ERROR] Failed to update: {$method}\n";

                    exit(1);
                }

                echo "[UPDATED] {$method}\n";

                $updated++;

                continue;
            }

            $unchanged++;
        }


        /*
         * ---------------------------------------------------------
         * STALE PERMISSIONS
         * ---------------------------------------------------------
         *
         * A stale permission exists in the database but no longer
         * exists in permissions.php.
         *
         * IMPORTANT:
         *
         * We DO NOT delete it automatically.
         *
         * It may already be assigned to a group.
         */

        $database_permissions = $this->db
            ->select('id, name, controller, method')
            ->order_by('controller', 'ASC')
            ->order_by('id', 'ASC')
            ->get('permissions')
            ->result();

        foreach ($database_permissions as $permission) {

            $name = trim((string) $permission->name);

            if (!isset($canonical[$name])) {

                echo "[STALE] {$name}";

                if (!empty($permission->controller)) {
                    echo " ({$permission->controller})";
                }

                echo "\n";

                $stale++;
            }
        }


        /*
         * ---------------------------------------------------------
         * SUMMARY
         * ---------------------------------------------------------
         */

        echo "\n";
        echo "Synchronization completed.\n";
        echo "-------------------------\n";
        echo "Added:       {$added}\n";
        echo "Updated:     {$updated}\n";
        echo "Unchanged:   {$unchanged}\n";
        echo "Stale:       {$stale}\n";
        echo "-------------------------\n";

        if ($stale > 0) {

            echo "\n";
            echo "WARNING:\n";
            echo "Stale permissions were detected but NOT deleted.\n";
            echo "Review them manually before removing them.\n";
        }

        echo "\n";
    }


    /**
     * Validate canonical permission configuration.
     *
     * @param mixed $permissions
     * @return bool
     */
    protected function validate_configuration($permissions)
    {
        if (!is_array($permissions)) {
            echo "[ERROR] permissions must be an array.\n";

            return false;
        }

        $seen = [];

        foreach ($permissions as $controller => $methods) {

            /*
             * Controller/module key validation.
             */
            if (!is_string($controller) || trim($controller) === '') {

                echo "[ERROR] Invalid controller/module key.\n";

                return false;
            }

            if (!is_array($methods) || empty($methods)) {

                echo "[ERROR] Invalid permission group: {$controller}\n";

                return false;
            }

            foreach ($methods as $method => $description) {

                /*
                 * Permission key.
                 */
                if (
                    !is_string($method) ||
                    trim($method) === ''
                ) {

                    echo "[ERROR] Empty permission key in {$controller}.\n";

                    return false;
                }

                $method = trim($method);

                /*
                 * Require lowercase machine keys.
                 */
                if ($method !== strtolower($method)) {

                    echo "[ERROR] Permission must be lowercase: {$method}\n";

                    return false;
                }

                /*
                 * Require underscore naming.
                 */
                if (
                    !preg_match(
                        '/^[a-z][a-z0-9_]*$/',
                        $method
                    )
                ) {

                    echo "[ERROR] Invalid permission key: {$method}\n";

                    return false;
                }

                /*
                 * Duplicate detection.
                 */
                if (isset($seen[$method])) {

                    echo "[ERROR] Duplicate permission: {$method}\n";

                    return false;
                }

                $seen[$method] = true;

                /*
                 * Description validation.
                 */
                if (
                    !is_string($description) ||
                    trim($description) === ''
                ) {

                    echo "[ERROR] Missing description: {$method}\n";

                    return false;
                }
            }
        }

        return true;
    }
}