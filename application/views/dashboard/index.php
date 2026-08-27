<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<!-- Dashboard Content -->

<section class="content-header">
    <div class="container-fluid">

        <div class="row mb-2">

            <div class="col-sm-6">
                <h1>
                    <?php echo html_escape($page_title ?? 'Dashboard'); ?>
                </h1>
            </div>

        </div>

    </div>
</section>


<section class="content">

    <div class="container-fluid">

        <!--
            Dashboard widgets will be added here.

            Keep this view intentionally minimal until the
            underlying application modules are implemented.
        -->

        <div class="card">

            <div class="card-body">

                <h5 class="card-title">
                    Welcome
                </h5>

                <p class="card-text mb-0">
                    Dashboard is ready.
                </p>

            </div>

        </div>

    </div>

</section>
