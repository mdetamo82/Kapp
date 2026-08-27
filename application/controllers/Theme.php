<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Theme Controller
 *
 * Handles authenticated user's personal theme preferences.
 *
 * Authorization model:
 * - Authentication is required.
 * - No RBAC permission is required because theme preference
 *   belongs to the currently authenticated user.
 */
class Theme extends MY_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library([
            'ion_auth',
            'ajax_response',
        ]);

        $this->load->model('User_model');
    }

    /**
     * Toggle the authenticated user's dark-mode preference.
     *
     * This is a state-changing operation and therefore requires
     * a POST request.
     *
     * @return void
     */
    public function toggle_dark_mode()
    {
        if (!$this->ion_auth->logged_in()) {
            if ($this->ajax_response->is_ajax()) {
                return $this->ajax_response->error(
                    'Authentication required.',
                    401
                );
            }

            redirect('auth/login');

            return;
        }

        if ($this->input->method() !== 'post') {
            if ($this->ajax_response->is_ajax()) {
                return $this->ajax_response->error(
                    'Method Not Allowed.',
                    405
                );
            }

            show_error(
                'Method Not Allowed.',
                405,
                'Method Not Allowed'
            );

            return;
        }

        $user_id = (int) $this->ion_auth->get_user_id();

        if ($user_id <= 0) {
            if ($this->ajax_response->is_ajax()) {
                return $this->ajax_response->error(
                    'Invalid authenticated user.',
                    401
                );
            }

            redirect('auth/login');

            return;
        }

        $current_mode =
            (int) $this->User_model->get_dark_mode($user_id);

        $new_mode = $current_mode === 1 ? 0 : 1;

        if (
            !$this->User_model->update_dark_mode(
                $user_id,
                $new_mode
            )
        ) {
            if ($this->ajax_response->is_ajax()) {
                return $this->ajax_response->error(
                    'Unable to update theme preference.',
                    500
                );
            }

            $this->session->set_flashdata(
                'error',
                'Unable to update theme preference.'
            );

            redirect('dashboard');

            return;
        }

        if ($this->ajax_response->is_ajax()) {
            return $this->ajax_response->success(
                'Theme preference updated successfully.',
                [
                    'dark_mode' => $new_mode,
                ]
            );
        }

        $this->session->set_flashdata(
            'success',
            'Theme preference updated successfully.'
        );

        /*
         * Do not trust an arbitrary external HTTP Referer.
         * Return to the dashboard instead.
         */
        redirect('dashboard');
    }
}
