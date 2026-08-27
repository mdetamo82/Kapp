<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

function alert_success($message) {
    $CI =& get_instance();
    $CI->session->set_flashdata('success', $message);
}

function alert_error($message) {
    $CI =& get_instance();
    $CI->session->set_flashdata('error', $message);
}

function alert_info($message) {
    $CI =& get_instance();
    $CI->session->set_flashdata('info', $message);
}

function alert_warning($message) {
    $CI =& get_instance();
    $CI->session->set_flashdata('warning', $message);
}

