<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attachment Model
 *
 * Polymorphic attachment storage shared across modules via
 * (attachable_type, attachable_id) — e.g. a customer's
 * documents, an invoice's supporting files, etc. all live in
 * this one table.
 *
 * Like Customer_model, this class NEVER touches the filesystem.
 * All file I/O (upload, delete) happens in the controller via
 * Upload_service. This model only ever reads/writes the metadata
 * row: path, original name, size, mime type, category.
 */
class Attachment_model extends CI_Model
{
    /**
     * Database table.
     *
     * @var string
     */
    protected $table = 'attachments';


    /**
     * Primary key.
     *
     * @var string
     */
    protected $primary_key = 'id';


    /**
     * Allowed category values.
     *
     * Single source of truth — mirrors the table's ENUM
     * definition, kept here (not hardcoded per-controller) so
     * validation and the DB constraint can never drift apart.
     *
     * @var array
     */
    protected $categories = [
        'invoice',
        'contract',
        'id_document',
        'other',
    ];


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /* ==============================================================
     * CATEGORIES
     * ============================================================== */

    /**
     * Allowed category values.
     *
     * @return array
     */
    public function allowed_categories()
    {
        return $this->categories;
    }


    /* ==============================================================
     * FIND
     * ============================================================== */

    /**
     * Find an attachment by ID.
     *
     * @param int $id
     *
     * @return object|null
     */
    public function find($id)
    {
        return $this->db
            ->where($this->primary_key, (int) $id)
            ->get($this->table)
            ->row();
    }


    /**
     * Find an attachment by ID, scoped to a specific attachable
     * type.
     *
     * This is the safe lookup a controller should use before
     * deleting/serving a file — it refuses to return a row that
     * doesn't actually belong to the expected parent type, which
     * stops one module's delete route from being used (by ID
     * guessing) to remove another module's attachment.
     *
     * @param int $id
     * @param string $attachable_type
     *
     * @return object|null
     */
    public function find_for_type($id, $attachable_type)
    {
        return $this->db
            ->where($this->primary_key, (int) $id)
            ->where('attachable_type', $attachable_type)
            ->get($this->table)
            ->row();
    }


    /**
     * Get every attachment for a given parent, optionally
     * filtered by category.
     *
     * @param string $attachable_type
     * @param int $attachable_id
     * @param string|null $category
     *
     * @return array
     */
    public function get_for($attachable_type, $attachable_id, $category = null)
    {
        $this->db
            ->where('attachable_type', $attachable_type)
            ->where('attachable_id', (int) $attachable_id);

        if ($category !== null) {
            $this->db->where('category', $category);
        }

        return $this->db
            ->order_by('created_at', 'DESC')
            ->get($this->table)
            ->result();
    }


    /**
     * Count attachments for a given parent.
     *
     * @param string $attachable_type
     * @param int $attachable_id
     *
     * @return int
     */
    public function count_for($attachable_type, $attachable_id)
    {
        return (int) $this->db
            ->where('attachable_type', $attachable_type)
            ->where('attachable_id', (int) $attachable_id)
            ->count_all_results($this->table);
    }


    /* ==============================================================
     * CREATE
     * ============================================================== */

    /**
     * Insert an attachment record.
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
     * DELETE
     * ============================================================== */

    /**
     * Delete an attachment record.
     *
     * Hard delete — attachments are not soft-deletable in this
     * schema. The caller is responsible for deleting the
     * underlying file via Upload_service AFTER this succeeds.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        return (bool) $this->db
            ->where($this->primary_key, (int) $id)
            ->delete($this->table);
    }


    /**
     * Delete every attachment record for a given parent.
     *
     * Intended for use alongside a parent's hard_delete() — e.g.
     * Customer_model::hard_delete() should call this (via the
     * controller) BEFORE removing the customer row, since there
     * is no database-level FK cascading this table for
     * polymorphic parents.
     *
     * Returns the rows that were deleted (not just a bool) so
     * the caller can still clean up their files afterward.
     *
     * @param string $attachable_type
     * @param int $attachable_id
     *
     * @return array The attachment rows that were deleted.
     */
    public function delete_for($attachable_type, $attachable_id)
    {
        $rows = $this->get_for($attachable_type, $attachable_id);

        if (empty($rows)) {
            return [];
        }

        $this->db
            ->where('attachable_type', $attachable_type)
            ->where('attachable_id', (int) $attachable_id)
            ->delete($this->table);

        return $rows;
    }
}
