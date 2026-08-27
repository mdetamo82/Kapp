<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo lang('edit_user_heading');?></h3>
    </div>
    
    <p class="card-subtitle ml-3 mt-2"><?php echo lang('edit_user_subheading');?></p>
    
    <div id="infoMessage" class="ml-3"><?php echo $message;?></div>

    <?php echo form_open(uri_string(), ['id' => 'editUserForm']);?>
    <div class="card-body">
        <div class="row">
            <!-- First Name -->
            <div class="form-group col-md-6">
                <label for="first_name"><?php echo lang('edit_user_fname_label', 'first_name');?></label>
                <?php echo form_input($first_name, '', 'class="form-control" id="first_name"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Last Name -->
            <div class="form-group col-md-6">
                <label for="last_name"><?php echo lang('edit_user_lname_label', 'last_name');?></label>
                <?php echo form_input($last_name, '', 'class="form-control" id="last_name"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Company -->
            <div class="form-group col-md-6">
                <label for="company"><?php echo lang('edit_user_company_label', 'company');?></label>
                <?php echo form_input($company, '', 'class="form-control" id="company"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Phone -->
            <div class="form-group col-md-6">
                <label for="phone"><?php echo lang('edit_user_phone_label', 'phone');?></label>
                <?php echo form_input($phone, '', 'class="form-control" id="phone"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Password -->
            <div class="form-group col-md-6">
                <label for="password"><?php echo lang('edit_user_password_label', 'password');?></label>
                <?php echo form_input($password, '', 'class="form-control" id="password" placeholder="'.lang('edit_user_password_placeholder').'"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Password Confirm -->
            <div class="form-group col-md-6">
                <label for="password_confirm"><?php echo lang('edit_user_password_confirm_label', 'password_confirm');?></label>
                <?php echo form_input($password_confirm, '', 'class="form-control" id="password_confirm" placeholder="'.lang('edit_user_password_confirm_placeholder').'"');?>
                <div class="invalid-feedback d-block"></div>
            </div>

            <?php if ($this->ion_auth->is_admin()): ?>
            <!-- Groups -->
            <div class="form-group col-12">
                <h4><?php echo lang('edit_user_groups_heading');?></h4>
                <div class="row">
                    <?php foreach ($groups as $group):?>
                    <div class="col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="groups[]" 
                                   value="<?php echo $group['id'];?>" 
                                   id="group_<?php echo $group['id'];?>"
                                   <?php echo (in_array($group, $currentGroups)) ? 'checked="checked"' : null; ?>>
                            <label class="custom-control-label" for="group_<?php echo $group['id'];?>">
                                <?php echo htmlspecialchars($group['name'],ENT_QUOTES,'UTF-8');?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach?>
                </div>
            </div>
            <?php endif ?>

            <?php echo form_hidden('id', $user->id);?>
            <?php echo form_hidden($csrf); ?>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><?php echo lang('edit_user_submit_btn');?></button>
        <a href="<?php echo site_url('auth'); ?>" class="btn btn-secondary">Cancel</a>
    </div>

    <?php echo form_close(); ?>
</div>

<!-- Scripts -->
<script src="<?= base_url('assets/plugins/jquery-validation/jquery.validate.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/jquery-validation/additional-methods.min.js') ?>"></script>

<script>
$(function () {
    $("#editUserForm").validate({
        rules: {
            first_name: {
                required: true,
                minlength: 2
            },
            last_name: {
                required: true,
                minlength: 2
            },
            password: {
                minlength: 8
            },
            password_confirm: {
                equalTo: "#password"
            }
        },
        messages: {
            first_name: {
                required: "Please enter first name",
                minlength: "Name must be at least 2 characters"
            },
            last_name: {
                required: "Please enter last name",
                minlength: "Name must be at least 2 characters"
            },
            password: {
                minlength: "Password must be at least 8 characters"
            },
            password_confirm: {
                equalTo: "Passwords do not match"
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            error.insertAfter(element);
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