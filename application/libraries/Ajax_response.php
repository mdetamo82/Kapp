<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ajax Response Library
 *
 * Enterprise-standard JSON response builder for AJAX/API-style
 * mutation requests.
 *
 * Responsibilities:
 * - Detect AJAX requests
 * - Return consistent JSON structures
 * - Set correct HTTP status codes
 * - Return the current CSRF token
 * - Return the CSRF token in a response header
 * - Provide consistent success/error responses
 *
 * This library does NOT:
 * - Perform authorization
 * - Perform validation
 * - Perform database operations
 * - Perform redirects
 * - Perform business logic
 */
class Ajax_response
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
    }

    /**
     * Determine whether the current request is AJAX.
     *
     * @return bool
     */
    public function is_ajax()
    {
        return $this->CI->input->is_ajax_request();
    }

    /**
     * Send a successful JSON response.
     *
     * @param string $message
     * @param array  $data
     * @param int    $status
     *
     * @return void
     */
    public function success(
        $message = 'Operation completed successfully.',
        array $data = [],
        $status = 200
    ) {
        $this->respond(
            true,
            $message,
            $data,
            (int) $status
        );
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message
     * @param int    $status
     * @param array  $data
     *
     * @return void
     */
    public function error(
        $message = 'The requested operation could not be completed.',
        $status = 400,
        array $data = []
    ) {
        $this->respond(
            false,
            $message,
            $data,
            (int) $status
        );
    }

    /**
     * Send a validation error response.
     *
     * @param array  $errors
     * @param string $message
     * @param int    $status
     *
     * @return void
     */
    public function validation_error(
        array $errors = [],
        $message = 'Validation failed.',
        $status = 422
    ) {
        $this->error(
            $message,
            (int) $status,
            [
                'errors' => $errors,
            ]
        );
    }

    /**
     * Send an unauthorized response.
     *
     * @param string $message
     *
     * @return void
     */
    public function unauthorized(
        $message = 'Authentication is required.'
    ) {
        $this->error(
            $message,
            401
        );
    }

    /**
     * Send a forbidden response.
     *
     * @param string $message
     *
     * @return void
     */
    public function forbidden(
        $message = 'You are not authorized to perform this action.'
    ) {
        $this->error(
            $message,
            403
        );
    }

    /**
     * Send a not-found response.
     *
     * @param string $message
     *
     * @return void
     */
    public function not_found(
        $message = 'The requested resource was not found.'
    ) {
        $this->error(
            $message,
            404
        );
    }

    /**
     * Send a method-not-allowed response.
     *
     * @param string $message
     *
     * @return void
     */
    public function method_not_allowed(
        $message = 'The requested HTTP method is not allowed.'
    ) {
        $this->error(
            $message,
            405
        );
    }

    /**
     * Send a conflict response.
     *
     * @param string $message
     * @param array  $data
     *
     * @return void
     */
    public function conflict(
        $message = 'The requested operation conflicts with existing data.',
        array $data = []
    ) {
        $this->error(
            $message,
            409,
            $data
        );
    }

    /**
     * Send an internal server error response.
     *
     * @param string $message
     * @param array  $data
     *
     * @return void
     */
    public function server_error(
        $message = 'An unexpected server error occurred.',
        array $data = []
    ) {
        $this->error(
            $message,
            500,
            $data
        );
    }

    /**
     * Send a service unavailable response.
     *
     * @param string $message
     * @param array  $data
     *
     * @return void
     */
    public function service_unavailable(
        $message = 'The service is temporarily unavailable.',
        array $data = []
    ) {
        $this->error(
            $message,
            503,
            $data
        );
    }

    /**
     * Build and send the final JSON response.
     *
     * @param bool   $success
     * @param string $message
     * @param array  $data
     * @param int    $status
     *
     * @return void
     */
    protected function respond(
        $success,
        $message,
        array $data,
        $status
    ) {
        $status = $this->normalize_status($status);

        $payload = [
            'success' => (bool) $success,
            'message' => (string) $message,
            'data'    => $data,
            'csrf'    => $this->csrf_payload(),
        ];

        $csrf_hash = $this->CI->security->get_csrf_hash();

        $this->CI->output
            ->set_status_header($status)
            ->set_header(
                'X-CSRF-TOKEN: ' . $csrf_hash
            )
            ->set_header(
                'X-Content-Type-Options: nosniff'
            )
            ->set_header(
                'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
            )
            ->set_content_type(
                'application/json',
                'utf-8'
            )
            ->set_output(
                $this->encode_json($payload)
            );
    }

    /**
     * Return the current CSRF information.
     *
     * @return array
     */
    protected function csrf_payload()
    {
        return [
            'name'  => $this->CI->security->get_csrf_token_name(),
            'token' => $this->CI->security->get_csrf_hash(),
        ];
    }

    /**
     * Safely encode a response as JSON.
     *
     * @param array $payload
     *
     * @return string
     */
    protected function encode_json(array $payload)
    {
        try {
            $json = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
            );

            if ($json !== false) {
                return $json;
            }

            log_message(
                'error',
                'Ajax_response: JSON encoding failed.'
            );
        } catch (Throwable $e) {
            log_message(
                'error',
                'Ajax_response: JSON encoding exception: '
                . $e->getMessage()
            );
        }

        /*
         * This should be extremely rare. Return a valid JSON
         * response rather than allowing an invalid response body.
         */
        return json_encode([
            'success' => false,
            'message' => 'Unable to encode the server response.',
            'data'    => [],
            'csrf'    => $this->csrf_payload(),
        ]);
    }

    /**
     * Normalize HTTP status codes.
     *
     * @param int $status
     *
     * @return int
     */
    protected function normalize_status($status)
    {
        $status = (int) $status;

        if ($status < 100 || $status > 599) {
            return 500;
        }

        return $status;
    }
}
