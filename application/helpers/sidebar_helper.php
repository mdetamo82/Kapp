<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sidebar Helper
 *
 * Responsible only for rendering the configured application
 * navigation tree.
 *
 * Authorization itself remains inside Authorization.
 */

/**
 * Normalize a sidebar URL.
 *
 * @param string $url
 * @return string
 */
if (!function_exists('sidebar_normalize_url')) {
    function sidebar_normalize_url($url)
    {
        return trim((string) $url, '/');
    }
}

/**
 * Determine whether the current URI matches a menu URL.
 *
 * @param string $url
 * @return bool
 */
if (!function_exists('sidebar_is_active')) {
    function sidebar_is_active($url)
    {
        $current = sidebar_normalize_url(uri_string());
        $target  = sidebar_normalize_url($url);

        if ($target === '') {
            return false;
        }

        return $current === $target;
    }
}

/**
 * Determine whether a menu item has children.
 *
 * @param array $item
 * @return bool
 */
if (!function_exists('sidebar_has_children')) {
    function sidebar_has_children($item)
    {
        return isset($item['children'])
            && is_array($item['children'])
            && count($item['children']) > 0;
    }
}

/**
 * Determine whether a menu item is visible.
 *
 * Parent items are visible when at least one child is visible.
 *
 * @param array $item
 * @return bool
 */
if (!function_exists('sidebar_item_visible')) {
    function sidebar_item_visible($item)
    {
        if (!is_array($item)) {
            return false;
        }

        /*
         * Parent menu.
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
         * Leaf without permission = public menu item.
         */
        $permission = isset($item['permission'])
            ? trim((string) $item['permission'])
            : '';

        if ($permission === '') {
            return true;
        }

        $CI =& get_instance();

        /*
         * Authorization is the source of truth.
         */
        if (!isset($CI->authorization)) {
            $CI->load->library('authorization');
        }

        return $CI->authorization->can($permission);
    }
}

/**
 * Determine whether an item or descendant is active.
 *
 * @param array $item
 * @return bool
 */
if (!function_exists('sidebar_item_active')) {
    function sidebar_item_active($item)
    {
        if (!is_array($item)) {
            return false;
        }

        if (
            isset($item['url']) &&
            sidebar_is_active($item['url'])
        ) {
            return true;
        }

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
 * Render sidebar menu tree.
 *
 * @param array $items
 * @return void
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
             * Authorization-aware visibility.
             */
            if (!sidebar_item_visible($item)) {
                continue;
            }

            $has_children = sidebar_has_children($item);

            $url = isset($item['url'])
                ? trim((string) $item['url'])
                : '';

            $href = $url !== ''
                ? site_url($url)
                : '#';

            $label = isset($item['label'])
                ? (string) $item['label']
                : '';

            $icon = isset($item['icon'])
                ? (string) $item['icon']
                : 'far fa-circle';

            $is_active = sidebar_item_active($item);

            $li_classes = 'nav-item';

            if ($has_children) {
                $li_classes .= ' has-treeview';

                if ($is_active) {
                    $li_classes .= ' menu-open';
                }
            }

            echo '<li class="' .
                html_escape($li_classes) .
                '">';

            echo '<a href="' .
                html_escape($href) .
                '" class="nav-link';

            if ($is_active) {
                echo ' active';
            }

            echo '">';

            echo '<i class="nav-icon ' .
                html_escape($icon) .
                '"></i>';

            echo '<p>';

            echo html_escape($label);

            if ($has_children) {
                echo '<i class="right fas fa-angle-left"></i>';
            }

            echo '</p>';

            echo '</a>';

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
