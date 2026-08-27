<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * FIX (bug #3): $field_errors holds manual-check errors (e.g.
 * "name already exists") that form_validation's own rules never
 * saw, so form_error() alone can't surface them. Fall back to
 * form_error() when no manual error was set for a field.
 */
$field_errors = isset($field_errors) ? $field_errors : [];

/*
 * Local closure instead of a named function, since this view file
 * can be included more than once per request lifecycle in some
 * CI3 setups and a named function would fatal on redeclaration.
 */
$field_error_html = function ($field) use ($field_errors) {

    if (!empty($field_errors[$field])) {
        return $field_errors[$field];
    }

    return form_error($field);
};
?>

<div class="content-header">

    <div class="container-fluid">

        <div class="row mb-2">

            <div class="col-sm-6">

                <h1 class="m-0">
                    <?php echo safe_html(
                        $page_title ?? 'Create Customer'
                    ); ?>
                </h1>

            </div>

            <div class="col-sm-6">

                <ol class="breadcrumb float-sm-right">

                    <li class="breadcrumb-item">

                        <a href="<?php echo site_url('dashboard'); ?>">
                            Dashboard
                        </a>

                    </li>

                    <li class="breadcrumb-item">

                        <a href="<?php echo site_url('customer'); ?>">
                            Customers
                        </a>

                    </li>

                    <li class="breadcrumb-item active">
                        Create
                    </li>

                </ol>

            </div>

        </div>

    </div>

</div>


<section class="content">

    <div class="container-fluid">

        <div class="card">

            <div class="card-header">

                <h3 class="card-title">
                    Create Customer
                </h3>

            </div>


            <?php echo form_open_multipart('customer/create'); ?>

                <div class="card-body">

                    <?php if (validation_errors()): ?>

                        <div class="alert alert-danger">

                            <?php echo validation_errors(); ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($field_errors)): ?>

                        <div class="alert alert-danger">

                            <?php foreach ($field_errors as $field_error): ?>

                                <?php if ($field_error): ?>

                                    <?php echo $field_error; ?>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <div class="form-group">

                        <label for="name">
                            Name
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control <?php echo $field_error_html('name') ? 'is-invalid' : ''; ?>"
                            value="<?php echo set_value('name'); ?>"
                            maxlength="150"
                            required
                            autofocus
                        >

                        <?php echo $field_error_html('name'); ?>

                    </div>


                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <textarea
                            name="address"
                            id="address"
                            class="form-control <?php echo $field_error_html('address') ? 'is-invalid' : ''; ?>"
                            rows="3"
                            maxlength="255"
                        ><?php echo set_value('address'); ?></textarea>

                        <?php echo $field_error_html('address'); ?>

                    </div>


                    <div class="form-group">

                        <label for="phone">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control <?php echo $field_error_html('phone') ? 'is-invalid' : ''; ?>"
                            value="<?php echo set_value('phone'); ?>"
                            maxlength="30"
                            autocomplete="tel"
                        >

                        <?php echo $field_error_html('phone'); ?>

                    </div>


                    <div class="form-group">

                        <label for="photo">
                            Photo
                            <span class="text-muted small">(optional)</span>
                        </label>

                        <input
                            type="file"
                            name="photo"
                            id="photo"
                            class="form-control-file <?php echo $field_error_html('photo') ? 'is-invalid' : ''; ?>"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <?php echo $field_error_html('photo'); ?>

                    </div>

                </div>


                <div class="card-footer">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-save"></i>
                        Save
                    </button>

                    <a
                        href="<?php echo site_url('customer'); ?>"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                </div>

            <?php echo form_close(); ?>

        </div>

    </div>

</section>
