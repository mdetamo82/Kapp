<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo lang('create_group_heading');?></h3>
    </div>
    
    <p class="card-subtitle ml-3 mt-2"><?php echo lang('create_group_subheading');?></p>
    
    <div id="infoMessage" class="ml-3"><?php echo $message;?></div>

    <?php echo form_open("auth/create_group", ['id' => 'createGroupForm']);?>
    <div class="card-body">
        <div class="row">
            <!-- Group Name -->
            <div class="form-group col-md-6">
                <label for="group_name"><?php echo lang('create_group_name_label', 'group_name');?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-users"></i></span>
                    </div>
                    <?php echo form_input($group_name, '', 'class="form-control" id="group_name"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Description -->
            <div class="form-group col-md-6">
                <label for="description"><?php echo lang('create_group_desc_label', 'description');?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                    </div>
                    <?php echo form_input($description, '', 'class="form-control" id="description"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><?php echo lang('create_group_submit_btn');?></button>
        <a href="<?php echo site_url('auth'); ?>" class="btn btn-secondary">Cancel</a>
    </div>

    <?php echo form_close(); ?>
</div>

<!-- Scripts -->
<script src="<?= base_url('assets/plugins/jquery-validation/jquery.validate.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/jquery-validation/additional-methods.min.js') ?>"></script>

<script>
$(function () {
    $("#createGroupForm").validate({
        rules: {
            group_name: {
                required: true,
                minlength: 2
            },
            description: {
                required: true,
                minlength: 5
            }
        },
        messages: {
            group_name: {
                required: "Please enter group name",
                minlength: "Group name must be at least 2 characters"
            },
            description: {
                required: "Please enter group description",
                minlength: "Description must be at least 5 characters"
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            error.insertAfter(element.parent());
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
            $(element).closest('.form-group').find('.invalid-feedback').show();
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
            $(element).closest('.form-group').find('.invalid-feedback').hide();
        }
    });
});
</script>