<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permission_model extends CI_Model
{
    /**
     * Get all available permissions.
     */
    public function get_all_permissions()
    {
        return $this->db
            ->order_by('controller', 'ASC')
            ->order_by('id', 'ASC')
            ->get('permissions')
            ->result();
    }

    /**
     * Get permission IDs assigned to a group.
     */
    public function get_permissions_by_group($group_id)
    {
        $query = $this->db
            ->select('permission_id')
            ->where('group_id', $group_id)
            ->get('group_permissions');

        return array_column($query->result_array(), 'permission_id');
    }

    /**
     * Replace all permissions assigned to a group.
     */
    public function update_group_permissions($group_id, $permission_ids = [])
    {
        $this->db->trans_start();

        $this->db
            ->where('group_id', $group_id)
            ->delete('group_permissions');

        if (!empty($permission_ids)) {
            $insert_data = [];

            foreach ($permission_ids as $permission_id) {
                $insert_data[] = [
                    'group_id'      => (int) $group_id,
                    'permission_id' => (int) $permission_id
                ];
            }

            $this->db->insert_batch(
                'group_permissions',
                $insert_data
            );
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
