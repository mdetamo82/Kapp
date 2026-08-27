<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Auth
 *
 * Ion Auth authentication controller.
 *
 * Responsibilities:
 * - Login / logout
 * - Password change
 * - Forgot/reset password
 * - User activation/deactivation
 * - User CRUD
 * - Group CRUD
 *
 * Authorization remains handled by Ion Auth.
 *
 * DELIBERATELY extends CI_Controller, not MY_Controller/
 * Base_Controller — this controller must be reachable by
 * unauthenticated visitors (login, forgot-password, reset-
 * password), so it can't inherit anything that enforces auth
 * automatically.
 *
 * CSRF NOTE: several actions here use a hand-rolled one-time
 * nonce (_get_csrf_nonce/_valid_csrf_nonce) alongside whatever
 * CI3's own CSRF protection is configured to do
 * ($config['csrf_protection']). If that's already enabled
 * globally, this nonce is redundant defense-in-depth rather than
 * the only thing standing between a form and a forged request —
 * worth confirming which is actually true for this app rather
 * than assuming. Either way, it's now applied consistently across
 * every state-changing action in this controller rather than on
 * some and not others.
 *
 * @property Ion_auth|Ion_auth_model $ion_auth
 * @property CI_Form_validation      $form_validation
 */
class Auth extends CI_Controller
{
    /**
     * View data.
     *
     * @var array
     */
    public $data = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->database();

        $this->load->library([
            'ion_auth',
            'form_validation'
        ]);

        $this->load->helper([
            'url',
            'language'
        ]);

        $this->form_validation->set_error_delimiters(
            $this->config->item('error_start_delimiter', 'ion_auth'),
            $this->config->item('error_end_delimiter', 'ion_auth')
        );

