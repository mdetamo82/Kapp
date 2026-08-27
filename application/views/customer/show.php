<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="content-header">

    <div class="container-fluid">

        <div class="row mb-2">

            <div class="col-sm-6">

                <h1 class="m-0">
                    Customer Details
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
                        <?php echo safe_html($customer->name); ?>
                    </li>

                </ol>

            </div>

        </div>

    </div>

</div>


<section class="content">

    <div class="container-fluid">

        <div class="row">

            <div class="col-md-4">

                <div class="card card-primary card-outline">

                    <div class="card-body box-profile text-center">

                        <?php if (!empty($customer->photo_path)): ?>

                            <img
                                src="<?php echo base_url(
                                    'uploads/' . $customer->photo_path
                                ); ?>"
                                alt="<?php echo safe_html($customer->name); ?>"
                                class="profile-user-img img-fluid img-circle"
                                style="width:150px;height:150px;object-fit:cover;"
                            >

                        <?php else: ?>

                            <div
                                class="d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto"
                                style="width:150px;height:150px;"
                            >

                                <i class="fas fa-user fa-3x text-muted"></i>

                            </div>

                        <?php endif; ?>


                        <h3 class="profile-username text-center mt-3">
                            <?php echo safe_html($customer->name); ?>
                        </h3>

                        <?php if (!empty($customer->customer_code)): ?>

                            <p class="text-muted text-center">
                                <?php echo safe_html($customer->customer_code); ?>
                            </p>

                        <?php endif; ?>


                        <ul class="list-group list-group-unbordered mb-3 text-left">

                            <li class="list-group-item">

                                <b>Address</b>

                                <span class="float-right">
                                    <?php echo safe_html(
                                        (string) $customer->address
                                    ); ?>
                                </span>

                            </li>

                            <li class="list-group-item">

                                <b>Phone</b>

                                <span class="float-right">
                                    <?php echo safe_html(
                                        (string) $customer->phone
                                    ); ?>
                                </span>

                            </li>

                            <li class="list-group-item">

                                <b>Created</b>

                                <span class="float-right">
                                    <?php echo safe_html($customer->created_at); ?>
                                </span>

                            </li>

                        </ul>


                        <a
                            href="<?php echo site_url(
                                'customer/id_card/' .
                                (int) $customer->customer_id
                            ); ?>"
                            target="_blank"
                            class="btn btn-primary btn-block"
                        >
                            <i class="fas fa-id-card"></i>
                            Print / Download ID Card
                        </a>


                        <?php if (has_permission('edit_customer')): ?>

                            <a
                                href="<?php echo site_url(
                                    'customer/edit/' .
                                    (int) $customer->customer_id
                                ); ?>"
                                class="btn btn-secondary btn-block mt-2"
                            >
                                <i class="fas fa-edit"></i>
                                Edit Customer
                            </a>

                        <?php endif; ?>


                        <a
                            href="<?php echo site_url('customer'); ?>"
                            class="btn btn-outline-secondary btn-block mt-2"
                        >
                            <i class="fas fa-arrow-left"></i>
                            Back to Customers
                        </a>

                    </div>

                </div>

            </div>


            <div class="col-md-8">

                <div class="card">

                    <div class="card-header">

                        <h3 class="card-title">
                            Identification
                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="row text-center">

                            <div class="col-md-6 mb-4">

                                <h5>QR Code</h5>

                                <?php if (!empty($customer->qr_code_path)): ?>

                                    <img
                                        src="<?php echo base_url(
                                            'uploads/' . $customer->qr_code_path
                                        ); ?>"
                                        alt="QR code"
                                        class="img-thumbnail"
                                        style="max-width:220px;"
                                    >

                                <?php else: ?>

                                    <p class="text-muted">
                                        No QR code has been generated yet.
                                    </p>

                                <?php endif; ?>

                            </div>


                            <div class="col-md-6 mb-4">

                                <h5>Barcode</h5>

                                <?php if (!empty($customer->barcode_path)): ?>

                                    <img
                                        src="<?php echo base_url(
                                            'uploads/' . $customer->barcode_path
                                        ); ?>"
                                        alt="Barcode"
                                        class="img-thumbnail"
                                        style="max-width:220px;"
                                    >

                                <?php else: ?>

                                    <p class="text-muted">
                                        No barcode has been generated yet.
                                    </p>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-12 mt-4">

                <div class="card">

                    <div class="card-header">

                        <h3 class="card-title">
                            Documents
                        </h3>

                    </div>


                    <div class="card-body">

                        <?php if ($this->session->flashdata('success')): ?>

                            <div class="alert alert-success alert-dismissible fade show">

                                <button
                                    type="button"
                                    class="close"
                                    data-dismiss="alert"
                                >
                                    &times;
                                </button>

                                <?php echo safe_html(
                                    $this->session->flashdata('success')
                                ); ?>

                            </div>

                        <?php endif; ?>


                        <?php if ($this->session->flashdata('error')): ?>

                            <div class="alert alert-danger alert-dismissible fade show">

                                <button
                                    type="button"
                                    class="close"
                                    data-dismiss="alert"
                                >
                                    &times;
                                </button>

                                <?php echo safe_html(
                                    $this->session->flashdata('error')
                                ); ?>

                            </div>

                        <?php endif; ?>


                        <?php

                        /*
                         * Small local closure for a human-readable
                         * file size — presentation-only, no reason
                         * for this to live in the model or a global
                         * helper.
                         */
                        $format_bytes = function ($bytes) {

                            $bytes = (int) $bytes;

                            if ($bytes >= 1048576) {
                                return round($bytes / 1048576, 1) . ' MB';
                            }

                            if ($bytes >= 1024) {
                                return round($bytes / 1024, 1) . ' KB';
                            }

                            return $bytes . ' B';
                        };

                        $category_labels = [
                            'invoice'     => 'Invoice',
                            'contract'    => 'Contract',
                            'id_document' => 'ID Document',
                            'other'       => 'Other',
                        ];

                        ?>


                        <?php if (!empty($attachments)): ?>

                            <div class="table-responsive mb-3">

                                <table class="table table-sm table-bordered">

                                    <thead>

                                        <tr>

                                            <th>File</th>
                                            <th style="width:130px;">Category</th>
                                            <th style="width:90px;">Size</th>
                                            <th style="width:150px;">Uploaded</th>
                                            <th style="width:110px;">Actions</th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach ($attachments as $attachment): ?>

                                            <tr>

                                                <td>

                                                    <?php echo safe_html(
                                                        $attachment->original_name
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <span class="badge badge-secondary">
                                                        <?php echo safe_html(
                                                            $category_labels[$attachment->category]
                                                                ?? ucfirst($attachment->category)
                                                        ); ?>
                                                    </span>

                                                </td>


                                                <td>

                                                    <?php echo $format_bytes(
                                                        $attachment->file_size
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?php echo safe_html(
                                                        $attachment->created_at
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <a
                                                        href="<?php echo base_url(
                                                            'uploads/' . $attachment->file_path
                                                        ); ?>"
                                                        download="<?php echo safe_html(
                                                            $attachment->original_name
                                                        ); ?>"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Download"
                                                    >
                                                        <i class="fas fa-download"></i>
                                                    </a>


                                                    <?php if (has_permission('edit_customer')): ?>

                                                        <form
                                                            action="<?php echo site_url(
                                                                'customer/delete_attachment/' .
                                                                (int) $attachment->id
                                                            ); ?>"
                                                            method="post"
                                                            class="d-inline attachment-delete-form"
                                                            data-attachment-name="<?php echo safe_html(
                                                                $attachment->original_name
                                                            ); ?>"
                                                        >

                                                            <?php
                                                            $csrf_name =
                                                                $this->security
                                                                    ->get_csrf_token_name();

                                                            $csrf_hash =
                                                                $this->security
                                                                    ->get_csrf_hash();
                                                            ?>

                                                            <input
                                                                type="hidden"
                                                                name="<?php echo safe_html($csrf_name); ?>"
                                                                value="<?php echo safe_html($csrf_hash); ?>"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="btn btn-sm btn-danger"
                                                                title="Delete"
                                                            >
                                                                <i class="fas fa-trash"></i>
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <p class="text-muted">
                                No documents have been uploaded for this customer yet.
                            </p>

                        <?php endif; ?>


                        <?php if (has_permission('edit_customer')): ?>

                            <hr>

                            <h5>Upload a Document</h5>

                            <?php echo form_open_multipart(
                                'customer/upload_attachment/' .
                                (int) $customer->customer_id
                            ); ?>

                                <div class="form-row align-items-end">

                                    <div class="col-md-5 form-group">

                                        <label for="document">
                                            File
                                        </label>

                                        <input
                                            type="file"
                                            name="document"
                                            id="document"
                                            class="form-control-file"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                            required
                                        >

                                    </div>


                                    <div class="col-md-4 form-group">

                                        <label for="category">
                                            Category
                                        </label>

                                        <select
                                            name="category"
                                            id="category"
                                            class="form-control"
                                        >

                                            <?php foreach (
                                                $attachment_categories as $category
                                            ): ?>

                                                <option value="<?php echo safe_html($category); ?>">
                                                    <?php echo safe_html(
                                                        $category_labels[$category]
                                                            ?? ucfirst($category)
                                                    ); ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="col-md-3 form-group">

                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-block"
                                        >
                                            <i class="fas fa-upload"></i>
                                            Upload
                                        </button>

                                    </div>

                                </div>

                            <?php echo form_close(); ?>

                            <small class="text-muted">
                                Allowed: PDF, Word, Excel, PNG, JPG — max 10 MB.
                            </small>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>
