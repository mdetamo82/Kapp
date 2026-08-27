<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Upload Service
 *
 * Centralized file-upload infrastructure for the application.
 *
 * Responsibilities:
 *
 * - Secure file upload handling
 * - MIME type / extension restrictions
 * - File size restrictions
 * - Randomized filenames
 * - Controlled storage directories
 * - Automatic directory creation
 * - Collision avoidance
 * - Upload error normalization
 * - File deletion
 *
 * Business-specific rules such as:
 *
 * - Customer profile photo
 * - Employee document
 * - Invoice attachment
 * - Identity document
 *
 * should be configured by the calling service/controller.
 *
 * This class does NOT:
 *
 * - Store database records
 * - Generate QR codes
 * - Generate barcodes
 * - Resize images
 * - Apply business rules
 */
class Upload_service
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;


    /**
     * Default upload directory.
     *
     * @var string
     */
    protected $default_directory = 'uploads';


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->helper([
            'file',
            'security',
        ]);
    }


    /* ==============================================================
     * PUBLIC UPLOAD API
     * ============================================================== */

    /**
     * Upload a file.
     *
     * Example:
     *
     * $result = $this->upload_service->upload(
     *     'profile_photo',
     *     [
     *         'directory'      => 'customers/photos/1001',
     *         'allowed_types'  => ['jpg', 'jpeg', 'png', 'webp'],
     *         'max_size'       => 2048,
     *     ]
     * );
     *
     * @param string $field
     * @param array  $options
     *
     * @return array
     */
    public function upload($field, $options = [])
    {
        $options = $this->normalize_options($options);

        if (!$this->has_file($field)) {
            return $this->failure(
                'No file was uploaded.'
            );
        }

        if ($this->has_upload_error($field)) {
            return $this->upload_error(
                $_FILES[$field]['error']
            );
        }

        $directory = $this->prepare_directory(
            $options['directory']
        );

        if ($directory === false) {
            return $this->failure(
                'Unable to prepare the upload directory.'
            );
        }

        $config = [
            'upload_path'      => $directory,
            'allowed_types'    => implode(
                '|',
                $options['allowed_types']
            ),
            'max_size'         => $options['max_size'],
            'encrypt_name'     => true,
            'remove_spaces'    => true,
            'detect_mime'      => true,
            'mod_mime_fix'     => true,
            'overwrite'        => false,
        ];

        /*
         * Only configure optional image restrictions when
         * explicitly supplied by the caller.
         */
        if ($options['max_width'] !== null) {
            $config['max_width'] = $options['max_width'];
        }

        if ($options['max_height'] !== null) {
            $config['max_height'] = $options['max_height'];
        }

        if ($options['min_width'] !== null) {
            $config['min_width'] = $options['min_width'];
        }

        if ($options['min_height'] !== null) {
            $config['min_height'] = $options['min_height'];
        }

        $this->CI->load->library(
            'upload',
            $config
        );

        /*
         * Reinitialize because this service may be used multiple
         * times during the same request.
         */
        $this->CI->upload->initialize(
            $config,
            true
        );

        if (!$this->CI->upload->do_upload($field)) {
            return $this->failure(
                $this->CI->upload->display_errors(
                    '',
                    ''
                )
            );
        }

        $file = $this->CI->upload->data();

        /*
         * Normalize the result so callers never need to know
         * CodeIgniter's internal upload array structure.
         */
        return [
            'success'         => true,
            'error'           => null,
            'original_name'   => $file['orig_name'],
            'filename'        => $file['file_name'],
            'extension'       => strtolower(
                ltrim($file['file_ext'], '.')
            ),
            'mime_type'       => $file['file_type'],
            'size'            => (int) $file['file_size'],
            'path'            => $file['full_path'],
            'relative_path'   => $this->relative_path(
                $file['full_path']
            ),
            'directory'       => $directory,
        ];
    }


    /**
     * Determine whether an upload exists.
     *
     * @param string $field
     *
     * @return bool
     */
    public function has_file($field)
    {
        return isset($_FILES[$field])
            && isset($_FILES[$field]['name'])
            && $_FILES[$field]['name'] !== '';
    }


    /**
     * Delete a previously uploaded file.
     *
     * Accepts either:
     *
     * - absolute filesystem path
     * - relative application upload path
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $absolute_path = $this->absolute_path($path);

        if ($absolute_path === false) {
            return false;
        }

        if (!is_file($absolute_path)) {
            return true;
        }

        return unlink($absolute_path);
    }


    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path)
    {
        $absolute_path = $this->absolute_path($path);

        return $absolute_path !== false
            && is_file($absolute_path);
    }


    /**
     * Convert a relative upload path to an absolute path.
     *
     * @param string $path
     *
     * @return string|false
     */
    public function absolute_path($path)
    {
        if ($path === null || $path === '') {
            return false;
        }

        /*
         * Already absolute.
         */
        if ($this->is_absolute_path($path)) {
            return $this->safe_path($path);
        }

        return $this->safe_path(
            FCPATH . ltrim($path, '/')
        );
    }


    /* ==============================================================
     * OPTIONS
     * ============================================================== */

    /**
     * Normalize upload options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function normalize_options($options)
    {
        $defaults = [
            'directory'     => $this->default_directory,

            /*
             * Empty allowed_types means "nothing allowed".
             *
             * This is intentional. Every caller should explicitly
             * define what it accepts.
             */
            'allowed_types' => [],

            /*
             * CodeIgniter max_size is expressed in KB.
             *
             * 5120 = 5 MB
             */
            'max_size'      => 5120,

            'max_width'     => null,
            'max_height'    => null,
            'min_width'     => null,
            'min_height'    => null,
        ];

        $options = array_merge(
            $defaults,
            $options
        );

        /*
         * Normalize directory.
         */
        $options['directory'] = trim(
            str_replace('\\', '/', $options['directory']),
            '/'
        );

        /*
         * Normalize extensions.
         */
        $options['allowed_types'] = $this->normalize_types(
            $options['allowed_types']
        );

        return $options;
    }


    /**
     * Normalize allowed file extensions.
     *
     * @param array|string $types
     *
     * @return array
     */
    protected function normalize_types($types)
    {
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        $normalized = [];

        foreach ((array) $types as $type) {
            $type = strtolower(
                trim(
                    ltrim($type, '.')
                )
            );

            if ($type === '') {
                continue;
            }

            /*
             * Only allow normal extension characters.
             */
            if (!preg_match('/^[a-z0-9]+$/', $type)) {
                continue;
            }

            $normalized[] = $type;
        }

        return array_values(
            array_unique($normalized)
        );
    }


    /* ==============================================================
     * DIRECTORY MANAGEMENT
     * ============================================================== */

    /**
     * Prepare an upload directory.
     *
     * @param string $relative_directory
     *
     * @return string|false
     */
    protected function prepare_directory($relative_directory)
    {
        $directory = $this->safe_directory(
            FCPATH . 'uploads',
            $relative_directory
        );

        if ($directory === false) {
            return false;
        }

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return false;
            }
        }

        if (!is_writable($directory)) {
            return false;
        }

        /*
         * Add an index file to prevent basic directory listing
         * when the web server permits directory browsing.
         */
        $index_file = $directory . DIRECTORY_SEPARATOR . 'index.html';

        if (!file_exists($index_file)) {
            @file_put_contents(
                $index_file,
                ''
            );
        }

        return rtrim(
            $directory,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR;
    }


    /**
     * Build a safe upload directory.
     *
     * Prevents "../" traversal outside the upload root.
     *
     * @param string $root
     * @param string $relative
     *
     * @return string|false
     */
    protected function safe_directory(
        $root,
        $relative
    ) {
        $root = realpath($root);

        if ($root === false) {
            /*
             * The uploads root itself may not exist yet.
             */
            $root = rtrim(
                $root,
                DIRECTORY_SEPARATOR
            );
        }

        $relative = trim(
            str_replace('\\', '/', $relative),
            '/'
        );

        /*
         * Reject traversal attempts.
         */
        if (
            strpos($relative, '..') !== false
            || preg_match(
                '#(^|/)\.\.(/|$)#',
                $relative
            )
        ) {
            return false;
        }

        /*
         * Only allow safe directory names.
         */
        if (
            $relative !== ''
            && !preg_match(
                '#^[a-zA-Z0-9/_-]+$#',
                $relative
            )
        ) {
            return false;
        }

        $directory = $root;

        if ($relative !== '') {
            $directory .= DIRECTORY_SEPARATOR
                . str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relative
                );
        }

        return $directory;
    }


    /* ==============================================================
     * FILE VALIDATION
     * ============================================================== */

    /**
     * Determine whether PHP reported an upload error.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function has_upload_error($field)
    {
        return isset($_FILES[$field]['error'])
            && $_FILES[$field]['error'] !== UPLOAD_ERR_OK;
    }


    /**
     * Convert PHP upload error to application response.
     *
     * @param int $error
     *
     * @return array
     */
    protected function upload_error($error)
    {
        switch ($error) {

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file is too large.';
                break;

            case UPLOAD_ERR_PARTIAL:
                $message = 'The file upload was incomplete.';
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded.';
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'The server upload directory is missing.';
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $message = 'The server could not write the uploaded file.';
                break;

            case UPLOAD_ERR_EXTENSION:
                $message = 'The file upload was blocked by a server extension.';
                break;

            default:
                $message = 'The file upload failed.';
                break;
        }

        return $this->failure($message);
    }


    /* ==============================================================
     * PATH SECURITY
     * ============================================================== */

    /**
     * Ensure a path remains inside the application upload directory.
     *
     * @param string $path
     *
     * @return string|false
     */
    protected function safe_path($path)
    {
        $upload_root = realpath(
            FCPATH . 'uploads'
        );

        if ($upload_root === false) {
            return false;
        }

        $real_path = realpath($path);

        if ($real_path === false) {
            /*
             * File may have already been deleted.
             */
            return false;
        }

        if (
            $real_path !== $upload_root
            && strpos(
                $real_path,
                $upload_root . DIRECTORY_SEPARATOR
            ) !== 0
        ) {
            return false;
        }

        return $real_path;
    }


    /**
     * Determine whether path is absolute.
     *
     * Supports Unix and Windows paths.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function is_absolute_path($path)
    {
        return (
            strpos($path, '/') === 0
            || preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path
            )
        );
    }


    /**
     * Convert absolute path to application-relative path.
     *
     * @param string $path
     *
     * @return string
     */
    protected function relative_path($path)
    {
        $base = rtrim(
            str_replace(
                '\\',
                '/',
                FCPATH
            ),
            '/'
        );

        $path = str_replace(
            '\\',
            '/',
            $path
        );

        if (
            strpos($path, $base . '/') === 0
        ) {
            return ltrim(
                substr(
                    $path,
                    strlen($base)
                ),
                '/'
            );
        }

        return $path;
    }


    /* ==============================================================
     * RESPONSES
     * ============================================================== */

    /**
     * Build a standardized failure response.
     *
     * @param string $message
     *
     * @return array
     */
    protected function failure($message)
    {
        return [
            'success' => false,
            'error'   => $message,
        ];
    }
}