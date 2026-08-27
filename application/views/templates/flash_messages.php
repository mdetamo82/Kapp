<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$success = $this->session->flashdata('success');
$error   = $this->session->flashdata('error');
$warning = $this->session->flashdata('warning');
$info    = $this->session->flashdata('info');
?>

<script>
$(function () {

    <?php if ($success): ?>

    NebatApp.toast({
        icon: 'success',
        title: <?= json_encode($success); ?>
    });

    <?php endif; ?>


    <?php if ($error): ?>

    NebatApp.toast({
        icon: 'error',
        title: <?= json_encode($error); ?>
    });

    <?php endif; ?>


    <?php if ($warning): ?>

    NebatApp.toast({
        icon: 'warning',
        title: <?= json_encode($warning); ?>
    });

    <?php endif; ?>


    <?php if ($info): ?>

    NebatApp.toast({
        icon: 'info',
        title: <?= json_encode($info); ?>
    });

    <?php endif; ?>

});
</script>