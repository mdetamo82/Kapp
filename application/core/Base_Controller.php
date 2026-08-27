<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base Controller
 *
 * Enterprise application foundation for CodeIgniter 3.
 *
 * This class carries every shared helper — libraries, request
 * guards, input normalization, validation, transactions, audit,
 * AJAX/HTML responses, CRUD helpers, view rendering — but
 * enforces NO authentication requirement of its own.
 *
 * Two things extend this class:
 *
 * - MY_Controller adds exactly one thing on top: a mandatory
 *   authentication check in its constructor. Every
 *   authenticated application controller (Customer, Supplier,
 *   Invoice, ...) extends MY_Controller, never Base_Controller
 *   directly.
 *
 * - Genuinely public controllers (Auth — login, logout,
 *   forgot-password) extend Base_Controller directly, so they
 *   still get every convenience method here without duplicating
 *   any of it, but without ever being able to accidentally
 *   require authentication before a user has logged in.
 *
 * This split exists specifically so "does this controller
 * require login" is a structural fact (which class it extends),
 * not a boolean flag that could be left on/off by accident on
 * the wrong controller. If you're deciding which class to
 * extend and you're not building the login flow itself, extend
 * MY_Controller.
 *
 * Business rules remain inside individual controllers/models.
 */
class Base_Controller extends CI_Controller
{
    /**
     * CodeIgniter application instance.
     *
     * @var CI_Controller
     */
    protected $CI;


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->CI =& get_instance();

        // ----------------------------------------------------------
        // Common application libraries
        // ----------------------------------------------------------

        $this->load->library([
            'authorization',
            'template',
            'ajax_response',
            'audit_log',
            'form_validation',
        ]);

        // ----------------------------------------------------------
        // Common application helpers
        // ----------------------------------------------------------

