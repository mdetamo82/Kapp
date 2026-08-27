<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<!-- jQuery -->
<script src="<?= base_url(
    'assets/plugins/jquery/jquery.min.js'
); ?>"></script>


<!-- jQuery UI -->
<script src="<?= base_url(
    'assets/plugins/jquery-ui/jquery-ui.min.js'
); ?>"></script>


<script>
if (typeof $.widget !== 'undefined') {
    $.widget.bridge('uibutton', $.ui.button);
}
</script>


<!-- Bootstrap -->
<script src="<?= base_url(
    'assets/plugins/bootstrap/js/bootstrap.bundle.min.js'
); ?>"></script>


<!-- SweetAlert -->
<script src="<?= base_url(
    'assets/plugins/sweetalert2/sweetalert2.min.js'
); ?>"></script>


<!-- AdminLTE -->
<script src="<?= base_url(
    'assets/dist/js/adminlte.js'
); ?>"></script>


<!-- Application Core -->
<script src="<?= base_url(
    'assets/js/app.js'
); ?>"></script>


<!-- Enterprise AJAX -->
<script src="<?= base_url(
    'assets/js/app-ajax.js'
); ?>"></script>


<!-- Page-specific JavaScript -->
<?php if (!empty($page_js) && is_array($page_js)): ?>

    <?php foreach ($page_js as $js): ?>

        <?php
        $js = trim((string) $js);

        if ($js === '') {
            continue;
        }

        $js_url = preg_match(
            '#^https?://#i',
            $js
        )
            ? $js
            : base_url($js);
        ?>

        <script src="<?= html_escape($js_url); ?>"></script>

    <?php endforeach; ?>

<?php endif; ?>


<!-- Inline page JavaScript -->
<?php if (!empty($inline_js) && is_array($inline_js)): ?>

    <?php foreach ($inline_js as $script): ?>

        <script>
<?= $script . "\n"; ?>
        </script>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>