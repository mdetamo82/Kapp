<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Barcode Service
 *
 * Enterprise barcode generation service for CodeIgniter 3.
 *
 * Responsibilities:
 *
 * - Generate barcodes using picqer/php-barcode-generator.
 * - Generate SVG or PNG output.
 * - Return generated barcode data.
 * - Save generated barcodes to filesystem.
 *
 * This service does NOT:
 *
 * - Handle file uploads.
 * - Manage customer records.
 * - Decide barcode business values.
 * - Write database records.
 *
 * Business logic remains inside the appropriate
 * application service/controller/model.
 */
class Barcode_service
{
    /**
     * SVG barcode generator.
     *
     * @var BarcodeGeneratorSVG
     */
    protected $svg_generator;


    /**
     * PNG barcode generator.
     *
     * @var BarcodeGeneratorPNG
     */
    protected $png_generator;


    /**
     * Default barcode type.
     *
     * @var string
     */
    protected $default_type = 'C128';


    /**
     * Default barcode width.
     *
     * @var int
     */
    protected $default_width = 2;


    /**
     * Default barcode height.
     *
     * @var int
     */
    protected $default_height = 60;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->svg_generator = new BarcodeGeneratorSVG();

        $this->png_generator = new BarcodeGeneratorPNG();
    }


    /**
     * Generate an SVG barcode.
     *
     * @param string $value
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function generate_svg(
        $value,
        $type = null,
        $width = null,
        $height = null
    ) {
        $value = trim((string) $value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'Barcode value cannot be empty.'
            );
        }

        $type = $type !== null
            ? strtoupper(trim($type))
            : $this->default_type;

        $width = $width !== null
            ? (int) $width
            : $this->default_width;

        $height = $height !== null
            ? (int) $height
            : $this->default_height;

        $this->validate_dimensions(
            $width,
            $height
        );

        $barcode_type = $this->resolve_type($type);

        return $this->svg_generator->getBarcode(
            $value,
            $barcode_type,
            $width,
            $height
        );
    }


    /**
     * Generate a PNG barcode.
     *
     * @param string $value
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function generate_png(
        $value,
        $type = null,
        $width = null,
        $height = null
    ) {
        $value = trim((string) $value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'Barcode value cannot be empty.'
            );
        }

        $type = $type !== null
            ? strtoupper(trim($type))
            : $this->default_type;

        $width = $width !== null
            ? (int) $width
            : $this->default_width;

        $height = $height !== null
            ? (int) $height
            : $this->default_height;

        $this->validate_dimensions(
            $width,
            $height
        );

        $barcode_type = $this->resolve_type($type);

        return $this->png_generator->getBarcode(
            $value,
            $barcode_type,
            $width,
            $height
        );
    }


    /**
     * Save an SVG barcode to filesystem.
     *
     * @param string $value
     * @param string $file_path
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     */
    public function save_svg(
        $value,
        $file_path,
        $type = null,
        $width = null,
        $height = null
    ) {
        $file_path = trim((string) $file_path);

        if ($file_path === '') {
            throw new InvalidArgumentException(
                'Barcode file path cannot be empty.'
            );
        }

        $directory = dirname($file_path);

        $this->ensure_directory($directory);

        $barcode = $this->generate_svg(
            $value,
            $type,
            $width,
            $height
        );

        if (
            file_put_contents(
                $file_path,
                $barcode
            ) === false
        ) {
            throw new RuntimeException(
                'Unable to save SVG barcode.'
            );
        }

        return true;
    }


    /**
     * Save a PNG barcode to filesystem.
     *
     * @param string $value
     * @param string $file_path
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     */
    public function save_png(
        $value,
        $file_path,
        $type = null,
        $width = null,
        $height = null
    ) {
        $file_path = trim((string) $file_path);

        if ($file_path === '') {
            throw new InvalidArgumentException(
                'Barcode file path cannot be empty.'
            );
        }

        $directory = dirname($file_path);

        $this->ensure_directory($directory);

        $barcode = $this->generate_png(
            $value,
            $type,
            $width,
            $height
        );

        if (
            file_put_contents(
                $file_path,
                $barcode
            ) === false
        ) {
            throw new RuntimeException(
                'Unable to save PNG barcode.'
            );
        }

        return true;
    }


    /**
     * Return an SVG barcode.
     *
     * Useful for direct HTML output.
     *
     * @param string $value
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     */
    public function svg(
        $value,
        $type = null,
        $width = null,
        $height = null
    ) {
        return $this->generate_svg(
            $value,
            $type,
            $width,
            $height
        );
    }


    /**
     * Return PNG barcode as base64.
     *
     * @param string $value
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     */
    public function base64(
        $value,
        $type = null,
        $width = null,
        $height = null
    ) {
        return base64_encode(
            $this->generate_png(
                $value,
                $type,
                $width,
                $height
            )
        );
    }


    /**
     * Return PNG barcode as a data URI.
     *
     * @param string $value
     * @param string|null $type
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     */
    public function data_uri(
        $value,
        $type = null,
        $width = null,
        $height = null
    ) {
        return 'data:image/png;base64,' .
            $this->base64(
                $value,
                $type,
                $width,
                $height
            );
    }


    /**
     * Resolve supported barcode type.
     *
     * @param string $type
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    protected function resolve_type($type)
    {
        $types = [
            'C128' => 'TYPE_CODE_128',

            'C39' => 'TYPE_CODE_39',
            'CODE39' => 'TYPE_CODE_39',

            'C93' => 'TYPE_CODE_93',
            'CODE93' => 'TYPE_CODE_93',

            'EAN13' => 'TYPE_EAN_13',

            'EAN8' => 'TYPE_EAN_8',

            'UPCA' => 'TYPE_UPC_A',

            'UPCE' => 'TYPE_UPC_E',

            'ITF14' => 'TYPE_ITF_14',

            'ITF' => 'TYPE_INTERLEAVED_2_5',

            'CODABAR' => 'TYPE_CODABAR',

            'MSI' => 'TYPE_MSI',

            'PHARMA' => 'TYPE_PHARMA_CODE_39',
        ];

        if (!isset($types[$type])) {
            throw new InvalidArgumentException(
                'Unsupported barcode type: ' . $type
            );
        }

        $constant = $types[$type];

        return constant(
            BarcodeGeneratorSVG::class . '::' . $constant
        );
    }


    /**
     * Validate barcode dimensions.
     *
     * @param int $width
     * @param int $height
     *
     * @return void
     */
    protected function validate_dimensions(
        $width,
        $height
    ) {
        if ($width <= 0) {
            throw new InvalidArgumentException(
                'Barcode width must be greater than zero.'
            );
        }

        if ($height <= 0) {
            throw new InvalidArgumentException(
                'Barcode height must be greater than zero.'
            );
        }
    }


    /**
     * Ensure target directory exists and is writable.
     *
     * @param string $directory
     *
     * @return void
     */
    protected function ensure_directory($directory)
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException(
                    'Unable to create barcode directory.'
                );
            }
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(
                'Barcode directory is not writable.'
            );
        }
    }
}
