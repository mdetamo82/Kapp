<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer Model
 *
 * Canonical CRUD model for the application.
 *
 * Responsibilities:
 *
 * - Customer database queries
 * - Search
 * - Pagination
 * - Existence checks
 * - Insert
 * - Update
 * - Soft delete
 * - Restore
 * - Whitelisted photo / QR / barcode column updates
 *
 * This model NEVER touches the filesystem. Photo files, QR
 * images, and barcode images are handled entirely by
 * Image_service / Upload_service / Qr_service / Barcode_service
 * from the controller. This model only ever reads or writes the
 * *paths* and *metadata* those services report back — and even
 * then, only through whitelisted column sets (update_photo_data,
 * update_media_codes), never a raw pass-through of an arbitrary
 * array. That keeps a stray/unexpected key from ever being mass-
 * assigned into an unrelated column.
 *
 * Business authorization and HTTP concerns do NOT belong here.
 */
class Customer_model extends CI_Model
{
    /**
     * Database table.
     *
     * @var string
     */
    protected $table = 'customers';


    /**
     * Primary key.
     *
     * @var string
     */
    protected $primary_key = 'customer_id';


    /**
     * Columns writable via update_photo_data().
     *
     * @var array
     */
    protected $photo_columns = [
        'photo_path',
        'photo_disk',
        'photo_original_name',
        'photo_mime_type',
        'photo_size',
        'photo_width',
        'photo_height',
    ];


    /**
     * Columns writable via update_media_codes().
     *
     * @var array
     */
    protected $media_code_columns = [
        'customer_code',
        'qr_code_path',
        'qr_code_disk',
        'barcode_path',
        'barcode_disk',
    ];


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /* ==============================================================
     * FIND
     * ============================================================== */

    /**
     * Find an active customer.
     *
     * @param int $customer_id
     *
     * @return object|null
     */
    public function find($customer_id)
    {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row();
    }


    /**
     * Find a customer including deleted records.
     *
     * @param int $customer_id
     *
     * @return object|null
     */
    public function find_any($customer_id)
    {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->get($this->table)
            ->row();
    }


    /**
     * Find a deleted customer.
     *
     * @param int $customer_id
     *
     * @return object|null
     */
    public function find_deleted($customer_id)
    {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->get($this->table)
            ->row();
    }


    /**
     * Find a customer by their deterministic reference code.
     *
     * Useful for QR/barcode scan lookups (e.g. a controller
     * endpoint that resolves a scanned "CUST-000123" value back
     * to a record).
     *
     * @param string $customer_code
     *
     * @return object|null
     */
    public function find_by_code($customer_code)
    {
        return $this->db
            ->where('customer_code', $customer_code)
            ->where('deleted_at IS NULL', null, false)
            ->get($this->table)
            ->row();
    }


    /* ==============================================================
     * SEARCH / PAGINATION
     * ============================================================== */

    /**
     * Count active customers.
     *
     * @param string|null $search
     *
     * @return int
     */
    public function count_all($search = null)
    {
        $this->apply_active_query($search);

        return (int) $this->db
            ->count_all_results($this->table);
    }


    /**
     * Get paginated active customers.
     *
     * @param string|null $search
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function get_paginated(
        $search = null,
        $limit = 20,
        $offset = 0
    ) {
        $this->apply_active_query($search);

        return $this->db
            ->order_by('customer_id', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get($this->table)
            ->result();
    }


    /**
     * Apply active customer query.
     *
     * @param string|null $search
     *
     * @return void
     */
    protected function apply_active_query($search = null)
    {
        $this->db->where('deleted_at IS NULL', null, false);

        if ($search !== null && $search !== '') {

            $this->db->group_start();

            $this->db
                ->like('name', $search)
                ->or_like('address', $search)
                ->or_like('phone', $search)
                ->or_like('customer_code', $search);

            $this->db->group_end();
        }
    }


    /**
     * Get deleted customers.
     *
     * @param string|null $search
     *
     * @return array
     */
    public function get_deleted($search = null)
    {
        $this->db
            ->where('deleted_at IS NOT NULL', null, false);

        if ($search !== null && $search !== '') {

            $this->db->group_start();

            $this->db
                ->like('name', $search)
                ->or_like('address', $search)
                ->or_like('phone', $search);

            $this->db->group_end();
        }

        return $this->db
            ->order_by('deleted_at', 'DESC')
            ->get($this->table)
            ->result();
    }


    /* ==============================================================
     * EXISTENCE
     * ============================================================== */

    /**
     * Determine whether an active customer name already exists.
     *
     * @param string $name
     * @param int|null $exclude_id
     *
     * @return bool
     */
    public function name_exists($name, $exclude_id = null)
    {
        $this->db
            ->select($this->primary_key)
            ->where('name', $name)
            ->where('deleted_at IS NULL', null, false);

        if ($exclude_id !== null) {
            $this->db->where(
                $this->primary_key . ' !=',
                (int) $exclude_id
            );
        }

        return $this->db
            ->limit(1)
            ->get($this->table)
            ->num_rows() > 0;
    }


