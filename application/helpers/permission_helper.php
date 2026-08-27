<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Safely encode a value for HTML output.
 *
 * @param mixed $input
 * @param bool  $preserveLineBreaks
 * @return string
 */
if (!function_exists('safe_html')) {
    function safe_html($input, bool $preserveLineBreaks = false): string
    {
        if ($input === null) {
            return '';
        }

        if (is_bool($input)) {
            return $input ? 'true' : 'false';
        }

        if (
            is_array($input) ||
            (is_object($input) && !method_exists($input, '__toString'))
        ) {
            throw new InvalidArgumentException(
                'Cannot sanitize array/object as HTML'
            );
        }

        $string = (string) $input;

        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding(
                $string,
                'UTF-8',
                'UTF-8'
            );
        }

        if ($preserveLineBreaks) {
            $string = nl2br($string, false);
        }

        return htmlspecialchars(
            $string,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8',
            false
        );
    }
}

/**
 * Determine whether the current user has a permission.
 *
 * This helper is intentionally thin.
 * All authorization decisions belong to Authorization.
 *
 * @param string $permission
 * @return bool
 */
if (!function_exists('has_permission')) {
    function has_permission($permission): bool
    {
        if (!is_string($permission) || trim($permission) === '') {
            return false;
        }

        $CI =& get_instance();

        $CI->load->library('authorization');

        return $CI->authorization->can(trim($permission));
    }
}

/**
 * Require a permission.
 *
 * Controllers should use this at authorization boundaries.
 *
 * @param string $permission
 * @return void
 */
if (!function_exists('require_permission')) {
    function require_permission($permission)
    {
        $CI =& get_instance();

        $CI->load->library('authorization');

        $CI->authorization->require_permission($permission);
    }
}
