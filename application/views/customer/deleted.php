<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$search = isset($search)
    ? $search
    : '';
?>

<div class="content-header">

    <div class="container-fluid">

        <div class="row mb-2">

            <div class="col-sm-6">

                <h1 class="m-0">
                    Deleted Customers
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
                        Deleted
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
                    Deleted Customer Records
                </h3>

                <div class="card-tools">

                    <a
                        href="<?php echo site_url('customer'); ?>"
                        class="btn btn-sm btn-secondary"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </a>

                </div>

            </div>


            <div class="card-body">

                <form
                    method="get"
                    action="<?php echo site_url('customer/deleted'); ?>"
                    class="mb-3"
                >

                    <div class="row">

                        <div class="col-md-9">

                            <label for="deleted-customer-search">
                                Search
                            </label>

                            <input
                                type="text"
                                id="deleted-customer-search"
                                name="search"
                                class="form-control"
                                value="<?php echo safe_html($search); ?>"
                                placeholder="Search deleted customers"
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
                                    href="<?php echo site_url('customer/deleted'); ?>"
                                    class="btn btn-secondary"
                                >
                                    Reset
                                </a>

                            </div>

                        </div>

                    </div>

                </form>


                <?php if (empty($customers)): ?>

                    <div class="alert alert-info">

                        There are no deleted customer records.

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover">

                            <thead>

                                <tr>

                                    <th style="width:70px;">
                                        ID
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

                                    <th>
                                        Deleted At
                                    </th>

                                    <th style="width:140px;">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php foreach (
                                $customers as $customer
                            ): ?>

                                <tr
                                    data-customer-id="<?php echo (int) $customer->customer_id; ?>"
                                >

                                    <td>

                                        <?php echo (int) $customer->customer_id; ?>

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
                                            $customer->deleted_at
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            has_permission(
                                                'restore_customer'
                                            )
                                        ): ?>

                                            <form
                                                action="<?php echo site_url(
                                                    'customer/restore/' .
                                                    (int) $customer->customer_id
                                                ); ?>"
                                                method="post"
                                                class="d-inline customer-restore-form"
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
                                                    class="btn btn-sm btn-success"
                                                >
                                                    <i class="fas fa-trash-restore"></i>
                                                    Restore
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</section>
