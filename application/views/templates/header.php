<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$app_title = isset($app_title)
    ? $app_title
    : 'Nebat Import Export';

$title = isset($title) && trim((string) $title) !== ''
    ? $title . ' | ' . $app_title
    : $app_title;

$body_classes = isset($body_classes) && is_array($body_classes)
    ? $body_classes
    : [
        'hold-transition',
        'sidebar-mini',
        'layout-fixed',
    ];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= html_escape($title); ?></title>

    <!-- CSRF -->
    <meta
        name="csrf-name"
        content="<?= html_escape(
            $this->security->get_csrf_token_name()
        ); ?>"
    >

    <meta
        name="csrf-token"
        content="<?= html_escape(
            $this->security->get_csrf_hash()
        ); ?>"
    >

    <?php if (!empty($meta) && is_array($meta)): ?>

        <?php foreach ($meta as $name => $content): ?>

            <meta
                name="<?= html_escape($name); ?>"
                content="<?= html_escape($content); ?>"
            >

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- Favicon -->
    <link
        rel="icon"
        href="<?= base_url(
            $this->config->item('app_favicon', 'app')
        ); ?>"
        type="image/png"
    >


    <!-- Google Font -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"
    >


    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="<?= base_url(
            'assets/plugins/fontawesome-free/css/all.min.css'
        ); ?>"
    >


    <!-- Bootstrap / AdminLTE -->
    <link
        rel="stylesheet"
        href="<?= base_url(
            'assets/dist/css/adminlte.min.css'
        ); ?>"
    >


    <!-- SweetAlert -->
    <link
        rel="stylesheet"
        href="<?= base_url(
            'assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css'
        ); ?>"
    >


    <!-- Global Application CSS -->
    <link
        rel="stylesheet"
        href="<?= base_url('assets/css/app.css'); ?>"
    >


    <!-- Page-specific CSS -->
    <?php if (!empty($page_css) && is_array($page_css)): ?>

        <?php foreach ($page_css as $css): ?>

            <?php
            $css = trim((string) $css);

            if ($css === '') {
                continue;
            }

            $css_url = preg_match(
                '#^https?://#i',
                $css
            )
                ? $css
                : base_url($css);
            ?>

            <link
                rel="stylesheet"
                href="<?= html_escape($css_url); ?>"
            >

        <?php endforeach; ?>

    <?php endif; ?>

</head>


<body class="<?= html_escape(
    implode(' ', $body_classes)
); ?>">
<div class="wrapper">