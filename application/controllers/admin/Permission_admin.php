<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Administration
 *
 * Responsible for assigning existing application permissions
 * to authorization groups.
 *
 * Permission definitions themselves are synchronized through
 * the CLI permission synchronizer.
 */
class Permission_admin extends CI_Controller
{
    /**
     * Permission required to access this controller.
     */
    private const REQUIRED_PERMISSION = 'manage_roles_permissions';

    public function __construct()
    {
        parent::__construct();

       $this->load->library('template'); $this->load->model('Permission_model');
        $this->load->model('ion_auth_model');
        $this->load->library('authorization');

        /*
         * Single authorization boundary.
         *
         * No direct ion_auth->is_admin() check belongs here.
         */
        $this->authorization->require_permission(
            self::REQUIRED_PERMISSION
        );
    }

    /**
     * Display permission management.
     *
     * @return void
     */
    public function index()
    {
        $data['permissions'] =
            $this->Permission_model->get_all_permissions();

        $data['groups'] =
            $this->ion_auth_model
                ->groups()
                ->result();

        foreach ($data['groups'] as &$group) {

            $group->assigned_permissions =
                $this->Permission_model
                    ->get_permissions_by_group(
                        $group->id
                    );
        }

        unset($group);

        $this->template->render(
            'admin/permission_management',
            $data
        );
    }

    /**
     * Replace permissions assigned to a group.
     *
     * AJAX endpoint.
     *
     * @return void
     */
    public function save()
    {
        $this->output->set_content_type(
            'application/json'
        );

        try {

            $group_id =
                $this->input->post(
                    'group_id',
                    true
                );

            $permission_ids =
                $this->input->post(
                    'permission_ids',
                    true
                );

            if ($permission_ids === null) {
                $permission_ids = [];
            }

            if (!is_array($permission_ids)) {
                $permission_ids = [$permission_ids];
            }

            if (
                $group_id === null ||
                !is_numeric($group_id) ||
                (int) $group_id <= 0
            ) {
                throw new Exception(
                    'A valid group ID is required.'
                );
            }

            $group_id = (int) $group_id;

            /*
             * Normalize permission IDs.
             */
            $permission_ids = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $permission_ids
                        ),
                        function ($id) {
                            return $id > 0;
                        }
                    )
                )
            );

            $success =
                $this->Permission_model
                    ->update_group_permissions(
                        $group_id,
                        $permission_ids
                    );

            if (!$success) {
                throw new Exception(
                    'Failed to update group permissions.'
                );
            }

            /*
             * Permissions may have changed during this request.
             */
            $this->authorization->clear_cache();

            $this->output->set_status_header(200);

            echo json_encode([
                'success' => true,
                'message' =>
                    'Permissions updated successfully.',
                'data' => [
                    'group_id' =>
                        $group_id,
                    'permission_count' =>
                        count($permission_ids),
                ],
            ]);

        } catch (Exception $e) {

            log_message(
                'error',
                'Permission save error: ' .
                $e->getMessage()
            );

            $this->output->set_status_header(400);

            echo json_encode([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ]);
        }
    }
}