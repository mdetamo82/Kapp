<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central Authorization Service
 *
 * Enterprise application authorization boundary.
 *
 * Responsibilities:
 *
 * - Determine authentication state
 * - Determine current user
 * - Determine administrator status
 * - Resolve RBAC permissions
 * - Cache authorization data for the request
 * - Check permissions
 * - Enforce permissions
 *
 * Authorization flow:
 *
 * users
 *   ↓
 * users_groups
 *   ↓
 * groups
 *   ↓
 * group_permissions
 *   ↓
 * permissions
 *
 * IMPORTANT:
 *
 * Menu visibility is NOT authorization.
 *
 * Controllers must independently enforce authorization.
 */
class Authorization
{
    /**
     * CodeIgniter instance.
     *
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Cached authentication state.
     *
     * @var bool|null
     */
    protected $authenticated = null;

    /**
     * Cached current user ID.
     *
     * @var int|null
     */
    protected $current_user_id = null;

    /**
     * Cached administrator state.
     *
     * @var bool|null
     */
    protected $administrator = null;

    /**
     * Cached permission map.
     *
     * Example:
     *
     * [
     *     'view_dashboard' => true,
     *     'view_sample'    => true,
     * ]
     *
     * @var array|null
     */
    protected $user_permissions = null;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        /*
         * Authorization requires database access and Ion Auth.
         */
        $this->CI->load->database();
        $this->CI->load->library('ion_auth');
    }


    /**
     * Determine whether the current request is authenticated.
     *
     * @return bool
     */
    public function authenticated()
    {
        if ($this->authenticated !== null) {
            return $this->authenticated;
        }

        $this->authenticated = (bool)
            $this->CI->ion_auth->logged_in();

        return $this->authenticated;
    }


    /**
     * Get current authenticated user ID.
     *
     * @return int|null
     */
    public function user_id()
    {
        if (!$this->authenticated()) {
            return null;
        }

        if ($this->current_user_id !== null) {
            return $this->current_user_id;
        }

        $user_id = $this->CI->ion_auth->get_user_id();

        if (
            $user_id === false ||
            $user_id === null ||
            $user_id === '' ||
            !is_numeric($user_id)
        ) {
            return null;
        }

        $user_id = (int) $user_id;

        if ($user_id <= 0) {
            return null;
        }

        $this->current_user_id = $user_id;

        return $this->current_user_id;
    }


    /**
     * Determine whether the current user belongs to
     * the application's administrator group.
     *
     * The database group named "admin" is the canonical
     * administrator group.
     *
     * @return bool
     */
    public function is_admin()
    {
        if ($this->administrator !== null) {
            return $this->administrator;
        }

        $this->administrator = false;

        $user_id = $this->user_id();

        if ($user_id === null) {
            return false;
        }

        $query = $this->CI->db
            ->select('groups.id')
            ->from('users_groups')
            ->join(
                'groups',
                'groups.id = users_groups.group_id',
                'inner'
            )
            ->where(
                'users_groups.user_id',
                $user_id
            )
            ->where(
                'groups.name',
                'admin'
            )
            ->limit(1)
            ->get();

        if (!$query) {
            return false;
        }

        if ($query->num_rows() > 0) {
            $this->administrator = true;
        }

        return $this->administrator;
    }


    /**
     * Determine whether the current user has a permission.
     *
     * Authorization rules:
     *
     * 1. User must be authenticated.
     * 2. Administrator has unrestricted access.
     * 3. Other users require an explicitly assigned permission.
     *
     * @param string $permission
     * @return bool
     */
    public function can($permission)
    {
        /*
         * Permission must be a non-empty string.
         */
        if (!is_string($permission)) {
            return false;
        }

        $permission = trim($permission);

        if ($permission === '') {
            return false;
        }

        /*
         * Authentication boundary.
         */
        if (!$this->authenticated()) {
            return false;
        }

        /*
         * Administrator bypass.
         *
         * This is intentionally centralized here.
         */
        if ($this->is_admin()) {
            return true;
        }

        /*
         * Resolve the current user's RBAC permissions.
         */
        $permissions = $this->get_user_permissions();

        return isset($permissions[$permission]);
    }


    /**
     * Require a permission.
     *
     * Controllers should call this at every authorization boundary.
     *
     * Example:
     *
     *     $this->authorization
     *         ->require_permission('view_sample');
     *
     * @param string $permission
     * @return void
     */
    public function require_permission($permission)
    {
        if ($this->can($permission)) {
            return;
        }

        /*
         * Always return HTTP 403 for authenticated users
         * who lack authorization.
         *
         * Unauthenticated requests are handled separately
         * by the application's authentication boundary.
         */
        show_error(
            'You do not have permission to perform this action.',
            403,
            'Forbidden'
        );
    }


    /**
     * Resolve all permissions belonging to the current user.
     *
     * Permission resolution:
     *
     * users
     *   ↓
     * users_groups
     *   ↓
     * group_permissions
     *   ↓
     * permissions
     *
     * The result is cached for the current request.
     *
     * @return array
     */
    protected function get_user_permissions()
    {
        if ($this->user_permissions !== null) {
            return $this->user_permissions;
        }

        $this->user_permissions = [];

        $user_id = $this->user_id();

        if ($user_id === null) {
            return $this->user_permissions;
        }

        /*
         * Only load active permission definitions.
         *
         * The permission table itself is the source of truth
         * for actual assigned permission keys.
         */
        $query = $this->CI->db
            ->distinct()
            ->select(
                'permissions.id,
                 permissions.name,
                 permissions.method,
                 permissions.controller'
            )
            ->from('users_groups')
            ->join(
                'group_permissions',
                'group_permissions.group_id = users_groups.group_id',
                'inner'
            )
            ->join(
                'permissions',
                'permissions.id = group_permissions.permission_id',
                'inner'
            )
            ->where(
                'users_groups.user_id',
                $user_id
            )
            ->where(
                'permissions.method IS NOT NULL',
                null,
                false
            )
            ->where(
                'permissions.method !=',
                ''
            )
            ->get();

        if (!$query) {
            return $this->user_permissions;
        }

        foreach ($query->result() as $row) {

            /*
             * Canonical permission key.
             *
             * The method column is the machine-level permission.
             */
            $method = trim((string) $row->method);

            if ($method === '') {
                continue;
            }

            $this->user_permissions[$method] = true;
        }

        return $this->user_permissions;
    }


    /**
     * Return all resolved permissions for the current user.
     *
     * Useful for:
     *
     * - debugging
     * - administration
     * - authorization-aware UI
     *
     * Controllers should normally use can() instead.
     *
     * @return array
     */
    public function permissions()
    {
        if (!$this->authenticated()) {
            return [];
        }

        if ($this->is_admin()) {
            return ['*' => true];
        }

        return $this->get_user_permissions();
    }


    /**
     * Clear authorization cache.
     *
     * This should be called after changing:
     *
     * - user groups
     * - group permissions
     *
     * during the same request.
     *
     * @return void
     */
    public function clear_cache()
    {
        $this->authenticated = null;
        $this->current_user_id = null;
        $this->administrator = null;
        $this->user_permissions = null;
    }
}
