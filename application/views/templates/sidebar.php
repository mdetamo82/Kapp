<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$CI =& get_instance();

/*
|--------------------------------------------------------------------------
| Application configuration
|--------------------------------------------------------------------------
*/

$app_title = $CI->config->item('app_title', 'app');
$app_logo  = $CI->config->item('app_logo', 'app');
$app_short = $CI->config->item('app_short_name', 'app');

$current_user = (
    isset($current_user) &&
    is_object($current_user)
)
    ? $current_user
    : null;


/*
|--------------------------------------------------------------------------
| Sidebar menu configuration
|--------------------------------------------------------------------------
*/

$CI->load->config('menu');

$menu_items = $CI->config->item('sidebar_menu');

if (!is_array($menu_items)) {
    $menu_items = [];
}


/*
|--------------------------------------------------------------------------
| Sidebar helper functions
|--------------------------------------------------------------------------
*/

/**
 * Normalize a URI.
 */
if (!function_exists('sidebar_normalize_url')) {
    function sidebar_normalize_url($url)
    {
        return trim((string) $url, '/');
    }
}


/**
 * Check whether a menu URL is currently active.
 */
if (!function_exists('sidebar_is_active')) {
    function sidebar_is_active($url)
    {
        $target = sidebar_normalize_url($url);

        if ($target === '') {
            return false;
        }

        $current = sidebar_normalize_url(uri_string());

        /*
         * Exact match.
         */
        if ($current === $target) {
            return true;
        }

        /*
         * Treat child URLs as active for parent links.
         *
         * Example:
         *
         * admin/users/edit
         *
         * keeps:
         *
         * admin/users
         *
         * active.
         */
        return strpos($current, $target . '/') === 0;
    }
}


/**
 * Determine whether a menu item has children.
 */
if (!function_exists('sidebar_has_children')) {
    function sidebar_has_children($item)
    {
        return (
            is_array($item) &&
            isset($item['children']) &&
            is_array($item['children']) &&
            !empty($item['children'])
        );
    }
}


/**
 * Determine whether a menu item is visible.
 *
 * Parent items do not need their own permission.
 * A parent is visible when at least one child is visible.
 */
if (!function_exists('sidebar_item_visible')) {
    function sidebar_item_visible($item)
    {
        if (!is_array($item)) {
            return false;
        }

        /*
         * Parent/container.
         */
        if (sidebar_has_children($item)) {

            foreach ($item['children'] as $child) {

                if (sidebar_item_visible($child)) {
                    return true;
                }
            }

            return false;
        }

        /*
         * Leaf item.
         *
         * No permission means public menu item.
         */
        $permission = isset($item['permission'])
            ? trim((string) $item['permission'])
            : '';

        if ($permission === '') {
            return true;
        }

        /*
         * Authorization helper.
         */
        return function_exists('has_permission')
            ? has_permission($permission)
            : false;
    }
}


/**
 * Determine whether this item or one of its children is active.
 */
