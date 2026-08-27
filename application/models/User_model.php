<?php
class User_model extends CI_Model
{
    public function update_dark_mode($user_id, $dark_mode) {
        return $this->db->where('id', $user_id)
                       ->update('users', ['dark_mode' => $dark_mode]);
    }
    
    public function get_dark_mode($user_id) {
        $user = $this->db->select('dark_mode')
                        ->where('id', $user_id)
                        ->get('users')
                        ->row();
        return $user->dark_mode ?? 0;
    }
}
