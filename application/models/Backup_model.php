<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Backup_model extends CI_Model
{
    private $table = 'backup_logs';

    public function log_backup($file_name, $file_size, $type = 'Manual', $user_id = null)
    {
        $this->db->insert($this->table, [
            'file_name'  => $file_name,
            'file_size'  => $file_size,
            'type'       => $type,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function get_all()
    {
        return $this->db->order_by('created_at', 'DESC')->get($this->table)->result();
    }

    
}