    /**
     * Determine whether an active phone already exists.
     *
     * @param string $phone
     * @param int|null $exclude_id
     *
     * @return bool
     */
    public function phone_exists($phone, $exclude_id = null)
    {
        if ($phone === null || $phone === '') {
            return false;
        }

        $this->db
            ->select($this->primary_key)
            ->where('phone', $phone)
            ->where('deleted_at IS NULL', null, false);

        if ($exclude_id !== null) {
            $this->db->where(
                $this->primary_key . ' !=',
                (int) $exclude_id
            );
        }

        return $this->db
            ->limit(1)
            ->get($this->table)
            ->num_rows() > 0;
    }


    /* ==============================================================
     * CREATE
     * ============================================================== */

    /**
     * Insert a customer.
     *
     * Intentionally accepts only the base customer fields
     * (name/address/phone/audit columns). Photo and media-code
     * columns are written separately via update_photo_data() /
     * update_media_codes(), since those only exist once an
     * insert_id is available.
     *
     * @param array $data
     *
     * @return int|false
     */
    public function insert($data)
    {
        if (!$this->db->insert($this->table, $data)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }


    /* ==============================================================
     * UPDATE
     * ============================================================== */

    /**
     * Update an active customer's base fields.
     *
     * @param int $customer_id
     * @param array $data
     *
     * @return bool
     */
    public function update($customer_id, $data)
    {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }


    /* ==============================================================
     * MEDIA (photo / QR / barcode) — DATABASE ONLY
     *
     * No file I/O happens in this section. These methods just
     * persist the paths/metadata that Image_service, Qr_service,
     * and Barcode_service report back to the controller, and
     * only through an explicit column whitelist.
     * ============================================================== */

    /**
     * Update photo columns for an active customer.
     *
     * Any key in $photo_data that isn't in $photo_columns is
     * silently dropped rather than reaching the query — this is
     * the "safe expose" boundary: callers can pass whatever they
     * have (e.g. a full Image_service result array) without risk
     * of mass-assigning into an unrelated column.
     *
     * @param int $customer_id
     * @param array $photo_data
     *
     * @return bool
     */
    public function update_photo_data($customer_id, array $photo_data)
    {
        $data = array_intersect_key(
            $photo_data,
            array_flip($this->photo_columns)
        );

        if (empty($data)) {
            return true;
        }

        return (bool) $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }


    /**
     * Clear all photo columns for an active customer.
     *
     * Used when a photo is removed without a replacement being
     * uploaded. Does NOT delete the underlying file — that's the
     * controller's job, via Image_service, before calling this.
     *
     * @param int $customer_id
     *
     * @return bool
     */
    public function clear_photo_data($customer_id)
    {
        $data = array_fill_keys(
            $this->photo_columns,
            null
        );

        return (bool) $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }


    /**
     * Update QR/barcode reference columns for an active customer.
     *
     * Same whitelist boundary as update_photo_data().
     *
     * @param int $customer_id
     * @param array $codes
     *
     * @return bool
     */
    public function update_media_codes($customer_id, array $codes)
    {
        $data = array_intersect_key(
            $codes,
            array_flip($this->media_code_columns)
        );

        if (empty($data)) {
            return true;
        }

        return (bool) $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->update($this->table, $data);
    }


    /* ==============================================================
     * SOFT DELETE
     * ============================================================== */

    /**
     * Soft delete a customer.
     *
     * @param int $customer_id
     * @param int|null $deleted_by
     *
     * @return bool
     */
    public function soft_delete(
        $customer_id,
        $deleted_by = null
    ) {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NULL', null, false)
            ->update(
                $this->table,
                [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'deleted_by' => $deleted_by !== null
                        ? (int) $deleted_by
                        : null,
                ]
            );
    }


    /* ==============================================================
     * RESTORE
     * ============================================================== */

    /**
     * Restore a deleted customer.
     *
     * @param int $customer_id
     * @param int|null $updated_by
     *
     * @return bool
     */
    public function restore(
        $customer_id,
        $updated_by = null
    ) {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->where('deleted_at IS NOT NULL', null, false)
            ->update(
                $this->table,
                [
                    'deleted_at' => null,
                    'deleted_by' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $updated_by !== null
                        ? (int) $updated_by
                        : null,
                ]
            );
    }


    /* ==============================================================
     * HARD DELETE
     * ============================================================== */

    /**
     * Permanently delete a customer.
     *
     * This method is intentionally separate from soft_delete().
     *
     * Use only for controlled administrative operations. Note
     * that this does NOT delete the customer's photo/QR/barcode
     * files — the controller must do that via the relevant
     * service BEFORE calling this, using the row's *_path
     * columns while they're still available.
     *
     * @param int $customer_id
     *
     * @return bool
     */
    public function hard_delete($customer_id)
    {
        return $this->db
            ->where($this->primary_key, (int) $customer_id)
            ->delete($this->table);
    }
}
