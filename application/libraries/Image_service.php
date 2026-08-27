<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Image Service
 *
 * Centralized image-upload infrastructure for the application.
 *
 * Responsibilities:
 *
 * - Secure image uploads
 * - Image-specific validation
 * - Profile-photo handling
 * - Dimension validation
 * - File-size validation
 * - Allowed image-type configuration
 * - Safe image replacement
 * - Image deletion
 *
 * This service delegates physical file uploading to
 * Upload_service.
 *
 * Business-specific database operations remain outside
 * this service.
 */
class Image_service
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;


    /**
     * Upload service.
     *
     * @var Upload_service
     */
    protected $upload_service;


    /**
     * Default allowed image extensions.
     *
     * @var array
     */
    protected $allowed_types = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];


    /**
     * Default maximum file size in KB.
     *
     * 5120 KB = 5 MB.
     *
     * @var int
     */
    protected $default_max_size = 5120;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->library(
            'upload_service'
        );

        $this->upload_service =
            $this->CI->upload_service;
    }


    /* ==============================================================
     * PROFILE PHOTO
     * ============================================================== */

    /**
     * Upload a profile photo.
     *
     * This is the main API that controllers should use for
     * customer/staff profile photos.
     *
     * Example:
     *
     * $result = $this->image_service->upload_profile_photo(
     *     'profile_photo',
     *     'customers/1001'
     * );
     *
     * @param string $field
     * @param string $directory
     * @param array  $options
     *
     * @return array
     */
    public function upload_profile_photo(
        $field,
        $directory,
        $options = []
    ) {
        $defaults = [
            'max_size'   => $this->default_max_size,
            'min_width'  => 100,
            'min_height' => 100,
            'max_width'  => 5000,
            'max_height' => 5000,
        ];

        $options = array_merge(
            $defaults,
            $options
        );

        return $this->upload_image(
            $field,
            $directory,
            $options
        );
    }


    /* ==============================================================
     * GENERIC IMAGE UPLOAD
     * ============================================================== */

    /**
     * Upload an image.
     *
     * @param string $field
     * @param string $directory
     * @param array  $options
     *
     * @return array
     */
    public function upload_image(
        $field,
        $directory,
        $options = []
    ) {
        $options = $this->normalize_options(
            $options
        );

        /*
         * Ensure a file was actually supplied.
         */
        if (!$this->upload_service->has_file($field)) {
            return $this->failure(
                'No image was uploaded.'
            );
        }

        /*
         * Validate the uploaded file before handing it
         * to CodeIgniter's Upload library.
         */
        $pre_validation = $this->validate_uploaded_image(
            $field,
            $options
        );

        if (!$pre_validation['success']) {
            return $pre_validation;
        }

        /*
         * Delegate the actual filesystem upload.
         */
        $result = $this->upload_service->upload(
            $field,
            [
                'directory'     => $directory,
                'allowed_types' => $options['allowed_types'],
                'max_size'      => $options['max_size'],
                'min_width'     => $options['min_width'],
                'min_height'    => $options['min_height'],
                'max_width'     => $options['max_width'],
                'max_height'    => $options['max_height'],
            ]
        );

        if (!$result['success']) {
            return $result;
        }

        /*
         * Perform an additional post-upload verification.
         */
        $post_validation = $this->validate_image_file(
            $result['path'],
            $options
        );

        if (!$post_validation['success']) {

            /*
             * Remove the uploaded file if it does not pass
             * final image validation.
             */
            $this->upload_service->delete(
                $result['path']
            );

            return $post_validation;
        }

        /*
         * Add image-specific metadata.
         */
        $result['width'] = $post_validation['width'];
        $result['height'] = $post_validation['height'];
        $result['image_type'] = $post_validation['image_type'];

        return $result;
    }


    /* ==============================================================
     * IMAGE VALIDATION
     * ============================================================== */

    /**
     * Validate the PHP uploaded file before processing.
     *
     * @param string $field
     * @param array  $options
     *
     * @return array
     */
    protected function validate_uploaded_image(
        $field,
        $options
    ) {
        if (
            !isset($_FILES[$field])
            || !is_array($_FILES[$field])
        ) {
            return $this->failure(
                'Invalid image upload.'
            );
        }

        $file = $_FILES[$field];

        if (
            !isset($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])
        ) {
            return $this->failure(
                'Invalid uploaded image.'
            );
        }

        /*
         * File size validation.
         */
        $size = isset($file['size'])
            ? (int) $file['size']
            : 0;

        $max_bytes =
            (int) $options['max_size'] * 1024;

        if ($size <= 0) {
            return $this->failure(
                'The uploaded image is empty.'
            );
        }

        if ($size > $max_bytes) {
            return $this->failure(
                'The image exceeds the maximum allowed size of '
                . $options['max_size']
                . ' KB.'
            );
        }

        /*
         * Validate the actual image contents.
         *
         * getimagesize() examines the file contents rather
         * than trusting the filename.
         */
        $image_info = @getimagesize(
            $file['tmp_name']
        );

        if ($image_info === false) {
            return $this->failure(
                'The uploaded file is not a valid image.'
            );
        }

        $mime = isset($image_info['mime'])
            ? strtolower($image_info['mime'])
            : '';

        $allowed_mimes =
            $this->allowed_mimes(
                $options['allowed_types']
            );

        if (
            !isset($allowed_mimes[$mime])
        ) {
            return $this->failure(
                'The uploaded image format is not allowed.'
            );
        }

        /*
         * Image dimensions.
         */
        $width = isset($image_info[0])
            ? (int) $image_info[0]
            : 0;

        $height = isset($image_info[1])
            ? (int) $image_info[1]
            : 0;

        if (
            $width < $options['min_width']
            || $height < $options['min_height']
        ) {
            return $this->failure(
                'The image dimensions are too small.'
            );
        }

        if (
            $options['max_width'] !== null
            && $width > $options['max_width']
        ) {
            return $this->failure(
                'The image width exceeds the maximum allowed dimension.'
            );
        }

        if (
            $options['max_height'] !== null
            && $height > $options['max_height']
        ) {
            return $this->failure(
                'The image height exceeds the maximum allowed dimension.'
            );
        }

        return [
            'success'    => true,
            'error'      => null,
            'width'      => $width,
            'height'     => $height,
            'mime_type'  => $mime,
            'image_type' => $image_info[2],
        ];
    }


    /**
     * Validate the uploaded image after it has been stored.
     *
     * @param string $path
     * @param array  $options
     *
     * @return array
     */
    protected function validate_image_file(
        $path,
        $options
    ) {
        if (!is_file($path)) {
            return $this->failure(
                'Uploaded image file could not be verified.'
            );
        }

        /*
         * Use getimagesize() again against the actual stored file.
         */
        $image_info = @getimagesize($path);

        if ($image_info === false) {
            return $this->failure(
                'The stored file is not a valid image.'
            );
        }

        $mime = isset($image_info['mime'])
            ? strtolower($image_info['mime'])
            : '';

        $allowed_mimes =
            $this->allowed_mimes(
                $options['allowed_types']
            );

        if (
            !isset($allowed_mimes[$mime])
        ) {
            return $this->failure(
                'The stored image format is not allowed.'
            );
        }

        $width = isset($image_info[0])
            ? (int) $image_info[0]
            : 0;

        $height = isset($image_info[1])
            ? (int) $image_info[1]
            : 0;

        if (
            $width < $options['min_width']
            || $height < $options['min_height']
        ) {
            return $this->failure(
                'The stored image dimensions are too small.'
            );
        }

        if (
            $options['max_width'] !== null
            && $width > $options['max_width']
        ) {
            return $this->failure(
                'The stored image width exceeds the allowed dimension.'
            );
        }

        if (
            $options['max_height'] !== null
            && $height > $options['max_height']
        ) {
            return $this->failure(
                'The stored image height exceeds the allowed dimension.'
            );
        }

        return [
            'success'    => true,
            'error'      => null,
            'width'      => $width,
            'height'     => $height,
            'mime_type'  => $mime,
            'image_type' => $image_info[2],
        ];
    }


    /* ==============================================================
     * DELETE / REPLACE
     * ============================================================== */

    /**
     * Delete an image.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        return $this->upload_service->delete(
            $path
        );
    }


    /**
     * Replace an existing image with a new upload.
     *
     * The old image is deleted only after the new image
     * has successfully passed validation and upload.
     *
     * @param string      $field
     * @param string      $directory
     * @param string|null $old_path
     * @param array       $options
     *
     * @return array
     */
    public function replace(
        $field,
        $directory,
        $old_path = null,
        $options = []
    ) {
        $result = $this->upload_image(
            $field,
            $directory,
            $options
        );

        if (!$result['success']) {
            return $result;
        }

        /*
         * New image is valid.
         *
         * Now remove the old image.
         */
        if (
            $old_path !== null
            && $old_path !== ''
        ) {
            $this->delete(
                $old_path
            );
        }

        return $result;
    }


    /* ==============================================================
     * OPTIONS
     * ============================================================== */

    /**
     * Normalize image options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function normalize_options(
        $options
    ) {
        $defaults = [
            'allowed_types' =>
                $this->allowed_types,

            'max_size' =>
                $this->default_max_size,

            'min_width' =>
                null,

            'min_height' =>
                null,

            'max_width' =>
                null,

            'max_height' =>
                null,
        ];

        $options = array_merge(
            $defaults,
            $options
        );

        $options['allowed_types'] =
            $this->normalize_types(
                $options['allowed_types']
            );

        return $options;
    }


    /**
     * Normalize image extensions.
     *
     * @param array|string $types
     *
     * @return array
     */
    protected function normalize_types(
        $types
    ) {
        if (is_string($types)) {
            $types = explode(
                '|',
                $types
            );
        }

        $normalized = [];

        foreach ((array) $types as $type) {

            $type = strtolower(
                trim(
                    ltrim(
                        $type,
                        '.'
                    )
                )
            );

            if ($type === '') {
                continue;
            }

            if (
                !preg_match(
                    '/^[a-z0-9]+$/',
                    $type
                )
            ) {
                continue;
            }

            $normalized[] = $type;
        }

        return array_values(
            array_unique(
                $normalized
            )
        );
    }


    /**
     * Map image extensions to MIME types.
     *
     * @param array $types
     *
     * @return array
     */
    protected function allowed_mimes(
        $types
    ) {
        $map = [
            'jpg' => [
                'image/jpeg',
            ],

            'jpeg' => [
                'image/jpeg',
            ],

            'png' => [
                'image/png',
            ],

            'webp' => [
                'image/webp',
            ],

            'gif' => [
                'image/gif',
            ],
        ];

        $allowed = [];

        foreach ($types as $type) {

            if (!isset($map[$type])) {
                continue;
            }

            foreach ($map[$type] as $mime) {
                $allowed[$mime] = true;
            }
        }

        return $allowed;
    }


    /* ==============================================================
     * RESPONSE
     * ============================================================== */

    /**
     * Build standardized failure response.
     *
     * @param string $message
     *
     * @return array
     */
    protected function failure(
        $message
    ) {
        return [
            'success' => false,
            'error'   => $message,
        ];
    }
}
