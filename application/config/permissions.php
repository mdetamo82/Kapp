<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Canonical Application Permissions
|--------------------------------------------------------------------------
|
| This file is the SOURCE OF TRUTH for application permissions.
|
| Structure:
|
|     controller/module
|         permission_key => human-readable description
|
| Permission keys must be:
|
| - unique
| - lowercase
| - machine-readable
| - stable
| - used by controllers and menu definitions
|
| IMPORTANT:
|
| This file defines WHAT permissions exist.
|
| It does NOT assign permissions to users or groups.
|
| Group assignment belongs in:
|
|     group_permissions
|
*/

$config['permissions'] = [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    'dashboard' => [

        'view_dashboard' =>
            'View Dashboard',

    ],


    /*
    |--------------------------------------------------------------------------
    | Sample CRUD
    |--------------------------------------------------------------------------
    |
    | Reference implementation for reusable CRUD modules.
    |
    */

    'custemers' => [

        'view_custemers' =>
            'View Sample Records',

        'add_custemers' =>
            'Add Sample Record',

        'edit_custemers' =>
            'Edit Sample Record',

        'delete_custemers' =>
            'Delete Sample Record',
            
        'restore_backup' =>
            'Delete Sample Record',

    ],


    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    'users' => [

        'manage_users' =>
            'Manage Users',

    ],


    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    */

    'roles' => [

        'manage_roles_permissions' =>
            'Manage Roles & Permissions',

    ],


    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [

        'manage_settings' =>
            'Manage Settings',

    ],


    /*
    |--------------------------------------------------------------------------
    | Backup
    |--------------------------------------------------------------------------
    */

    'backup' => [

        'view_backup' =>
            'View Backups',

        'create_backup' =>
            'Create Backup',

        'download_backup' =>
            'Download Backup',

        'delete_backup' =>
            'Delete Backup',

        'restore_backup' =>
            'Restore Backup',

    ],


    /*
    |--------------------------------------------------------------------------
    | Audit / Logs
    |--------------------------------------------------------------------------
    */

    'logs' => [

        'view_logs' =>
            'View Logs / Audit Trail',

    ],

];