if (!function_exists('sidebar_item_active')) {
    function sidebar_item_active($item)
    {
        if (!is_array($item)) {
            return false;
        }

        /*
         * Direct URL.
         */
        if (
            isset($item['url']) &&
            $item['url'] !== '' &&
            sidebar_is_active($item['url'])
        ) {
            return true;
        }

        /*
         * Child URL.
         */
        if (sidebar_has_children($item)) {

            foreach ($item['children'] as $child) {

                if (
                    sidebar_item_visible($child) &&
                    sidebar_item_active($child)
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}


/**
 * Render sidebar menu recursively.
 */
if (!function_exists('render_sidebar_menu')) {

    function render_sidebar_menu($items)
    {
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {

            if (!is_array($item)) {
                continue;
            }

            /*
             * Do not display unauthorized items.
             */
            if (!sidebar_item_visible($item)) {
                continue;
            }

            $has_children = sidebar_has_children($item);

            $label = isset($item['label'])
                ? (string) $item['label']
                : '';

            $icon = isset($item['icon'])
                ? (string) $item['icon']
                : 'far fa-circle';

            $url = isset($item['url'])
                ? trim((string) $item['url'])
                : '';

            /*
             * Parent without URL is a tree container.
             */
            $href = $url !== ''
                ? site_url($url)
                : '#';

            $active = sidebar_item_active($item);

            /*
             * <li> classes.
             */
            $li_class = 'nav-item';

            if ($has_children) {
                $li_class .= ' has-treeview';

                if ($active) {
                    $li_class .= ' menu-open';
                }
            }

            echo '<li class="' .
                html_escape($li_class) .
                '">';

            /*
             * Link.
             */
            echo '<a href="' .
                html_escape($href) .
                '" class="nav-link';

            if ($active) {
                echo ' active';
            }

            echo '">';

            /*
             * Icon.
             */
            echo '<i class="nav-icon ' .
                html_escape($icon) .
                '"></i>';

            /*
             * Label.
             */
            echo '<p>';

            echo html_escape($label);

            /*
             * Tree arrow.
             */
            if ($has_children) {
                echo '<i class="right fas fa-angle-left"></i>';
            }

            echo '</p>';

            echo '</a>';

            /*
             * Children.
             */
            if ($has_children) {

                echo '<ul class="nav nav-treeview">';

                render_sidebar_menu(
                    $item['children']
                );

                echo '</ul>';
            }

            echo '</li>';
        }
    }
}
?>


<!-- =========================================================
     MAIN SIDEBAR
     ========================================================= -->

<aside class="main-sidebar sidebar-dark-primary elevation-4">


    <!-- Brand -->
    <a
        href="<?= site_url('dashboard'); ?>"
        class="brand-link"
    >

        <img
            src="<?= base_url($app_logo); ?>"
            alt="<?= html_escape($app_title); ?>"
            class="brand-image img-circle elevation-3"
            style="opacity:.8"
        >

        <span class="brand-text font-weight-light">
            <?= html_escape($app_short); ?>
        </span>

    </a>


    <!-- Sidebar -->
    <div class="sidebar">


        <!-- =================================================
             USER PANEL
             ================================================= -->

        <?php if ($current_user !== null): ?>

            <div class="user-panel mt-3 pb-3 mb-3 d-flex">

                <div class="image">

                    <?php
                    $avatar = isset($current_user->avatar)
                        ? $current_user->avatar
                        : '';
                    ?>

                    <?php if ($avatar !== ''): ?>

                        <img
                            src="<?= base_url($avatar); ?>"
                            class="img-circle elevation-2"
                            alt="User"
                        >

                    <?php else: ?>

                        <i class="fas fa-user-circle fa-2x text-light"></i>

                    <?php endif; ?>

                </div>


                <div class="info">

                    <a
                        href="<?= site_url('admin/profile'); ?>"
                        class="d-block"
                    >

                        <?php

                        $first = isset($current_user->first_name)
                            ? $current_user->first_name
                            : '';

                        $last = isset($current_user->last_name)
                            ? $current_user->last_name
                            : '';

                        $name = trim(
                            $first . ' ' . $last
                        );

                        if ($name === '') {

                            $name = isset($current_user->username)
                                ? $current_user->username
                                : 'User';
                        }

                        ?>

                        <?= html_escape($name); ?>

                    </a>

                </div>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SIDEBAR SEARCH
             ================================================= -->

        <div class="form-inline">

            <div
                class="input-group"
                data-widget="sidebar-search"
            >

                <input
                    class="form-control form-control-sidebar"
                    type="search"
                    placeholder="Search"
                    aria-label="Search"
                >

                <div class="input-group-append">

                    <button
                        class="btn btn-sidebar"
                        type="button"
                    >

                        <i class="fas fa-search fa-fw"></i>

                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
             NAVIGATION
             ================================================= -->

        <nav class="mt-2">

            <ul
                class="nav nav-pills nav-sidebar flex-column nav-child-indent"
                data-widget="treeview"
                role="menu"
                data-accordion="false"
            >

                <?php
                render_sidebar_menu($menu_items);
                ?>

            </ul>

        </nav>


    </div>

</aside>


<!-- =========================================================
     CONTENT WRAPPER
     ========================================================= -->

<div class="content-wrapper">
