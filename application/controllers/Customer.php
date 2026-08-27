<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer Controller
 *
 * Canonical CRUD reference controller.
 *
 * All future simple CRUD modules should follow this controller's
 * architecture.
 *
 * Media integration:
 *
 * - Image_service    — optional profile photo upload/replace,
 *                       with MIME/dimension/size validation.
 * - Upload_service    — used directly here for exists()/delete()
 *                       on the non-image QR/barcode files.
 * - Qr_service        — customer reference QR code.
 * - Barcode_service   — customer reference barcode.
 *
 * All media is optional per customer. Photo/QR/barcode paths and
 * metadata live only in whitelisted Customer_model columns — the
 * model itself never touches the filesystem; every file
 * operation happens here, in the controller, through a service.
 */
class Customer extends MY_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Customer_model', 'customer');
        $this->load->model('Attachment_model', 'attachment');

        $this->load->library([
            'image_service',
            'upload_service',
            'qr_service',
            'barcode_service',
        ]);

        $this->set_validation_error_delimiters();
    }


    /**
     * The attachable_type value this controller uses when
     * reading/writing rows in the shared `attachments` table.
     *
     * @var string
     */
    protected $attachable_type = 'customer';


    /**
     * Allowed attachment file extensions.
     *
     * @var array
     */
    protected $attachment_allowed_types = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'png',
        'jpg',
        'jpeg',
    ];


    /**
     * Maximum attachment size in KB.
     *
     * 10240 KB = 10 MB.
     *
     * @var int
     */
    protected $attachment_max_size = 10240;


    /* ==============================================================
     * INDEX
     * ============================================================== */

    /**
     * Customer list.
     *
     * Supports:
     * - Search
     * - Pagination
     */
    public function index()
    {
        $this->require_permission('view_customer');

        $search = $this->get_string('search');

        $page = (int) $this->input->get('page');

        if ($page < 1) {
            $page = 1;
        }

        $per_page = 20;

        $total_rows = $this->customer->count_all(
            $search
        );

        $total_pages = max(
            1,
            (int) ceil($total_rows / $per_page)
        );

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $offset = ($page - 1) * $per_page;

        $data = [
            'customers'  => $this->customer->get_paginated(
                $search,
                $per_page,
                $offset
            ),
            'search'     => $search,
            'page'       => $page,
            'per_page'   => $per_page,
            'offset'     => $offset,
            'total_rows' => $total_rows,
            'total_pages'=> $total_pages,
        ];

        $this->render(
            'customer/index',
            $data
        );
    }


    /* ==============================================================
     * SHOW (detail page)
     * ============================================================== */

    /**
     * Customer detail page.
     *
     * Displays the full profile: large photo, contact info, QR
     * code, barcode, and a link to the printable ID card.
     *
     * @param mixed $id
     */
    public function show($id = null)
    {
        $this->require_permission('view_customer');

        $customer = $this->find_or_404(
            $this->customer,
            'find',
            $id,
            'Customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $this->render(
            'customer/show',
            [
                'page_title'            => 'Customer Details',
                'customer'              => $customer,
                'attachments'           => $this->attachment->get_for(
                    $this->attachable_type,
                    $customer->customer_id
                ),
                'attachment_categories' => $this->attachment->allowed_categories(),
            ]
        );
    }


    /* ==============================================================
     * ID CARD (printable)
     * ============================================================== */

    /**
     * Printable / downloadable identification card.
     *
     * Deliberately bypasses $this->render() (which wraps pages
     * in the AdminLTE template) — a print layout should not
     * include the app sidebar/topbar. Loaded as a bare view.
     *
     * @param mixed $id
     */
    public function id_card($id = null)
    {
        $this->require_permission('view_customer');

        $customer = $this->find_or_404(
            $this->customer,
            'find',
            $id,
            'Customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $this->load->view(
            'customer/id_card',
            ['customer' => $customer]
        );
    }


    /* ==============================================================
     * ATTACHMENTS
     * ============================================================== */

    /**
     * Upload a document/attachment for a customer.
     *
     * The uploaded file is validated and written to disk BEFORE
     * the DB transaction (filesystem writes aren't covered by
     * $this->db->trans_*()), and cleaned up if the subsequent
     * insert fails — same pattern as the profile photo.
     *
     * @param mixed $customer_id
     */
    public function upload_attachment($customer_id = null)
    {
        $this->require_permission('edit_customer');
        $this->require_post();

        $customer = $this->find_or_404(
            $this->customer,
            'find',
            $customer_id,
            'Customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $customer_id = (int) $customer->customer_id;

        /*
         * Validate the category explicitly (not just via the
         * ENUM at the DB layer) so a bad value fails with a
         * clear message instead of a raw DB error. Defaults to
         * 'other' when omitted, matching the column default.
         */
        $category = $this->post_string('category');

        if ($category === null) {
            $category = 'other';
        }

        if (!in_array(
            $category,
            $this->attachment->allowed_categories(),
            true
        )) {
            $this->error_response(
                'Invalid attachment category.',
                422,
                [],
                'customer/show/' . $customer_id
            );

            return;
        }

        if (!$this->has_uploaded_file('document')) {
            $this->error_response(
                'Please choose a file to upload.',
                422,
                [],
                'customer/show/' . $customer_id
            );

            return;
        }

        /*
         * Generic Upload_service, not Image_service — attachments
         * are documents first (PDF/Office), images second. MIME
         * type validation, extension validation, size limits, and
         * randomized filenames are all enforced inside
         * Upload_service itself.
         */
        $upload_result = $this->upload_service->upload(
            'document',
            [
                'directory'     => 'customers/documents/' . $customer_id,
                'allowed_types' => $this->attachment_allowed_types,
                'max_size'      => $this->attachment_max_size,
            ]
        );

        if (!$upload_result['success']) {
            $this->error_response(
                $upload_result['error'],
                422,
                [],
                'customer/show/' . $customer_id
            );

            return;
        }

        $attachment_data = [
            'attachable_type' => $this->attachable_type,
            'attachable_id'   => $customer_id,
            'file_name'       => $upload_result['filename'],
            'original_name'   => $upload_result['original_name'],
            'file_path'       => $upload_result['relative_path'],
            'file_type'       => $upload_result['mime_type'],
            'file_size'       => $upload_result['size'],
            'category'        => $category,
            'created_by'      => $this->current_user_id(),
        ];

        $attachment_id = $this->run_transactional(
            function () use ($attachment_data) {
                return $this->attachment->insert($attachment_data);
            },
            function ($new_attachment_id) use ($attachment_data, $customer_id) {
                $this->audit(
                    'customer',
                    'attachment_upload',
                    $customer_id,
                    null,
                    array_merge(
                        $attachment_data,
                        ['attachment_id' => $new_attachment_id]
                    )
                );
            },
            'Unable to save the uploaded document.',
            'customer/show/' . $customer_id
        );

        if ($attachment_id === false) {

            /*
             * DB insert failed — the file already hit disk, so
             * clean it up rather than leaving it orphaned.
             */
            $this->upload_service->delete(
                $upload_result['relative_path']
            );

            return;
        }

        $this->success_response(
            'Document uploaded successfully.',
            ['attachment_id' => $attachment_id],
            'customer/show/' . $customer_id
        );
    }


    /**
     * Delete a customer attachment.
     *
     * Hard delete, matching the schema (attachments are not
     * soft-deletable). The DB row is removed first, inside a
     * transaction; the file is only deleted from disk AFTER that
     * transaction commits successfully — this ordering means a
     * failed delete never leaves the DB pointing at a missing
     * file, and a file is only ever removed once we're certain
     * nothing references it anymore.
     *
     * @param mixed $attachment_id
     */
    public function delete_attachment($attachment_id = null)
    {
        $this->require_permission('edit_customer');
        $this->require_post();

        $resolved_id = $this->resolve_id($attachment_id);

        /*
         * Scoped lookup: find_for_type() only returns the row if
         * it actually belongs to a customer. Without this check,
         * this route could be used — by anyone with
         * edit_customer — to delete an attachment belonging to
         * an unrelated module (e.g. an invoice) just by guessing
         * its numeric ID.
         */
        $attachment = $this->attachment->find_for_type(
            $resolved_id,
            $this->attachable_type
        );

        if (!$attachment) {
            $this->not_found('Attachment not found.');
            return;
        }

        $customer_id = (int) $attachment->attachable_id;

        $deleted = $this->run_transactional(
            function () use ($resolved_id) {
                return $this->attachment->delete($resolved_id);
            },
            function () use ($attachment, $customer_id) {
                $this->audit(
                    'customer',
                    'attachment_delete',
                    $customer_id,
                    $attachment,
                    null
                );
            },
            'Unable to delete the document.',
            'customer/show/' . $customer_id
        );

        if ($deleted === false) {
            return;
        }

        $this->upload_service->delete($attachment->file_path);

        $this->success_response(
            'Document deleted successfully.',
            ['attachment_id' => $resolved_id],
            'customer/show/' . $customer_id
        );
    }


    /* ==============================================================
     * CREATE
     * ============================================================== */

    /**
     * Create customer.
     */
    public function create()
    {
        $this->require_permission('add_customer');

        if (!$this->is_post()) {
            $this->render(
                'customer/create',
                ['page_title' => 'Create Customer']
            );

            return;
        }

        $this->require_post();

        $this->set_create_validation_rules();

        $view_data = ['page_title' => 'Create Customer'];

        if (!$this->validate_request(
            ['name', 'address', 'phone'],
            'Please correct the highlighted fields.',
            'customer/create',
            'customer/create',
            $view_data
        )) {
            return;
        }

        $name = $this->post_string('name');
        $address = $this->post_string('address');
        $phone = $this->post_string('phone');

        if (!$this->assert_unique(
            $this->customer->name_exists($name),
            'name',
            'A customer with this name already exists.',
            'customer/create',
            $view_data
        )) {
            return;
        }

        if (!$this->assert_unique(
            $phone !== null && $this->customer->phone_exists($phone),
            'phone',
            'A customer with this phone number already exists.',
            'customer/create',
            $view_data
        )) {
            return;
        }

        /*
         * Photo upload is optional and happens BEFORE the DB
         * transaction, since filesystem writes are not covered
         * by $this->db->trans_*(). Image_service performs MIME
         * type validation, extension validation, size limits,
         * and dimension checks, and Upload_service underneath it
         * always writes with a randomized filename inside a
         * fixed, non-user-controlled directory.
         */
        $photo_result = $this->upload_photo_if_present(
            'customer/create',
            $view_data
        );

        if ($photo_result === false) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $user_id = $this->current_user_id();

        /*
         * Base row only — photo metadata and media codes are
         * written separately via the model's whitelisted
         * update_photo_data() / update_media_codes() once the
         * insert_id is known.
         */
        $data = [
            'name'       => $name,
            'address'    => $address,
            'phone'      => $phone,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $user_id !== null
                ? (int) $user_id
                : null,
        ];

        $customer_id = $this->run_transactional(
            function () use ($data, $photo_result) {

                $new_id = $this->customer->insert($data);

                if ($new_id === false) {
                    return false;
                }

                if ($photo_result !== null) {
                    $photo_saved = $this->customer->update_photo_data(
                        $new_id,
                        $this->photo_columns_from_result($photo_result)
                    );

                    if (!$photo_saved) {
                        return false;
                    }
                }

                if (!$this->store_customer_codes($new_id)) {
                    return false;
                }

                return $new_id;
            },
            function ($new_id) use ($data, $photo_result) {

                $this->audit(
                    'customer',
                    'create',
                    $new_id,
                    null,
                    $data
                );

                if ($photo_result !== null) {
                    $this->audit(
                        'customer',
                        'photo_upload',
                        $new_id,
                        null,
                        [
                            'photo_path'          => $photo_result['relative_path'],
                            'photo_original_name' => $photo_result['original_name'],
                            'photo_size'          => $photo_result['size'],
                        ]
                    );
                }

                $this->audit(
                    'customer',
                    'codes_generate',
                    $new_id,
                    null,
                    ['customer_code' => $this->customer_code($new_id)]
                );
            },
            'Unable to create the customer.',
            'customer/create'
        );

        if ($customer_id === false) {

            /*
             * DB rolled back — clean up the orphaned photo file,
             * since it already hit disk. (QR/barcode file
             * cleanup, if any of those were partially written,
             * is handled inside store_customer_codes() itself.)
             */
            if ($photo_result !== null) {
                $this->upload_service->delete(
                    $photo_result['relative_path']
                );
            }

            return;
        }

        $this->success_response(
            'Customer created successfully.',
            [
                'customer_id' => $customer_id,
            ],
            'customer'
        );
    }


    /* ==============================================================
     * EDIT
     * ============================================================== */

    /**
     * Edit customer.
     *
     * @param mixed $id
     */
    public function edit($id = null)
    {
        $this->require_permission('edit_customer');

        $customer = $this->find_or_404(
            $this->customer,
            'find',
            $id,
            'Customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $id = (int) $customer->customer_id;

        if (!$this->is_post()) {
            $this->render(
                'customer/edit',
                [
                    'page_title' => 'Edit Customer',
                    'customer'   => $customer,
                ]
            );

            return;
        }

        $this->require_post();

        $this->set_edit_validation_rules($id);

        $view_data = [
            'page_title' => 'Edit Customer',
            'customer'   => $customer,
        ];

        if (!$this->validate_request(
            ['name', 'address', 'phone'],
            'Please correct the highlighted fields.',
            'customer/edit/' . $id,
            'customer/edit',
            $view_data
        )) {
            return;
        }

        $name = $this->post_string('name');
        $address = $this->post_string('address');
        $phone = $this->post_string('phone');

        if (!$this->assert_unique(
            $this->customer->name_exists($name, $id),
            'name',
            'A customer with this name already exists.',
            'customer/edit',
            $view_data
        )) {
            return;
        }

        if (!$this->assert_unique(
            $phone !== null && $this->customer->phone_exists($phone, $id),
            'phone',
            'A customer with this phone number already exists.',
            'customer/edit',
            $view_data
        )) {
            return;
        }

        /*
         * Photo replacement is optional and, again, happens
         * BEFORE the transaction. Image_service::replace()
         * uploads + validates the NEW file first and only
         * deletes the OLD file once that succeeds — so a failed
         * upload never destroys the existing photo.
         *
         * KNOWN TRADE-OFF: if the DB transaction below still
         * fails AFTER a successful replace(), the new file gets
         * cleaned up (see $updated === false), but the old file
         * is already gone by that point — replace() doesn't
         * know about the pending DB write. This is an accepted
         * gap given how rarely a post-upload DB write fails;
         * flag it if you need transactional file storage later.
         */
        $photo_result = null;
        $new_photo_uploaded = false;

        if ($this->has_uploaded_file('photo')) {

            $photo_result = $this->image_service->replace(
                'photo',
                'customers/photos',
                $customer->photo_path
            );

            if (!$photo_result['success']) {
                $this->validation_error_response(
                    [
                        'photo' =>
                            '<div class="text-danger small">'
                            . $photo_result['error']
                            . '</div>',
                    ],
                    $photo_result['error'],
                    null,
                    'customer/edit',
                    $view_data
                );

                return;
            }

            $new_photo_uploaded = true;
        }

        /*
         * Regenerate QR/barcode only when required: the encoded
         * value is deterministic (derived from customer_id, not
         * from anything editable), so under normal conditions
         * nothing needs to change here. We only regenerate when
         * the code/paths are missing (legacy row) or the actual
         * files are no longer on disk (e.g. purged externally).
         * This check is read-only and safe to run before the
         * transaction.
         */
        $regenerate_codes = $this->needs_code_regeneration($customer);

        $old_customer = $customer;

        $data = [
            'name'       => $name,
            'address'    => $address,
            'phone'      => $phone,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->current_user_id(),
        ];

        $updated = $this->run_transactional(
            function () use ($id, $data, $photo_result, $regenerate_codes) {

                if (!$this->customer->update($id, $data)) {
                    return false;
                }

                if ($photo_result !== null) {
                    $photo_saved = $this->customer->update_photo_data(
                        $id,
                        $this->photo_columns_from_result($photo_result)
                    );

                    if (!$photo_saved) {
                        return false;
                    }
                }

                if ($regenerate_codes) {
                    if (!$this->store_customer_codes($id)) {
                        return false;
                    }
                }

                return true;
            },
            function () use ($id, $old_customer, $data, $photo_result, $regenerate_codes) {

                $this->audit(
                    'customer',
                    'update',
                    $id,
                    $old_customer,
                    $data
                );

                if ($photo_result !== null) {
                    $this->audit(
                        'customer',
                        'photo_replace',
                        $id,
                        ['photo_path' => $old_customer->photo_path],
                        [
                            'photo_path'          => $photo_result['relative_path'],
                            'photo_original_name' => $photo_result['original_name'],
                            'photo_size'          => $photo_result['size'],
                        ]
                    );
                }

                if ($regenerate_codes) {
                    $this->audit(
                        'customer',
                        'codes_regenerate',
                        $id,
                        [
                            'qr_code_path' => $old_customer->qr_code_path,
                            'barcode_path' => $old_customer->barcode_path,
                        ],
                        ['customer_code' => $this->customer_code($id)]
                    );
                }
            },
            'Unable to update the customer.',
            'customer/edit/' . $id
        );

        if ($updated === false) {

            if ($new_photo_uploaded) {
                $this->upload_service->delete(
                    $photo_result['relative_path']
                );
            }

            return;
        }

        $this->success_response(
            'Customer updated successfully.',
            [
                'customer_id' => $id,
            ],
            'customer'
        );
    }


    /* ==============================================================
     * DELETE
     * ============================================================== */

    /**
     * Soft delete customer.
     *
     * Photo / QR / barcode files are intentionally NOT deleted
     * here — soft delete must remain reversible via restore().
     * Physical cleanup only belongs alongside a hard-delete
     * administrative flow, which is out of scope for this
     * controller.
     *
     * @param mixed $id
     */
    public function delete($id = null)
    {
        $this->require_permission('delete_customer');
        $this->require_post();

        $customer = $this->find_or_404(
            $this->customer,
            'find',
            $id,
            'Customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $id = (int) $customer->customer_id;
        $user_id = $this->current_user_id();

        $deleted = $this->run_transactional(
            function () use ($id, $user_id) {
                return $this->customer->soft_delete($id, $user_id);
            },
            function () use ($id, $customer) {
                $this->audit(
                    'customer',
                    'delete',
                    $id,
                    $customer,
                    ['deleted_at' => date('Y-m-d H:i:s')]
                );
            },
            'Unable to delete the customer.'
        );

        if ($deleted === false) {
            return;
        }

        $this->success_response(
            'Customer deleted successfully.',
            [
                'customer_id' => $id,
            ],
            'customer'
        );
    }


    /* ==============================================================
     * DELETED
     * ============================================================== */

    /**
     * Display deleted customers.
     *
     * Requires 'restore_customer' (not 'view_customer') so this
     * matches the permission the UI already gates the "Deleted"
     * link behind — see customer/index.php.
     */
    public function deleted()
    {
        $this->require_permission('restore_customer');

        $search = $this->get_string('search');

        $data = [
            'customers' => $this->customer->get_deleted(
                $search
            ),
            'search' => $search,
        ];

        $this->render(
            'customer/deleted',
            $data
        );
    }


    /* ==============================================================
     * RESTORE
     * ============================================================== */

    /**
     * Restore deleted customer.
     *
     * @param mixed $id
     */
    public function restore($id = null)
    {
        $this->require_permission('restore_customer');
        $this->require_post();

        $customer = $this->find_or_404(
            $this->customer,
            'find_deleted',
            $id,
            'Deleted customer record not found.'
        );

        if ($customer === null) {
            return;
        }

        $id = (int) $customer->customer_id;

        /*
         * Before restoring, make sure the restored customer
         * does not conflict with an existing active customer.
         * These are conflict (409) responses rather than
         * validation errors, so they stay separate from
         * assert_unique().
         */
        if ($this->customer->name_exists($customer->name)) {
            $this->conflict(
                'The customer cannot be restored because another active customer already uses this name.'
            );

            return;
        }

        if (
            $customer->phone !== null
            && $customer->phone !== ''
            && $this->customer->phone_exists($customer->phone)
        ) {
            $this->conflict(
                'The customer cannot be restored because another active customer already uses this phone number.'
            );

            return;
        }

        $user_id = $this->current_user_id();

        /*
         * If the customer's QR/barcode files were purged while
         * soft-deleted, self-heal on restore rather than leaving
         * a customer with dangling media links.
         */
        $regenerate_codes = $this->needs_code_regeneration($customer);

        $restored = $this->run_transactional(
            function () use ($id, $user_id, $regenerate_codes) {

                if (!$this->customer->restore($id, $user_id)) {
                    return false;
                }

                if ($regenerate_codes) {
                    if (!$this->store_customer_codes($id)) {
                        return false;
                    }
                }

                return true;
            },
            function () use ($id, $customer, $user_id, $regenerate_codes) {

                $this->audit(
                    'customer',
                    'restore',
                    $id,
                    $customer,
                    [
                        'deleted_at' => null,
                        'updated_by' => $user_id,
                    ]
                );

                if ($regenerate_codes) {
                    $this->audit(
                        'customer',
                        'codes_regenerate',
                        $id,
                        [
                            'qr_code_path' => $customer->qr_code_path,
                            'barcode_path' => $customer->barcode_path,
                        ],
                        ['customer_code' => $this->customer_code($id)]
                    );
                }
            },
            'Unable to restore the customer.'
        );

        if ($restored === false) {
            return;
        }

        $this->success_response(
            'Customer restored successfully.',
            [
                'customer_id' => $id,
            ],
            'customer/deleted'
        );
    }


    /* ==============================================================
     * MEDIA (photo / QR / barcode) — CONTROLLER-SIDE HELPERS
     *
     * All filesystem I/O lives here, never in the model.
     * ============================================================== */

    /**
     * Determine whether a file was actually supplied for the
     * given upload field.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function has_uploaded_file($field)
    {
        return isset($_FILES[$field]['name'])
            && $_FILES[$field]['name'] !== '';
    }


    /**
     * Upload the optional 'photo' field during create(), sending
     * a validation error response and returning false if the
     * upload was attempted but failed.
     *
     * @param string $view
     * @param array $view_data
     *
     * @return array|null|false Image_service result array, null
     *                            if no file was supplied, or
     *                            false if an error response has
     *                            already been sent.
     */
    protected function upload_photo_if_present($view, $view_data)
    {
        if (!$this->has_uploaded_file('photo')) {
            return null;
        }

        $photo_result = $this->image_service->upload_profile_photo(
            'photo',
            'customers/photos'
        );

        if (!$photo_result['success']) {
            $this->validation_error_response(
                [
                    'photo' =>
                        '<div class="text-danger small">'
                        . $photo_result['error']
                        . '</div>',
                ],
                $photo_result['error'],
                null,
                $view,
                $view_data
            );

            return false;
        }

        return $photo_result;
    }


    /**
     * Map an Image_service upload/replace result to the column
     * set accepted by Customer_model::update_photo_data().
     *
     * @param array $photo_result
     *
     * @return array
     */
    protected function photo_columns_from_result($photo_result)
    {
        return [
            'photo_path'          => $photo_result['relative_path'],
            'photo_disk'          => 'local',
            'photo_original_name' => $photo_result['original_name'],
            'photo_mime_type'     => $photo_result['mime_type'],
            'photo_size'          => $photo_result['size'],
            'photo_width'         => isset($photo_result['width'])
                ? $photo_result['width']
                : null,
            'photo_height'        => isset($photo_result['height'])
                ? $photo_result['height']
                : null,
        ];
    }


    /**
     * Determine whether a customer's QR code / barcode need to
     * be (re)generated.
     *
     * True when the reference code or either file path is
     * missing, or when a path is set but the file itself is no
     * longer present on disk.
     *
     * @param object $customer
     *
     * @return bool
     */
    protected function needs_code_regeneration($customer)
    {
        if (empty($customer->customer_code)) {
            return true;
        }

        if (
            empty($customer->qr_code_path)
            || !$this->upload_service->exists($customer->qr_code_path)
        ) {
            return true;
        }

        if (
            empty($customer->barcode_path)
            || !$this->upload_service->exists($customer->barcode_path)
        ) {
            return true;
        }

        return false;
    }


    /**
     * Generate and store the QR code + barcode for a customer,
     * then persist their paths on the customer row.
     *
     * Both encode the same deterministic customer_code() value,
     * so they can always be safely regenerated — nothing here is
     * one-way or destructive of anything the user entered.
     *
     * On any failure (generation OR the subsequent DB write),
     * any file that was actually written to disk during this
     * call is cleaned up before returning false, so a failure
     * here never leaves an orphaned QR/barcode image behind.
     *
     * @param int $customer_id
     *
     * @return bool
     */
    protected function store_customer_codes($customer_id)
    {
        $code = $this->customer_code($customer_id);

        $qr_relative = 'customers/qr/' . $customer_id . '.png';
        $barcode_relative = 'customers/barcode/' . $customer_id . '.png';

        $qr_absolute = FCPATH . 'uploads/' . $qr_relative;
        $barcode_absolute = FCPATH . 'uploads/' . $barcode_relative;

        try {

            $this->qr_service->save(
                $code,
                $qr_absolute
            );

            $this->barcode_service->save_png(
                $code,
                $barcode_absolute
            );

        } catch (Exception $e) {

            log_message(
                'error',
                'Customer code generation failed for customer_id '
                . $customer_id . ': ' . $e->getMessage()
            );

            $this->upload_service->delete($qr_relative);
            $this->upload_service->delete($barcode_relative);

            return false;
        }

        $saved = $this->customer->update_media_codes(
            $customer_id,
            [
                'customer_code' => $code,
                'qr_code_path'  => $qr_relative,
                'qr_code_disk'  => 'local',
                'barcode_path'  => $barcode_relative,
                'barcode_disk'  => 'local',
            ]
        );

        if (!$saved) {

            /*
             * DB write failed — nothing in the database points
             * to these files anymore, so remove them rather than
             * leaving orphans.
             */
            $this->upload_service->delete($qr_relative);
            $this->upload_service->delete($barcode_relative);

            return false;
        }

        return true;
    }


    /**
     * Build the deterministic reference code encoded into a
     * customer's QR code and barcode.
     *
     * Never influenced by user input — built purely from the
     * integer customer_id, which also means it's never used to
     * build a filesystem path directly (the *_relative paths
     * above use the raw integer id, not this formatted string).
     *
     * @param int $customer_id
     *
     * @return string
     */
    protected function customer_code($customer_id)
    {
        return 'CUST-' . str_pad(
            (string) $customer_id,
            6,
            '0',
            STR_PAD_LEFT
        );
    }


    /* ==============================================================
     * VALIDATION RULES
     * ============================================================== */

    /**
     * Create validation rules.
     */
    protected function set_create_validation_rules()
    {
        $this->form_validation->reset_validation();

        $this->add_validation_rule(
            'name',
            'Customer Name',
            'trim|required|min_length[2]|max_length[150]'
        );

        $this->add_validation_rule(
            'address',
            'Address',
            'trim|max_length[255]'
        );

        $this->add_validation_rule(
            'phone',
            'Phone',
            'trim|max_length[30]'
        );
    }


    /**
     * Edit validation rules.
     *
     * @param int $id
     */
    protected function set_edit_validation_rules($id)
    {
        $this->form_validation->reset_validation();

        $this->add_validation_rule(
            'name',
            'Customer Name',
            'trim|required|min_length[2]|max_length[150]'
        );

        $this->add_validation_rule(
            'address',
            'Address',
            'trim|max_length[255]'
        );

        $this->add_validation_rule(
            'phone',
            'Phone',
            'trim|max_length[30]'
        );
    }
}
