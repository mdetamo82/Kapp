<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$search = isset($search) ? $search : '';

$page = isset($page)
    ? (int) $page
    : 1;

$per_page = isset($per_page)
    ? (int) $per_page
    : 20;

$offset = isset($offset)
    ? (int) $offset
    : 0;

$total_rows = isset($total_rows)
    ? (int) $total_rows
    : 0;

$total_pages = isset($total_pages)
    ? (int) $total_pages
    : 1;
?>

<div class="content-header">
    <div class="container-fluid">

        <div class="row mb-2">

            <div class="col-sm-6">

                <h1 class="m-0">
                    <?php echo safe_html(
                        $page_title ?? 'Customers'
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

                    <li class="breadcrumb-item active">
                        Customers
                    </li>

                </ol>

            </div>

        </div>

    </div>
</div>


<section class="content">

    <div class="container-fluid">

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


        <div class="card">

            <div class="card-header">

                <h3 class="card-title">
                    Customer Records
                </h3>

                <div class="card-tools">

                    <?php if (has_permission('add_customer')): ?>

                        <a
                            href="<?php echo site_url('customer/create'); ?>"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="fas fa-plus"></i>
                            Add Customer
                        </a>

                    <?php endif; ?>


                    <?php if (has_permission('restore_customer')): ?>

                        <a
                            href="<?php echo site_url('customer/deleted'); ?>"
                            class="btn btn-secondary btn-sm"
                        >
                            <i class="fas fa-trash-restore"></i>
                            Deleted
                        </a>

                    <?php endif; ?>

                </div>

            </div>


            <div class="card-body">

                <form
                    method="get"
                    action="<?php echo site_url('customer'); ?>"
                    class="mb-3"
                >

                    <div class="row">

                        <div class="col-md-9">

                            <label for="customer-search">
                                Search
                            </label>

                            <input
                                type="text"
                                id="customer-search"
                                name="search"
                                class="form-control"
                                value="<?php echo safe_html($search); ?>"
                                placeholder="Search name, address or phone"
                            >

                        </div>


                        <div class="col-md-3">

                            <label>&nbsp;</label>

                            <div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>

                                <a
                                    href="<?php echo site_url('customer'); ?>"
                                    class="btn btn-secondary"
                                >
                                    Reset
                                </a>

                            </div>

                        </div>

                    </div>

                </form>


                <div class="d-flex justify-content-between mb-2">

                    <div class="text-muted">

                        Total records:
                        <strong>
                            <?php echo number_format($total_rows); ?>
                        </strong>

                    </div>


                    <?php if ($total_rows > 0): ?>

                        <div class="text-muted">

                            Page
                            <strong>
                                <?php echo $page; ?>
                            </strong>

                            of

                            <strong>
                                <?php echo $total_pages; ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>

                            <tr>

                                <th style="width:70px;">
                                    #
                                </th>

                                <th style="width:70px;">
                                    Photo
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th style="width:180px;">
                                    Created
                                </th>

                                <th style="width:230px;">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (!empty($customers)): ?>

                            <?php foreach (
                                $customers as $index => $customer
                            ): ?>

                                <tr
                                    data-customer-id="<?php echo (int) $customer->customer_id; ?>"
                                >

                                    <td>

                                        <?php
                                        echo $offset
                                            + $index
                                            + 1;
                                        ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($customer->photo_path)): ?>

                                            <img
                                                src="<?php echo base_url($customer->photo_path); ?>"
                                                alt="<?php echo safe_html($customer->name); ?>"
                                                style="width:40px;height:40px;object-fit:cover;"
                                                class="img-thumbnail"
                                            >

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php echo safe_html(
                                            $customer->name
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php echo safe_html(
                                            (string) $customer->address
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php echo safe_html(
                                            (string) $customer->phone
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php echo safe_html(
                                            $customer->created_at
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            has_permission('view_customer')
                                        ): ?>

                                            <a
                                                href="<?php echo site_url(
                                                    'customer/show/' .
                                                    (int) $customer->customer_id
                                                ); ?>"
                                                class="btn btn-sm btn-primary"
                                                title="View"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            has_permission('edit_customer')
                                        ): ?>

                                            <a
                                                href="<?php echo site_url(
                                                    'customer/edit/' .
                                                    (int) $customer->customer_id
                                                ); ?>"
                                                class="btn btn-sm btn-info"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>

                                        <?php endif; ?>


                                        <?php if (!empty($customer->qr_code_path)): ?>

                                            <a
                                                href="<?php echo base_url(
                                                    'uploads/' . $customer->qr_code_path
                                                ); ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-secondary"
                                                title="View QR code"
                                            >
                                                <i class="fas fa-qrcode"></i>
                                            </a>

                                        <?php endif; ?>


                                        <?php if (!empty($customer->barcode_path)): ?>

                                            <a
                                                href="<?php echo base_url(
                                                    'uploads/' . $customer->barcode_path
                                                ); ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-secondary"
                                                title="View barcode"
                                            >
                                                <i class="fas fa-barcode"></i>
                                            </a>

                                        <?php endif; ?>


                                        <?php if (
                                            has_permission('delete_customer')
                                        ): ?>

                                            <form
                                                action="<?php echo site_url(
                                                    'customer/delete/' .
                                                    (int) $customer->customer_id
                                                ); ?>"
                                                method="post"
                                                class="d-inline customer-delete-form"
                                                data-customer-id="<?php echo (int) $customer->customer_id; ?>"
                                                data-customer-name="<?php echo safe_html($customer->name); ?>"
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

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center text-muted py-4"
                                >
                                    No customer records found.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <?php if ($total_pages > 1): ?>

                    <?php

                    $query = [];

                    if ($search !== '') {
                        $query['search'] = $search;
                    }

                    $query_string = !empty($query)
                        ? '&' . http_build_query($query)
                        : '';

                    $start_page = max(
                        1,
                        $page - 2
                    );

                    $end_page = min(
                        $total_pages,
                        $page + 2
                    );

                    ?>

                    <nav aria-label="Customer pagination">

                        <ul class="pagination justify-content-center">

                            <?php if ($page > 1): ?>

                                <li class="page-item">

                                    <a
                                        class="page-link"
                                        href="<?php echo site_url(
                                            'customer?page=' .
                                            ($page - 1) .
                                            $query_string
                                        ); ?>"
                                    >
                                        Previous
                                    </a>

                                </li>

                            <?php endif; ?>


                            <?php for (
                                $i = $start_page;
                                $i <= $end_page;
                                $i++
                            ): ?>

                                <li
                                    class="page-item <?php echo $i === $page ? 'active' : ''; ?>"
                                >

                                    <a
                                        class="page-link"
                                        href="<?php echo site_url(
                                            'customer?page=' .
                                            $i .
                                            $query_string
                                        ); ?>"
                                    >
                                        <?php echo $i; ?>
                                    </a>

                                </li>

                            <?php endfor; ?>


                            <?php if ($page < $total_pages): ?>

                                <li class="page-item">

                                    <a
                                        class="page-link"
                                        href="<?php echo site_url(
                                            'customer?page=' .
                                            ($page + 1) .
                                            $query_string
                                        ); ?>"
                                    >
                                        Next
                                    </a>

                                </li>

                            <?php endif; ?>

                        </ul>

                    </nav>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>
