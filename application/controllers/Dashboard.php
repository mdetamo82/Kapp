<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard Controller
 *
 * Application landing page after authentication.
 *
 * This controller intentionally contains no business logic yet.
 * Dashboard statistics and widgets will be added later through
 * dedicated services/models.
 */
class Dashboard extends MY_controller
{
    public function __construct()
    {
        parent::__construct();

        /*
         * Central authorization service.
         */
        $this->load->library('authorization');
    }

    /**
     * Dashboard landing page.
     *
     * @return void
     */
    public function index()
    {
        /*
         * Authorization boundary.
         *
         * Menu visibility is not security.
         * The controller independently requires the permission.
         */
        $this->authorization->require_permission('view_dashboard');

        /*
         * Dashboard data.
         *
         * Keep this empty for now. Business-specific dashboard
         * statistics will be introduced later.
         */
        $data = [
            'page_title' => 'Dashboard',
        ];

        /*
         * Render through the centralized application template.
         */
        $this->load->library('template');
        $this->template->render('dashboard/index', $data);
    }
}
