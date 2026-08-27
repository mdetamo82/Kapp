<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QR Code Service
 *
 * Enterprise QR code generation service for CodeIgniter 3.
 *
 * Responsibilities:
 *
 * - Generate QR codes using endroid/qr-code.
 * - Generate PNG output.
 * - Return binary image data.
 * - Save QR codes to a supplied filesystem path.
 *
 * This service does NOT:
 *
 * - Handle file uploads.
 * - Validate uploaded images.
 * - Manage database records.
 * - Decide where customer files are stored.
 *
 * Those responsibilities belong to Upload_service,
 * Image_service, and the relevant model/service.
 */
class Qr_service
{
    /**
     * QR code image writer.
     *
     * @var PngWriter
     */
    protected $writer;


    /**
     * Default QR size.
     *
     * @var int
     */
    protected $default_size = 300;


    /**
     * Default margin.
     *
     * @var int
     */
    protected $default_margin = 10;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->writer = new PngWriter();
    }


    /**
     * Generate QR code PNG binary data.
     *
     * @param string $data
     * @param int $size
     * @param int $margin
     *
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function generate(
        $data,
        $size = null,
        $margin = null
    ) {
        $data = trim((string) $data);

        if ($data === '') {
            throw new InvalidArgumentException(
                'QR code data cannot be empty.'
            );
        }

        $size = $size !== null
            ? (int) $size
            : $this->default_size;

        $margin = $margin !== null
            ? (int) $margin
            : $this->default_margin;

        if ($size <= 0) {
            throw new InvalidArgumentException(
                'QR code size must be greater than zero.'
            );
        }

        if ($margin < 0) {
            throw new InvalidArgumentException(
                'QR code margin cannot be negative.'
            );
        }

        $result = Builder::create()
            ->writer($this->writer)
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(
                new ErrorCorrectionLevelHigh()
            )
            ->size($size)
            ->margin($margin)
            ->roundBlockSizeMode(
                new RoundBlockSizeModeMargin()
            )
            ->build();

        return $result->getString();
    }


    /**
     * Generate QR code and save it to a file.
     *
     * @param string $data
     * @param string $file_path
     * @param int|null $size
     * @param int|null $margin
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function save(
        $data,
        $file_path,
        $size = null,
        $margin = null
    ) {
        $file_path = trim((string) $file_path);

        if ($file_path === '') {
            throw new InvalidArgumentException(
                'QR code file path cannot be empty.'
            );
        }

        $directory = dirname($file_path);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException(
                    'Unable to create QR code directory.'
                );
            }
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(
                'QR code directory is not writable.'
            );
        }

        $png = $this->generate(
            $data,
            $size,
            $margin
        );

        if (file_put_contents($file_path, $png) === false) {
            throw new RuntimeException(
                'Unable to save QR code.'
            );
        }

        return true;
    }


    /**
     * Generate QR code and return base64 data URI.
     *
     * Useful when the QR code needs to be displayed
     * directly inside an HTML page.
     *
     * @param string $data
     * @param int|null $size
     * @param int|null $margin
     *
     * @return string
     */
    public function data_uri(
        $data,
        $size = null,
        $margin = null
    ) {
        $png = $this->generate(
            $data,
            $size,
            $margin
        );

        return 'data:image/png;base64,' . base64_encode($png);
    }


    /**
     * Generate QR code as base64 string.
     *
     * @param string $data
     * @param int|null $size
     * @param int|null $margin
     *
     * @return string
     */
    public function base64(
        $data,
        $size = null,
        $margin = null
    ) {
        return base64_encode(
            $this->generate(
                $data,
                $size,
                $margin
            )
        );
    }
}
