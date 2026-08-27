<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo lang('create_user_heading');?></h3>
    </div>
    
    <p class="card-subtitle ml-3 mt-2"><?php echo lang('create_user_subheading');?></p>
    
    <div id="infoMessage" class="ml-3"><?php echo $message;?></div>

    <?php echo form_open("auth/create_user", ['id' => 'userForm']);?>
    <div class="card-body">
        <div class="row">

            <!-- First Name -->
            <div class="form-group col-md-6">
                <label for="first_name"><?php echo lang('create_user_fname_label', 'first_name');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-user"></i></div></div>
                    <?php echo form_input($first_name, '', 'class="form-control" id="first_name"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Last Name -->
            <div class="form-group col-md-6">
                <label for="last_name"><?php echo lang('create_user_lname_label', 'last_name');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-user"></i></div></div>
                    <?php echo form_input($last_name, '', 'class="form-control" id="last_name"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <?php if($identity_column!=='email') { ?>
            <!-- Identity -->
            <div class="form-group col-md-6">
                <label for="identity"><?php echo lang('create_user_identity_label', 'identity');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-id-card"></i></div></div>
                    <?php echo form_input($identity, '', 'class="form-control" id="identity"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
                <?php echo form_error('identity'); ?>
            </div>
            <?php } ?>

            <!-- Company -->
            <div class="form-group col-md-6">
                <label for="company"><?php echo lang('create_user_company_label', 'company');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-building"></i></div></div>
                    <?php echo form_input($company, '', 'class="form-control" id="company"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Email -->
            <div class="form-group col-md-6">
                <label for="email"><?php echo lang('create_user_email_label', 'email');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-envelope"></i></div></div>
                    <?php echo form_input($email, '', 'class="form-control" id="email"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Phone -->
            <div class="form-group col-md-6">
                <label for="phone"><?php echo lang('create_user_phone_label', 'phone');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-phone"></i></div></div>
                    <?php echo form_input($phone, '', 'class="form-control" id="phone"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Password -->
            <div class="form-group col-md-6">
                <label for="password"><?php echo lang('create_user_password_label', 'password');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-lock"></i></div></div>
                    <?php echo form_input($password, '', 'class="form-control" id="password"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

            <!-- Password Confirm -->
            <div class="form-group col-md-6">
                <label for="password_confirm"><?php echo lang('create_user_password_confirm_label', 'password_confirm');?></label>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="fas fa-lock"></i></div></div>
                    <?php echo form_input($password_confirm, '', 'class="form-control" id="password_confirm"');?>
                </div>
                <div class="invalid-feedback d-block"></div>
            </div>

        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><?php echo lang('create_user_submit_btn');?></button>
        <a href="<?php echo site_url('auth'); ?>" class="btn btn-secondary">Cancel</a>
    </div>

    <?php echo form_close(); ?>
</div>

<!-- Scripts -->
<script src="<?= base_url('assets/plugins/jquery-validation/jquery.validate.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/jquery-validation/additional-methods.min.js') ?>"></script>

<style>
.invalid-feedback {
    width: 100%;
    margin-top: 0.25rem;
    font-size: 80%;
    color: #dc3545;
}
</style>

<script>
$(function () {
    $("#userForm").validate({
        rules: {
            first_name: {
                required: true,
                minlength: 2
            },
            last_name: {
                required: true,
                minlength: 2
            },
            <?php if($identity_column!=='email') { ?>
            identity: {
                required: true,
                minlength: 3,
                remote: {
                    url: "<?= site_url('auth/check_identity') ?>",
                    type: "post"
                }
            },
            <?php } ?>
            email: {
                required: true,
                email: true,
                remote: {
                    url: "<?= site_url('auth/check_email') ?>",
                    type: "post"
                }
            },
            password: {
                required: true,
                minlength: 8
            },
            password_confirm: {
                required: true,
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
            <?php if($identity_column!=='email') { ?>
            identity: {
                required: "Please enter username",
                minlength: "Username must be at least 3 characters",
                remote: "Username already exists"
            },
            <?php } ?>
            email: {
                required: "Please enter email",
                email: "Please enter a valid email address",
                remote: "Email already exists"
            },
            password: {
                required: "Please enter password",
                minlength: "Password must be at least 8 characters"
            },
            password_confirm: {
                required: "Please confirm password",
                equalTo: "Passwords do not match"
            }
        },
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            error.insertAfter(element.closest('.input-group'));
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