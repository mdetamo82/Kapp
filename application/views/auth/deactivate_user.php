<div class="card card-danger">
    <div class="card-header">
        <h3 class="card-title"><?php echo lang('deactivate_heading'); ?></h3>
    </div>
    
    <div class="card-body">
        <p class="lead"><?php echo sprintf(lang('deactivate_subheading'), htmlspecialchars($user->{$identity}, ENT_QUOTES, 'UTF-8')); ?></p>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo lang('deactivate_warning'); ?>
        </div>

        <?php echo form_open("auth/deactivate/".$user->id, ['id' => 'deactivateForm']); ?>
        
        <div class="form-group">
            <label class="d-block font-weight-bold mb-3"><?php echo lang('deactivate_confirm_label'); ?></label>
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="confirm-yes" name="confirm" value="yes" class="custom-control-input" checked>
                <label class="custom-control-label text-success" for="confirm-yes">
                    <i class="fas fa-check-circle mr-1"></i>
                    <?php echo lang('deactivate_confirm_y_label'); ?>
                </label>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" id="confirm-no" name="confirm" value="no" class="custom-control-input">
                <label class="custom-control-label text-danger" for="confirm-no">
                    <i class="fas fa-times-circle mr-1"></i>
                    <?php echo lang('deactivate_confirm_n_label'); ?>
                </label>
            </div>
        </div>

        <?php echo form_hidden($csrf); ?>
        <?php echo form_hidden(['id' => $user->id]); ?>

        <div class="card-footer text-right">
            <a href="<?php echo site_url('auth'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> <?php echo lang('cancel_btn'); ?>
            </a>
            <button type="submit" class="btn btn-danger" id="submitBtn">
                <i class="fas fa-user-slash mr-1"></i> <?php echo lang('deactivate_submit_btn'); ?>
            </button>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>


<script>
$(document).ready(function() {
    $('#deactivateForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const isDeactivating = $('input[name="confirm"]:checked').val() === 'yes';
        
        if (isDeactivating) {
            Swal.fire({
                title: '<?php echo lang("deactivate_confirmation_title"); ?>',
                text: '<?php echo lang("deactivate_confirmation_text"); ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?php echo lang("deactivate_confirm_btn"); ?>',
                cancelButtonText: '<?php echo lang("cancel_btn"); ?>',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    $('#submitBtn').html('<i class="fas fa-spinner fa-spin mr-1"></i> <?php echo lang("processing"); ?>');
                    $('#submitBtn').prop('disabled', true);
                    
                    // Submit the form
                    form.submit();
                }
            });
        } else {
            form.submit();
        }
    });
});
</script>