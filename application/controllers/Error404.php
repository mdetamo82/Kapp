<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Error 404 Controller
 *
 * Displays the application's custom 404 page.
 *
 * This controller intentionally has no authorization boundary.
 * Error pages must remain accessible regardless of user permissions.
 */
class Error404 extends CI_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('template');
    }

    /**
     * Display the 404 page.
     *
     * @return void
     */
    public function index()
    {
        $this->output->set_status_header(404);

        $data = [
            'title' => '404 Page Not Found',
        ];

        $this->template->render(
            'errors/html/error_404',
            $data
        );
    }
}
