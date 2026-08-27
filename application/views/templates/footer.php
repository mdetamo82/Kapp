<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

</div>
<!-- /.content-wrapper -->


<footer class="main-footer">

    <div class="float-right d-none d-sm-inline">

        <b>Version</b>
        <?= html_escape(
            $this->config->item('app_version', 'app')
        ); ?>

    </div>


    <strong>

        &copy; 2017-<?= date('Y'); ?>

        <a
            href="https://ynebat.com"
            target="_blank"
            rel="noopener noreferrer"
        >
            Nebate
        </a>.

    </strong>

    All rights reserved.

</footer>


<aside class="control-sidebar control-sidebar-dark"></aside>


</div>
<!-- /.wrapper -->