        $this->lang->load('auth');
    }

    /**
     * Display users.
     */
    public function index()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login', 'refresh');
        }

        if (!$this->ion_auth->is_admin()) {
            show_error(
                'You must be an administrator to view this page.',
                403,
                'Access Denied'
            );
        }

        $this->data['title'] = $this->lang->line('index_heading');

        $this->data['message'] = validation_errors()
            ?: $this->session->flashdata('message');

        $users = $this->ion_auth->users()->result();

        foreach ($users as $key => $user) {
            $users[$key]->groups =
                $this->ion_auth->get_users_groups($user->id)->result();
        }

        $this->data['users'] = $users;

        $this->template->render('auth/index', $this->data);
    }

    /**
     * Login.
     */
    public function login()
    {
        /*
         * Authenticated users should never see the login page.
         * Send them to the application landing page.
         */
        if ($this->ion_auth->logged_in()) {
            redirect('/', 'refresh');
            return;
        }

        $this->data['title'] = $this->lang->line('login_heading');

        $this->form_validation->set_rules(
            'identity',
            str_replace(
                ':',
                '',
                $this->lang->line('login_identity_label')
            ),
            'required'
        );

        $this->form_validation->set_rules(
            'password',
            str_replace(
                ':',
                '',
                $this->lang->line('login_password_label')
            ),
            'required'
        );

        if ($this->form_validation->run() === TRUE) {

            $identity = trim($this->input->post('identity'));
            $password = $this->input->post('password');
            $remember = (bool) $this->input->post('remember');

            if ($this->ion_auth->login($identity, $password, $remember)) {

                $this->session->set_flashdata(
                    'message',
                    $this->ion_auth->messages()
                );

                redirect('/', 'refresh');
                return;
            }

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->errors()
            );

            redirect('auth/login', 'refresh');
            return;
        }

        $this->data['message'] = validation_errors()
            ?: $this->session->flashdata('message');

        $this->data['identity'] = [
            'name'  => 'identity',
            'id'    => 'identity',
            'type'  => 'text',
            'value' => $this->form_validation->set_value('identity'),
            'class' => 'form-control',
        ];

        $this->data['password'] = [
            'name'  => 'password',
            'id'    => 'password',
            'type'  => 'password',
            'class' => 'form-control',
        ];

        $this->_render_page(
            'auth' . DIRECTORY_SEPARATOR . 'login',
            $this->data
        );
    }

    /**
     * Logout.
     */
    public function logout()
    {
        $this->ion_auth->logout();

        redirect('auth/login', 'refresh');
    }

    /**
     * Change currently authenticated user's password.
     */
    public function change_password()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login', 'refresh');
        }

        $min_password_length =
            $this->config->item('min_password_length', 'ion_auth');

        $this->form_validation->set_rules(
            'old',
            $this->lang->line(
                'change_password_validation_old_password_label'
            ),
            'required'
        );

        $this->form_validation->set_rules(
            'new',
            $this->lang->line(
                'change_password_validation_new_password_label'
            ),
            'required|min_length[' . $min_password_length . ']|matches[new_confirm]'
        );

        $this->form_validation->set_rules(
            'new_confirm',
            $this->lang->line(
                'change_password_validation_new_password_confirm_label'
            ),
            'required'
        );

        $user = $this->ion_auth->user()->row();

        if ($this->form_validation->run() === FALSE) {

            $this->data['message'] = validation_errors()
                ?: $this->session->flashdata('message');

            $this->data['min_password_length'] = $min_password_length;

            $this->data['old_password'] = [
                'name' => 'old',
                'id'   => 'old',
                'type' => 'password',
            ];

            $this->data['new_password'] = [
                'name'    => 'new',
                'id'      => 'new',
                'type'    => 'password',
                'pattern' => '^.{' . $min_password_length . '}.*$',
            ];

            $this->data['new_password_confirm'] = [
                'name'    => 'new_confirm',
                'id'      => 'new_confirm',
                'type'    => 'password',
                'pattern' => '^.{' . $min_password_length . '}.*$',
            ];

            $this->data['user_id'] = [
                'name'  => 'user_id',
                'id'    => 'user_id',
                'type'  => 'hidden',
                'value' => $user->id,
            ];

            $this->_render_page(
                'auth' . DIRECTORY_SEPARATOR . 'change_password',
                $this->data
            );

            return;
        }

        $identity = $this->session->userdata('identity');

        $change = $this->ion_auth->change_password(
            $identity,
            $this->input->post('old'),
            $this->input->post('new')
        );

        if (!$change) {

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->errors()
            );

            redirect('auth/change_password', 'refresh');
        }

        $this->session->set_flashdata(
            'message',
            $this->ion_auth->messages()
        );

        $this->logout();
    }

    /**
     * Forgot password.
     */
    public function forgot_password()
    {
        $this->data['title'] =
            $this->lang->line('forgot_password_heading');

        $identity_column =
            $this->config->item('identity', 'ion_auth');

        if ($identity_column !== 'email') {

            $this->form_validation->set_rules(
                'identity',
                $this->lang->line(
                    'forgot_password_identity_label'
                ),
                'required'
            );

        } else {

            $this->form_validation->set_rules(
                'identity',
                $this->lang->line(
                    'forgot_password_validation_email_label'
                ),
                'required|valid_email'
            );
        }

        if ($this->form_validation->run() === FALSE) {

            $this->data['type'] = $identity_column;

            $this->data['identity'] = [
                'name' => 'identity',
                'id'   => 'identity',
            ];

            $this->data['identity_label'] =
                $identity_column !== 'email'
                    ? $this->lang->line(
                        'forgot_password_identity_label'
                    )
                    : $this->lang->line(
                        'forgot_password_email_identity_label'
                    );

            $this->data['message'] = validation_errors()
                ?: $this->session->flashdata('message');

            $this->_render_page(
                'auth' . DIRECTORY_SEPARATOR . 'forgot_password',
                $this->data
            );

            return;
        }

        $identity_value = trim(
            $this->input->post('identity')
        );

        $user = $this->ion_auth
            ->where($identity_column, $identity_value)
            ->users()
            ->row();

        if (!$user) {

            $error_key = $identity_column !== 'email'
                ? 'forgot_password_identity_not_found'
                : 'forgot_password_email_not_found';

            $this->ion_auth->set_error($error_key);

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->errors()
            );

            redirect('auth/forgot_password', 'refresh');
        }

        $forgotten = $this->ion_auth->forgotten_password(
            $user->{$identity_column}
        );

        if ($forgotten) {

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->messages()
            );

            redirect('auth/login', 'refresh');
        }

        $this->session->set_flashdata(
            'message',
            $this->ion_auth->errors()
        );

        redirect('auth/forgot_password', 'refresh');
    }

    /**
     * Reset forgotten password.
     *
     * @param string|null $code
     */
    public function reset_password($code = NULL)
    {
        if (!$code) {
            show_404();
        }

        $this->data['title'] =
            $this->lang->line('reset_password_heading');

        $user = $this->ion_auth->forgotten_password_check($code);

        if (!$user) {

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->errors()
            );

            redirect('auth/forgot_password', 'refresh');
        }

        $min_password_length =
            $this->config->item('min_password_length', 'ion_auth');

        $this->form_validation->set_rules(
            'new',
            $this->lang->line(
                'reset_password_validation_new_password_label'
            ),
            'required|min_length[' . $min_password_length . ']|matches[new_confirm]'
        );

        $this->form_validation->set_rules(
            'new_confirm',
            $this->lang->line(
                'reset_password_validation_new_password_confirm_label'
            ),
            'required'
        );

        if ($this->form_validation->run() === FALSE) {

            $this->data['message'] = validation_errors()
                ?: $this->session->flashdata('message');

            $this->data['min_password_length'] =
                $min_password_length;

            $this->data['new_password'] = [
                'name'    => 'new',
                'id'      => 'new',
                'type'    => 'password',
                'pattern' => '^.{' . $min_password_length . '}.*$',
            ];

            $this->data['new_password_confirm'] = [
                'name'    => 'new_confirm',
                'id'      => 'new_confirm',
                'type'    => 'password',
                'pattern' => '^.{' . $min_password_length . '}.*$',
            ];

            $this->data['user_id'] = [
                'name'  => 'user_id',
                'id'    => 'user_id',
                'type'  => 'hidden',
                'value' => $user->id,
            ];

            $this->data['csrf'] =
                $this->_get_csrf_nonce();

            $this->data['code'] = $code;

            $this->_render_page(
                'auth' . DIRECTORY_SEPARATOR . 'reset_password',
                $this->data
            );

            return;
        }

        $identity_column =
            $this->config->item('identity', 'ion_auth');

        $identity = $user->{$identity_column};

        if (
            !$this->_valid_csrf_nonce()
            || (int) $user->id !== (int) $this->input->post('user_id')
        ) {

            $this->ion_auth->clear_forgotten_password_code(
                $identity
            );

            show_error(
                $this->lang->line('error_csrf'),
                403,
                'Security Error'
            );
        }

        $change = $this->ion_auth->reset_password(
            $identity,
            $this->input->post('new')
        );

        if ($change) {

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->messages()
            );

            redirect('auth/login', 'refresh');
        }

        $this->session->set_flashdata(
            'message',
            $this->ion_auth->errors()
        );

        redirect(
            'auth/reset_password/' . $code,
            'refresh'
        );
    }

    /**
     * Activate user.
     *
     * @param int         $id
     * @param string|bool $code
     */
    public function activate($id, $code = FALSE)
    {
        $activation = FALSE;

        if ($code !== FALSE) {

            /*
             * Email-activation-link flow — deliberately public,
             * since a brand new user isn't logged in yet. Security
             * here rests entirely on the code itself being correct;
             * ion_auth->activate() validates it against the DB.
             */
            $activation = $this->ion_auth->activate(
                $id,
                $code
            );

        } elseif (
            $this->ion_auth->logged_in()
            && $this->ion_auth->is_admin()
        ) {

            /*
             * Manual admin activation flow (no code) — explicit
             * logged_in() check added alongside is_admin() for
             * consistency with every other admin-gated action in
             * this controller, even though is_admin() likely
             * already returns false for an anonymous session.
             */
            $activation = $this->ion_auth->activate($id);
        }

        if ($activation) {

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->messages()
            );

            redirect('auth', 'refresh');
        }

        $this->session->set_flashdata(
            'message',
            $this->ion_auth->errors()
        );

        redirect('auth/forgot_password', 'refresh');
    }

    /**
     * Deactivate user.
     *
     * @param int|string|null $id
     */
    public function deactivate($id = NULL)
    {
        if (
            !$this->ion_auth->logged_in()
            || !$this->ion_auth->is_admin()
        ) {
            show_error(
                'You must be an administrator to perform this action.',
                403,
                'Access Denied'
            );
        }

        $id = (int) $id;

        if ($id <= 0) {
            show_404();
        }

        $this->form_validation->set_rules(
            'confirm',
            $this->lang->line(
                'deactivate_validation_confirm_label'
            ),
            'required'
        );

        $this->form_validation->set_rules(
            'id',
            $this->lang->line(
                'deactivate_validation_user_id_label'
            ),
            'required|alpha_numeric'
        );

        if ($this->form_validation->run() === FALSE) {

            $this->data['csrf'] =
                $this->_get_csrf_nonce();

            $this->data['user'] =
                $this->ion_auth->user($id)->row();

            if (!$this->data['user']) {
                show_404();
            }

            $this->data['identity'] =
                $this->config->item('identity', 'ion_auth');

            $this->template->render(
                'auth/deactivate_user',
                $this->data
            );

            return;
        }

        if ($this->input->post('confirm') !== 'yes') {
            redirect('auth', 'refresh');
        }

        if (
            !$this->_valid_csrf_nonce()
            || (int) $this->input->post('id') !== $id
        ) {
            show_error(
                $this->lang->line('error_csrf'),
                403,
                'Security Error'
            );
        }

        /*
         * FIX: the previous version flashed messages() here
         * unconditionally, regardless of whether deactivate()
         * actually succeeded — meaning a failed deactivation
         * still told the admin it worked. Every other
         * success/failure action in this controller (activate,
         * reset_password, create_group, edit_group) branches on
         * the actual result; this one now does too.
         */
        $deactivated = $this->ion_auth->deactivate($id);

        $this->session->set_flashdata(
            'message',
            $deactivated
                ? $this->ion_auth->messages()
                : $this->ion_auth->errors()
        );

        redirect('auth', 'refresh');
    }

    /**
     * Create a new user.
     */
    public function create_user()
    {
        if (
            !$this->ion_auth->logged_in()
            || !$this->ion_auth->is_admin()
        ) {
            redirect('auth', 'refresh');
        }

        $this->data['title'] =
            $this->lang->line('create_user_heading');

        $tables =
            $this->config->item('tables', 'ion_auth');

        $identity_column =
            $this->config->item('identity', 'ion_auth');

        $this->data['identity_column'] =
            $identity_column;

        $this->form_validation->set_rules(
            'first_name',
            $this->lang->line(
                'create_user_validation_fname_label'
            ),
            'trim|required'
        );

        $this->form_validation->set_rules(
            'last_name',
            $this->lang->line(
                'create_user_validation_lname_label'
            ),
            'trim|required'
        );

        if ($identity_column !== 'email') {

            $this->form_validation->set_rules(
                'identity',
                $this->lang->line(
                    'create_user_validation_identity_label'
                ),
                'trim|required|is_unique[' .
                $tables['users'] . '.' .
                $identity_column . ']'
            );

            $this->form_validation->set_rules(
                'email',
                $this->lang->line(
                    'create_user_validation_email_label'
                ),
                'trim|required|valid_email'
            );

        } else {

            $this->form_validation->set_rules(
                'email',
                $this->lang->line(
                    'create_user_validation_email_label'
                ),
                'trim|required|valid_email|is_unique[' .
                $tables['users'] . '.email]'
            );
        }

        $this->form_validation->set_rules(
            'phone',
            $this->lang->line(
                'create_user_validation_phone_label'
            ),
            'trim'
        );

        $this->form_validation->set_rules(
            'company',
            $this->lang->line(
                'create_user_validation_company_label'
            ),
            'trim'
        );

        $this->form_validation->set_rules(
            'password',
            $this->lang->line(
                'create_user_validation_password_label'
            ),
            'required|min_length[' .
            $this->config->item(
                'min_password_length',
                'ion_auth'
            ) .
            ']|matches[password_confirm]'
        );

        $this->form_validation->set_rules(
            'password_confirm',
            $this->lang->line(
                'create_user_validation_password_confirm_label'
            ),
            'required'
        );

        if (
            $this->form_validation->run() === TRUE
            && !$this->_valid_csrf_nonce()
        ) {
            show_error(
                $this->lang->line('error_csrf'),
                403,
                'Security Error'
            );
        }

        if ($this->form_validation->run() === TRUE) {

            $email = strtolower(
                trim($this->input->post('email'))
            );

            $identity = $identity_column === 'email'
                ? $email
                : trim($this->input->post('identity'));

            $password = $this->input->post('password');

            $additional_data = [
                'first_name' => trim(
                    $this->input->post('first_name')
                ),
                'last_name' => trim(
                    $this->input->post('last_name')
                ),
                'company' => trim(
                    $this->input->post('company')
                ),
                'phone' => trim(
                    $this->input->post('phone')
                ),
            ];

            $user_id = $this->ion_auth->register(
                $identity,
                $password,
                $email,
                $additional_data
            );

            if ($user_id) {

                $this->session->set_flashdata(
                    'message',
                    $this->ion_auth->messages()
                );

                redirect('auth', 'refresh');
            }
        }

        $this->data['csrf'] =
            $this->_get_csrf_nonce();

        $this->data['message'] =
            validation_errors()
            ?: (
                $this->ion_auth->errors()
                ?: $this->session->flashdata('message')
            );

        $this->data['first_name'] = [
            'name'  => 'first_name',
            'id'    => 'first_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'first_name'
            ),
        ];

        $this->data['last_name'] = [
            'name'  => 'last_name',
            'id'    => 'last_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'last_name'
            ),
        ];

        $this->data['identity'] = [
            'name'  => 'identity',
            'id'    => 'identity',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'identity'
            ),
        ];

        $this->data['email'] = [
            'name'  => 'email',
            'id'    => 'email',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'email'
            ),
        ];

        $this->data['company'] = [
            'name'  => 'company',
            'id'    => 'company',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'company'
            ),
        ];

        $this->data['phone'] = [
            'name'  => 'phone',
            'id'    => 'phone',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'phone'
            ),
        ];

        $this->data['password'] = [
            'name' => 'password',
            'id'   => 'password',
            'type' => 'password',
        ];

        $this->data['password_confirm'] = [
            'name' => 'password_confirm',
            'id'   => 'password_confirm',
            'type' => 'password',
        ];

        $this->template->render(
            'auth/create_user',
            $this->data
        );
    }

    /**
     * Redirect user according to privilege.
     */
    public function redirectUser()
    {
        if ($this->ion_auth->is_admin()) {
            redirect('auth', 'refresh');
        }

        redirect('/', 'refresh');
    }

    /**
     * Edit a user.
     *
     * @param int|string $id
     */
    public function edit_user($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            show_404();
        }

        if (!$this->ion_auth->logged_in()) {
            redirect('auth', 'refresh');
        }

        $current_user = $this->ion_auth->user()->row();

        if (
            !$this->ion_auth->is_admin()
            && (!$current_user || (int) $current_user->id !== $id)
        ) {
            redirect('auth', 'refresh');
        }

        $user = $this->ion_auth->user($id)->row();

        if (!$user) {
            show_404();
        }

        $this->data['title'] =
            $this->lang->line('edit_user_heading');

        $groups =
            $this->ion_auth->groups()->result_array();

        $currentGroups =
            $this->ion_auth
                ->get_users_groups($id)
                ->result_array();

        $this->form_validation->set_rules(
            'first_name',
            $this->lang->line(
                'edit_user_validation_fname_label'
            ),
            'trim|required'
        );

        $this->form_validation->set_rules(
            'last_name',
            $this->lang->line(
                'edit_user_validation_lname_label'
            ),
            'trim|required'
        );

        $this->form_validation->set_rules(
            'phone',
            $this->lang->line(
                'edit_user_validation_phone_label'
            ),
            'trim'
        );

        $this->form_validation->set_rules(
            'company',
            $this->lang->line(
                'edit_user_validation_company_label'
            ),
            'trim'
        );

        if ($this->input->method() === 'post') {

            if (
                !$this->_valid_csrf_nonce()
                || (int) $this->input->post('id') !== $id
            ) {
                show_error(
                    $this->lang->line('error_csrf'),
                    403,
                    'Security Error'
                );
            }

            if ($this->input->post('password')) {

                $this->form_validation->set_rules(
                    'password',
                    $this->lang->line(
                        'edit_user_validation_password_label'
                    ),
                    'required|min_length[' .
                    $this->config->item(
                        'min_password_length',
                        'ion_auth'
                    ) .
                    ']|matches[password_confirm]'
                );

                $this->form_validation->set_rules(
                    'password_confirm',
                    $this->lang->line(
                        'edit_user_validation_password_confirm_label'
                    ),
                    'required'
                );
            }

            if ($this->form_validation->run() === TRUE) {

                $data = [
                    'first_name' => trim(
                        $this->input->post('first_name')
                    ),
                    'last_name' => trim(
                        $this->input->post('last_name')
                    ),
                    'company' => trim(
                        $this->input->post('company')
                    ),
                    'phone' => trim(
                        $this->input->post('phone')
                    ),
                ];

                if ($this->input->post('password')) {
                    $data['password'] =
                        $this->input->post('password');
                }

                /*
                 * Only administrators may modify groups.
                 */
                if ($this->ion_auth->is_admin()) {

                    $this->ion_auth->remove_from_group(
                        '',
                        $id
                    );

                    $group_data =
                        $this->input->post('groups');

                    if (
                        is_array($group_data)
                        && !empty($group_data)
                    ) {
                        foreach ($group_data as $group_id) {

                            $this->ion_auth->add_to_group(
                                (int) $group_id,
                                $id
                            );
                        }
                    }
                }

                /*
                 * FIX: previously relied on redirectUser()'s
                 * internal redirect() call exiting mid-if to stop
                 * the failure branch below from also running. That
                 * works, but only because redirect() happens to
                 * call exit() — a fragile, implicit dependency.
                 * Made explicit with if/else so this can't silently
                 * break if redirectUser() is ever changed to not
                 * exit (e.g. for a future AJAX response path).
                 */
                if ($this->ion_auth->update($user->id, $data)) {

                    $this->session->set_flashdata(
                        'message',
                        'User updated successfully.'
                    );

                } else {

                    $this->session->set_flashdata(
                        'message',
                        $this->ion_auth->errors()
                    );
                }

                $this->redirectUser();
            }
        }

        $this->data['csrf'] =
            $this->_get_csrf_nonce();

        $this->data['message'] =
            validation_errors()
            ?: (
                $this->ion_auth->errors()
                ?: $this->session->flashdata('message')
            );

        $this->data['user'] = $user;
        $this->data['groups'] = $groups;
        $this->data['currentGroups'] = $currentGroups;

        $this->data['first_name'] = [
            'name'  => 'first_name',
            'id'    => 'first_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'first_name',
                $user->first_name
            ),
        ];

        $this->data['last_name'] = [
            'name'  => 'last_name',
            'id'    => 'last_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'last_name',
                $user->last_name
            ),
        ];

        $this->data['company'] = [
            'name'  => 'company',
            'id'    => 'company',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'company',
                $user->company
            ),
        ];

        $this->data['phone'] = [
            'name'  => 'phone',
            'id'    => 'phone',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'phone',
                $user->phone
            ),
        ];

        $this->data['password'] = [
            'name' => 'password',
            'id'   => 'password',
            'type' => 'password',
        ];

        $this->data['password_confirm'] = [
            'name' => 'password_confirm',
            'id'   => 'password_confirm',
            'type' => 'password',
        ];

        $this->template->render(
            'auth/edit_user',
            $this->data
        );
    }

    /**
     * Create a group.
     */
    public function create_group()
    {
        if (
            !$this->ion_auth->logged_in()
            || !$this->ion_auth->is_admin()
        ) {
            redirect('auth', 'refresh');
        }

        $this->data['title'] =
            $this->lang->line('create_group_title');

        $this->form_validation->set_rules(
            'group_name',
            $this->lang->line(
                'create_group_validation_name_label'
            ),
            'trim|required|alpha_dash'
        );

        if ($this->form_validation->run() === TRUE) {

            if (!$this->_valid_csrf_nonce()) {
                show_error(
                    $this->lang->line('error_csrf'),
                    403,
                    'Security Error'
                );
            }

            $group_id = $this->ion_auth->create_group(
                trim($this->input->post('group_name')),
                trim($this->input->post('description'))
            );

            if ($group_id) {

                $this->session->set_flashdata(
                    'message',
                    $this->ion_auth->messages()
                );

                redirect('auth', 'refresh');
            }

            $this->session->set_flashdata(
                'message',
                $this->ion_auth->errors()
            );
        }

        $this->data['csrf'] =
            $this->_get_csrf_nonce();

        $this->data['message'] =
            validation_errors()
            ?: (
                $this->ion_auth->errors()
                ?: $this->session->flashdata('message')
            );

        $this->data['group_name'] = [
            'name'  => 'group_name',
            'id'    => 'group_name',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'group_name'
            ),
        ];

        $this->data['description'] = [
            'name'  => 'description',
            'id'    => 'description',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'description'
            ),
        ];

        $this->template->render(
            'auth/create_group',
            $this->data
        );
    }

    /**
     * Edit a group.
     *
     * @param int|string $id
     */
    public function edit_group($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            redirect('auth', 'refresh');
        }

        if (
            !$this->ion_auth->logged_in()
            || !$this->ion_auth->is_admin()
        ) {
            redirect('auth', 'refresh');
        }

        $group = $this->ion_auth->group($id)->row();

        if (!$group) {
            show_404();
        }

        $this->data['title'] =
            $this->lang->line('edit_group_title');

        $this->form_validation->set_rules(
            'group_name',
            $this->lang->line(
                'edit_group_validation_name_label'
            ),
            'trim|required|alpha_dash'
        );

        if ($this->input->method() === 'post') {

            if (!$this->_valid_csrf_nonce()) {
                show_error(
                    $this->lang->line('error_csrf'),
                    403,
                    'Security Error'
                );
            }

            if ($this->form_validation->run() === TRUE) {

                $group_update =
                    $this->ion_auth->update_group(
                        $id,
                        trim(
                            $this->input->post('group_name')
                        ),
                        [
                            'description' => trim(
                                $this->input->post(
                                    'group_description'
                                )
                            )
                        ]
                    );

                if ($group_update) {

                    $this->session->set_flashdata(
                        'message',
                        $this->lang->line(
                            'edit_group_saved'
                        )
                    );

                    redirect('auth', 'refresh');
                }

                $this->session->set_flashdata(
                    'message',
                    $this->ion_auth->errors()
                );
            }
        }

        $this->data['csrf'] =
            $this->_get_csrf_nonce();

        $this->data['message'] =
            validation_errors()
            ?: (
                $this->ion_auth->errors()
                ?: $this->session->flashdata('message')
            );

        $this->data['group'] = $group;

        $this->data['group_name'] = [
            'name'    => 'group_name',
            'id'      => 'group_name',
            'type'    => 'text',
            'value'   => $this->form_validation->set_value(
                'group_name',
                $group->name
            ),
        ];

        /*
         * Never allow the administrator group to be renamed.
         */
        if (
            $this->config->item(
                'admin_group',
                'ion_auth'
            ) === $group->name
        ) {
            $this->data['group_name']['readonly'] =
                'readonly';
        }

        $this->data['group_description'] = [
            'name'  => 'group_description',
            'id'    => 'group_description',
            'type'  => 'text',
            'value' => $this->form_validation->set_value(
                'group_description',
                $group->description
            ),
        ];

        $this->template->render(
            'auth/edit_group',
            $this->data
        );
    }

    /**
     * Generate a one-time Ion Auth CSRF nonce.
     *
     * @return array
     */
    public function _get_csrf_nonce()
    {
        $this->load->helper('string');

        $key = random_string('alnum', 32);
        $value = random_string('alnum', 64);

        $this->session->set_flashdata(
            'csrfkey',
            $key
        );

        $this->session->set_flashdata(
            'csrfvalue',
            $value
        );

        return [
            $key => $value
        ];
    }

    /**
     * Validate Ion Auth CSRF nonce.
     *
     * @return bool
     */
    public function _valid_csrf_nonce()
    {
        $csrf_key =
            $this->session->flashdata('csrfkey');

        $csrf_value =
            $this->session->flashdata('csrfvalue');

        if (!$csrf_key || !$csrf_value) {
            return FALSE;
        }

        $posted_value =
            $this->input->post($csrf_key);

        if (
            $posted_value
            && hash_equals(
                (string) $csrf_value,
                (string) $posted_value
            )
        ) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Render an application view.
     *
     * @param string     $view
     * @param array|null $data
     * @param bool       $returnhtml
     *
     * @return mixed
     */
    public function _render_page(
        $view,
        $data = NULL,
        $returnhtml = FALSE
    ) {
        $viewdata =
            empty($data)
                ? $this->data
                : $data;

        $view_html = $this->load->view(
            $view,
            $viewdata,
            $returnhtml
        );

        if ($returnhtml) {
            return $view_html;
        }

        return NULL;
    }
}
