<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Application Sidebar Menu
|--------------------------------------------------------------------------
|
| This file defines navigation only.
|
| IMPORTANT:
|
| 1. Menu visibility is NOT authorization.
| 2. Controllers must enforce permissions independently.
| 3. Parent menus do not require their own permission.
| 4. Child permissions determine whether a parent is visible.
|
| Permission keys must match application/config/permissions.php.
|
*/

$config['sidebar_menu'] = [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    [
        'label'      => 'Dashboard',
        'url'        => 'dashboard',
        'icon'       => 'fas fa-tachometer-alt',
        'permission' => 'view_dashboard',
    ],


    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    |
    | This is a parent/container menu.
    | It intentionally has NO permission of its own.
    |
    */

    [
        'label' => 'Administration',
        'icon'  => 'fas fa-cogs',

        'children' => [

            /*
            |--------------------------------------------------------------------------
            | User Management
            |--------------------------------------------------------------------------
            */

            [
                'label'      => 'Users',
                'url'        => 'auth',
                'icon'       => 'fas fa-users',
                'permission' => 'manage_users',
            ],

            /*
            |--------------------------------------------------------------------------
            | Roles & Permissions
            |--------------------------------------------------------------------------
            */

            [
                'label'      => 'Roles & Permissions',
                'url'        => 'admin/roles',
                'icon'       => 'fas fa-user-shield',
                'permission' => 'manage_roles_permissions',
            ],

            /*
            |--------------------------------------------------------------------------
            | Application Settings
            |--------------------------------------------------------------------------
            */

            [
                'label'      => 'Settings',
                'url'        => 'admin/settings',
                'icon'       => 'fas fa-sliders-h',
                'permission' => 'manage_settings',
            ],

        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | System
    |--------------------------------------------------------------------------
    |
    | System-level administration functions.
    |
    */

    [
        'label' => 'System',
        'icon'  => 'fas fa-server',

        'children' => [

            /*
            |--------------------------------------------------------------------------
            | Backup & Restore
            |--------------------------------------------------------------------------
            */

            [
                'label'      => 'Backup & Restore',
                'url'        => 'admin/backup',
                'icon'       => 'fas fa-database',
                'permission' => 'restore_backup',
            ],

            /*
            |--------------------------------------------------------------------------
            | Audit Logs
            |--------------------------------------------------------------------------
            */

            [
                'label'      => 'Audit Logs',
                'url'        => 'admin/logs',
                'icon'       => 'fas fa-history',
                'permission' => 'view_logs',
            ],

        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Development / Reference
    |--------------------------------------------------------------------------
    |
    | Sample CRUD is intentionally kept as a reference module.
    | We can remove it later when the reusable CRUD foundation is complete.
    |
    */

    [
        'label' => 'Sample CRUD',
        'icon'  => 'fas fa-flask',

        'children' => [

            [
                'label'      => 'Sample Records',
                'url'        => 'custemers',
                'icon'       => 'fas fa-list',
                'permission' => 'view_custemers',
            ],

            [
                'label'      => 'Add Sample',
                'url'        => 'custemers/create',
                'icon'       => 'fas fa-plus',
                'permission' => 'add_custemers',
            ],

        ],
    ],

];
