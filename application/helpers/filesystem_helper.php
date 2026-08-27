    <?php defined('BASEPATH') OR exit('No direct script access allowed');

    if (!function_exists('filesize_formatted')) {
        function filesize_formatted($path)
        {
            $size = file_exists($path) ? filesize($path) : 0;
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            $power = $size > 0 ? floor(log($size, 1024)) : 0;
            return number_format($size / pow(1024, $power), 2) . ' ' . $units[$power];
        }
    }