        $this->load->helper([
            'url',
            'form',
            'security',
            'permission',
        ]);
    }


    /* ==============================================================
     * AUTHENTICATION
     *
     * These are just capability checks — none of them ENFORCE
     * anything by themselves. Enforcement (require_authentication
     * running automatically) lives one layer up, in
     * MY_Controller. Base_Controller only offers the primitives.
     * ============================================================== */

    /**
     * Determine whether the current request is authenticated.
     *
     * @return bool
     */
    protected function is_authenticated()
    {
        return $this->authorization->authenticated();
    }


    /**
     * Require an authenticated user.
     *
     * Available here so a public controller can still gate an
     * individual action (e.g. Auth::logout() should require a
     * logged-in user even though Auth as a whole is public).
     *
     * @return void
     */
    protected function require_authentication()
    {
        if ($this->is_authenticated()) {
            return;
        }

        if ($this->is_ajax()) {
            $this->ajax_response->unauthorized();
            exit;
        }

        redirect('auth/login');
        exit;
    }


    /**
     * Get current authenticated user ID.
     *
     * @return int|null
     */
    protected function current_user_id()
    {
        return $this->authorization->user_id();
    }


    /**
     * Determine whether current user is administrator.
     *
     * @return bool
     */
    protected function is_admin()
    {
        return $this->authorization->is_admin();
    }


    /* ==============================================================
     * AUTHORIZATION
     * ============================================================== */

    /**
     * Check whether current user has a permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    protected function can($permission)
    {
        return $this->authorization->can($permission);
    }


    /**
     * Require a permission.
     *
     * @param string $permission
     *
     * @return void
     */
    protected function require_permission($permission)
    {
        $this->authorization->require_permission($permission);
    }


    /* ==============================================================
     * REQUEST METHOD
     * ============================================================== */

    /**
     * Determine whether request is POST.
     *
     * @return bool
     */
    protected function is_post()
    {
        return $this->input->method() === 'post';
    }


    /**
     * Determine whether request is GET.
     *
     * @return bool
     */
    protected function is_get()
    {
        return $this->input->method() === 'get';
    }


    /**
     * Require POST request.
     *
     * @return void
     */
    protected function require_post()
    {
        if ($this->is_post()) {
            return;
        }

        if ($this->is_ajax()) {
            $this->ajax_response->method_not_allowed();
            exit;
        }

        show_error(
            'The requested HTTP method is not allowed.',
            405,
            'Method Not Allowed'
        );
    }


    /* ==============================================================
     * INPUT NORMALIZATION
     * ============================================================== */

    /**
     * Normalize a string value.
     *
     * Empty strings become null.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    protected function input_string($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }


    /**
     * Get sanitized POST string.
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function post_string($key)
    {
        return $this->input_string(
            $this->input->post($key, true)
        );
    }


    /**
     * Get sanitized GET string.
     *
     * @param string $key
     *
     * @return string|null
     */
    protected function get_string($key)
    {
        return $this->input_string(
            $this->input->get($key, true)
        );
    }


    /**
     * Collect normalized POST strings.
     *
     * Example:
     *
     * $data = $this->post_strings([
     *     'name',
     *     'description',
     * ]);
     *
     * @param array $fields
     *
     * @return array
     */
    protected function post_strings($fields)
    {
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = $this->post_string($field);
        }

        return $data;
    }


    /**
     * Resolve a positive integer ID.
     *
     * @param mixed $id
     *
     * @return int
     */
    protected function resolve_id($id)
    {
        if (
            !is_numeric($id)
            || (int) $id <= 0
            || (string) (int) $id !== (string) $id
        ) {
            $this->error_response(
                'Invalid ID.',
                400
            );

            exit;
        }

        return (int) $id;
    }


    /* ==============================================================
     * VALIDATION
     * ============================================================== */

    /**
     * Add a validation rule.
     *
     * @param string $field
     * @param string $label
     * @param string $rules
     *
     * @return void
     */
    protected function add_validation_rule(
        $field,
        $label,
        $rules
    ) {
        $this->form_validation->set_rules(
            $field,
            $label,
            $rules
        );
    }


    /**
     * Determine whether validation succeeded.
     *
     * @return bool
     */
    protected function validate()
    {
        return $this->form_validation->run() === true;
    }


    /**
     * Validate request and return selected field errors.
     *
     * This combines the two repeated controller operations:
     *
     *     if (!$this->validate()) {
     *         return $this->validation_error_response(...);
     *     }
     *
     * If a $view is supplied, a failed validation re-renders that
     * view in the SAME request instead of redirecting — a
     * redirect starts a brand new GET request, which throws away
     * $_POST and the form_validation state that form_error()/
     * set_value()/validation_errors() depend on, leaving the
     * re-rendered form blank with no field errors and no
     * repopulated input.
     *
     * @param array $fields
     * @param string $message
     * @param string|null $redirect
     * @param string|null $view
     * @param array $data
     *
     * @return bool
     */
    protected function validate_request(
        $fields = [],
        $message = 'Validation failed.',
        $redirect = null,
        $view = null,
        $data = []
    ) {
        if ($this->validate()) {
            return true;
        }

        $this->validation_error_response(
            $this->validation_errors($fields),
            $message,
            $redirect,
            $view,
            $data
        );

        return false;
    }


    /**
     * Return validation errors.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function validation_errors($fields = [])
    {
        $errors = [];

        foreach ($fields as $field) {
            $errors[$field] = form_error($field);
        }

        return $errors;
    }


    /**
     * Set standard validation error delimiters.
     *
     * @return void
     */
    protected function set_validation_error_delimiters()
    {
        $this->form_validation->set_error_delimiters(
            '<div class="text-danger small">',
            '</div>'
        );
    }


    /* ==============================================================
     * REUSABLE CRUD HELPERS
     *
     * Extracted from the Customer module (the canonical reference
     * controller) so every future model — Supplier, Investor,
     * Invoice, etc. — can reuse the same boilerplate instead of
     * re-typing it per controller.
     * ============================================================== */

    /**
     * Assert a field is unique, and send a validation error
     * response (re-rendering $view in the same request) if it
     * is not.
     *
     * Wraps the repeated pattern of:
     *
     *     if ($this->model->x_exists($value)) {
     *         $this->validation_error_response([...], ..., ..., $view, $data);
     *         return;
     *     }
     *
     * Usage:
     *
     *     if (!$this->assert_unique(
     *         $this->customer->name_exists($name),
     *         'name',
     *         'A customer with this name already exists.',
     *         'customer/create',
     *         ['page_title' => 'Create Customer']
     *     )) {
     *         return;
     *     }
     *
     * @param bool $exists Result of the model's *_exists() check.
     * @param string $field Field name the error belongs to.
     * @param string $message Human-readable error message.
     * @param string|null $view View to re-render on failure.
     * @param array $data Data for that view.
     *
     * @return bool True when unique (no conflict). False when a
     *              response has already been sent and the caller
     *              should return immediately.
     */
    protected function assert_unique(
        $exists,
        $field,
        $message,
        $view = null,
        $data = []
    ) {
        if (!$exists) {
            return true;
        }

        $this->validation_error_response(
            [
                $field =>
                    '<div class="text-danger small">'
                    . $message
                    . '</div>',
            ],
            $message,
            null,
            $view,
            $data
        );

        return false;
    }


    /**
     * Resolve an ID, look up a record via the given model
     * method, and send a 404-style response if it is missing.
     *
     * Wraps the repeated pattern of:
     *
     *     $id = $this->resolve_id($id);
     *     $record = $this->model->find($id);
     *     if (!$record) {
     *         $this->not_found('...');
     *         return;
     *     }
     *
     * Usage:
     *
     *     $customer = $this->find_or_404(
     *         $this->customer,
     *         'find',
     *         $id,
     *         'Customer record not found.'
     *     );
     *
     *     if ($customer === null) {
     *         return;
     *     }
     *
     * @param object $model Model instance (e.g. $this->customer).
     * @param string $method Lookup method on the model (find, find_any, find_deleted, ...).
     * @param mixed $id Raw ID from the route.
     * @param string $message Not-found message.
     *
     * @return object|null The record, or null if a not-found
     *                      response has already been sent.
     */
    protected function find_or_404(
        $model,
        $method,
        $id,
        $message = 'Record not found.'
    ) {
        $resolved_id = $this->resolve_id($id);

        $record = $model->$method($resolved_id);

        if (!$record) {
            $this->not_found($message);
            return null;
        }

        return $record;
    }


    /**
     * Run a mutation inside a DB transaction, with automatic
     * rollback + error response on failure.
     *
     * Wraps the repeated pattern of:
     *
     *     $this->transaction_begin();
     *     $result = $this->model->insert($data);
     *     if ($result === false || !$this->transaction_ok()) {
     *         $this->transaction_rollback();
     *         $this->error_response(...);
     *         return;
     *     }
     *     ...audit...
     *     if (!$this->transaction_ok()) {
     *         $this->transaction_rollback();
     *         $this->error_response(...);
     *         return;
     *     }
     *     $this->transaction_commit();
     *
     * The $mutate callback should return the mutation result
     * (e.g. an insert ID, or true/false for update/delete). The
     * $audit callback, if given, runs INSIDE the same
     * transaction and receives the $mutate result; if it throws
     * or the audit_log call fails the transaction is still
     * rolled back like any other step.
     *
     * Usage:
     *
     *     $customer_id = $this->run_transactional(
     *         function () use ($data) {
     *             return $this->customer->insert($data);
     *         },
     *         function ($result) use ($data) {
     *             $this->audit('customer', 'create', $result, null, $data);
     *         },
     *         'Unable to create the customer.',
     *         'customer/create'
     *     );
     *
     *     if ($customer_id === false) {
     *         return;
     *     }
     *
     * @param callable $mutate Performs the DB write, returns the result.
     * @param callable|null $audit Runs after a successful mutate; receives the mutate result.
     * @param string $error_message Message sent on failure.
     * @param string|null $redirect Redirect target for the non-AJAX error response.
     *
     * @return mixed The $mutate result on success, or false if a
     *               response has already been sent and the
     *               caller should return immediately. Because
     *               false is also a valid "mutation failed"
     *               result, always compare with === false.
     */
    protected function run_transactional(
        callable $mutate,
        callable $audit = null,
        $error_message = 'Operation failed.',
        $redirect = null
    ) {
        $this->transaction_begin();

        $result = $mutate();

        if (
            $result === false
            || !$this->transaction_ok()
        ) {
            $this->transaction_rollback();

            $this->error_response(
                $error_message,
                500,
                [],
                $redirect
            );

            return false;
        }

        if ($audit !== null) {
            $audit($result);

            if (!$this->transaction_ok()) {
                $this->transaction_rollback();

                $this->error_response(
                    $error_message,
                    500,
                    [],
                    $redirect
                );

                return false;
            }
        }

        $this->transaction_commit();

        return $result;
    }


    /* ==============================================================
     * DATABASE TRANSACTIONS
     * ============================================================== */

    /**
     * Begin database transaction.
     *
     * @return void
     */
    protected function transaction_begin()
    {
        $this->db->trans_begin();
    }


    /**
     * Determine transaction state.
     *
     * @return bool
     */
    protected function transaction_ok()
    {
        return $this->db->trans_status() !== false;
    }


    /**
     * Commit transaction.
     *
     * @return void
     */
    protected function transaction_commit()
    {
        $this->db->trans_commit();
    }


    /**
     * Rollback transaction.
     *
     * @return void
     */
    protected function transaction_rollback()
    {
        $this->db->trans_rollback();
    }


    /**
     * Rollback transaction and return an error response.
     *
     * @param string $message
     * @param int $status
     * @param array $data
     * @param string|null $redirect
     *
     * @return void
     */
    protected function transaction_error(
        $message,
        $status = 500,
        $data = [],
        $redirect = null
    ) {
        $this->transaction_rollback();

        return $this->error_response(
            $message,
            $status,
            $data,
            $redirect
        );
    }


    /* ==============================================================
     * AUDIT
     * ============================================================== */

    /**
     * Write an audit event.
     *
     * Business controllers provide old/new values.
     *
     * @param string $module
     * @param string $action
     * @param int $record_id
     * @param mixed $old_values
     * @param mixed $new_values
     *
     * @return int|false
     */
    protected function audit(
        $module,
        $action,
        $record_id,
        $old_values = null,
        $new_values = null
    ) {
        return $this->audit_log->log(
            $module,
            $action,
            (int) $record_id,
            $this->object_to_array($old_values),
            $this->object_to_array($new_values)
        );
    }


    /**
     * Convert object/array value for audit logging.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function object_to_array($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return $value;
    }


    /* ==============================================================
     * RESPONSES
     * ============================================================== */

    /**
     * Determine whether request is AJAX.
     *
     * @return bool
     */
    protected function is_ajax()
    {
        return $this->ajax_response->is_ajax();
    }


    /**
     * Send successful response.
     *
     * AJAX:
     *     JSON response.
     *
     * HTML:
     *     Flash message + redirect.
     *
     * @param string $message
     * @param array $data
     * @param string|null $redirect
     *
     * @return void
     */
    protected function success_response(
        $message,
        $data = [],
        $redirect = null
    ) {
        if ($this->is_ajax()) {
            $this->ajax_response->success(
                $message,
                $data,
                200
            );

            return;
        }

        $this->session->set_flashdata(
            'success',
            $message
        );

        if ($redirect !== null) {
            redirect($redirect);
            return;
        }

        redirect('/');
    }


    /**
     * Send error response.
     *
     * @param string $message
     * @param int $status
     * @param array $data
     * @param string|null $redirect
     *
     * @return void
     */
    protected function error_response(
        $message,
        $status = 400,
        $data = [],
        $redirect = null
    ) {
        if ($this->is_ajax()) {
            $this->ajax_response->error(
                $message,
                (int) $status,
                $data
            );

            return;
        }

        if ((int) $status === 404) {
            show_404();
            return;
        }

        $this->session->set_flashdata(
            'error',
            $message
        );

        if ($redirect !== null) {
            redirect($redirect);
            return;
        }

        show_error(
            $message,
            (int) $status,
            'Error'
        );
    }


    /**
     * Send validation error response.
     *
     * When a $view is supplied, a validation failure re-renders
     * that view in the SAME request so form_error()/set_value()/
     * validation_errors() have data to show. $redirect remains
     * as a fallback for callers that don't pass a $view.
     *
     * @param array $errors
     * @param string $message
     * @param string|null $redirect
     * @param string|null $view
     * @param array $data
     *
     * @return void
     */
    protected function validation_error_response(
        $errors = [],
        $message = 'Validation failed.',
        $redirect = null,
        $view = null,
        $data = []
    ) {
        if ($this->is_ajax()) {
            $this->ajax_response->validation_error(
                $errors,
                $message,
                422
            );

            return;
        }

        $this->session->set_flashdata(
            'error',
            $message
        );

        if ($view !== null) {
            /*
             * $errors is exposed to the view as $field_errors so
             * templates can check it before falling back to
             * form_error(). This matters because form_error('x')
             * only returns something when form_validation's OWN
             * rules rejected that field — it knows nothing about
             * manual checks (like "name already exists") that
             * run after form_validation already passed.
             */
            $data['field_errors'] = $errors;

            $this->render($view, $data);
            return;
        }

        if ($redirect !== null) {
            redirect($redirect);
        }
    }


    /**
     * Return a not-found response.
     *
     * @param string $message
     *
     * @return void
     */
    protected function not_found(
        $message = 'Record not found.'
    ) {
        $this->error_response(
            $message,
            404
        );
    }


    /**
     * Return a conflict response.
     *
     * @param string $message
     * @param array $data
     *
     * @return void
     */
    protected function conflict(
        $message,
        $data = []
    ) {
        if ($this->is_ajax()) {
            $this->ajax_response->conflict(
                $message,
                $data
            );

            return;
        }

        $this->error_response(
            $message,
            409,
            $data
        );
    }


    /* ==============================================================
     * VIEW / TEMPLATE
     * ============================================================== */

    /**
     * Render application view.
     *
     * @param string $view
     * @param array $data
     *
     * @return void
     */
    protected function render(
        $view,
        $data = []
    ) {
        $this->template->render(
            $view,
            $data
        );
    }
}
