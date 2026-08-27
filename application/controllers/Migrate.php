<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    public function latest()
    {
        if (!is_cli()) {
            show_404();
        }

        $this->load->library('migration');

        if (!$this->migration->latest()) {
            echo "Migration failed:\n";
            echo $this->migration->error_string();
            exit(1);
        }

        echo "Migration completed successfully.\n";
    }

    public function status()
{
    if (!is_cli()) {
        show_404();
    }

    $this->config->load('migration');

    $this->load->library('migration');

    $row = $this->db
        ->select('version')
        ->get('migrations')
        ->row();

    $current = $row ? (int) $row->version : 0;
    $target  = (int) $this->config->item('migration_version');

    echo "Migration status\n";
    echo "----------------\n";
    echo "Database version : {$current}\n";
    echo "Target version   : {$target}\n";

    if ($current === $target) {
        echo "Status            : UP TO DATE\n";
    } elseif ($current < $target) {
        echo "Status            : PENDING\n";
    } else {
        echo "Status            : AHEAD OF CONFIGURATION\n";
    }
}
}