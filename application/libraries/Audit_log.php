<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Log Service
 *
 * Centralized application audit logging.
 *
 * Responsibilities:
 * - Record who performed an action
 * - Record module/action/record
 * - Record old and new values
 * - Record request IP and user agent
 *
 * Audit logging must remain independent from authorization.
 */
class Audit_log
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->database();
        $this->CI->load->library('ion_auth');
    }

    /**
     * Record an audit event.
     *
     * @param string     $module
     * @param string     $action
     * @param int|null   $record_id
     * @param array|null $old_values
     * @param array|null $new_values
     *
     * @return int|false Audit ID on success, false on failure.
     */
    public function log(
        $module,
        $action,
        $record_id = null,
        $old_values = null,
        $new_values = null
    ) {
        if (!is_string($module) || trim($module) === '') {
            return false;
        }

        if (!is_string($action) || trim($action) === '') {
            return false;
        }

        $module = trim($module);
        $action = trim($action);

        if ($record_id !== null) {
            if (
                !is_numeric($record_id) ||
                (int) $record_id <= 0
            ) {
                return false;
            }

            $record_id = (int) $record_id;
        }

        $user_id = null;

        if ($this->CI->ion_auth->logged_in()) {
            $user_id = $this->CI->ion_auth->get_user_id();

            if ($user_id !== null && $user_id !== false) {
                $user_id = (int) $user_id;
            } else {
                $user_id = null;
            }
        }

        $data = [
            'user_id'    => $user_id,
            'module'     => $module,
            'action'     => $action,
            'record_id'  => $record_id,
            'old_values' => $this->encode_values($old_values),
            'new_values' => $this->encode_values($new_values),
            'ip_address' => $this->get_ip_address(),
            'user_agent' => $this->get_user_agent(),
        ];

        if (!$this->CI->db->insert('audit_logs', $data)) {
            log_message(
                'error',
                'Audit_log: Failed to insert audit event.'
                . ' module=' . $module
                . ' action=' . $action
                . ' record_id=' . (string) $record_id
            );

            return false;
        }

        $audit_id = $this->CI->db->insert_id();

        return $audit_id > 0
            ? (int) $audit_id
            : false;
    }

    /**
     * Encode audit values safely as JSON.
     *
     * @param mixed $values
     *
     * @return string|null
     */
    protected function encode_values($values)
    {
        if ($values === null) {
            return null;
        }

        if (!is_array($values)) {
            return null;
        }

        try {
            $json = json_encode(
                $values,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
            );

            if ($json === false) {
                log_message(
                    'error',
                    'Audit_log: Unable to encode audit values.'
                );

                return null;
            }

            return $json;
        } catch (Throwable $e) {
            log_message(
                'error',
                'Audit_log: JSON encoding exception: '
                . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Get the current request IP address.
     *
     * @return string|null
     */
    protected function get_ip_address()
    {
        $ip = $this->CI->input->ip_address();

        if (!$ip || $ip === '0.0.0.0') {
            return null;
        }

        return substr((string) $ip, 0, 45);
    }

    /**
     * Get the current request user agent.
     *
     * @return string|null
     */
    protected function get_user_agent()
    {
        $user_agent = $this->CI->input->user_agent();

        if (!$user_agent) {
            return null;
        }

        return substr((string) $user_agent, 0, 500);
    }
}
