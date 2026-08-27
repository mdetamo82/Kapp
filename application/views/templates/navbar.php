<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$current_user = isset($current_user) && is_object($current_user)
    ? $current_user
    : null;
?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    <!-- Left -->
    <ul class="navbar-nav">

        <li class="nav-item">

            <a
                class="nav-link"
                data-widget="pushmenu"
                href="#"
                role="button"
                aria-label="Toggle navigation"
            >
                <i class="fas fa-bars"></i>
            </a>

        </li>

        <li class="nav-item d-none d-sm-inline-block">

            <a
                href="<?= site_url('dashboard'); ?>"
                class="nav-link"
            >
                Home
            </a>

        </li>

    </ul>


    <!-- Right -->
    <ul class="navbar-nav ml-auto">

        <?php if ($current_user !== null): ?>

            <!-- Dark mode -->
            <li class="nav-item">

                <a
                    href="<?= site_url(
                        'theme/toggle_dark_mode'
                    ); ?>"
                    class="nav-link"
                    id="darkModeToggle"
                    title="Toggle Dark Mode"
                    aria-label="Toggle Dark Mode"
                >

                    <i
                        class="fas <?= !empty($dark_mode)
                            ? 'fa-sun'
                            : 'fa-moon'; ?>"
                    ></i>

                </a>

            </li>


            <!-- User -->
            <li class="nav-item dropdown">

                <a
                    class="nav-link"
                    data-toggle="dropdown"
                    href="#"
                    aria-haspopup="true"
                    aria-expanded="false"
                >

                    <i class="fas fa-user mr-1"></i>

                    <?php
                    $username = isset($current_user->username)
                        ? $current_user->username
                        : 'User';
                    ?>

                    <span class="d-none d-md-inline">
                        <?= html_escape($username); ?>
                    </span>

                </a>


                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">

                    <a
                        href="<?= site_url('admin/profile'); ?>"
                        class="dropdown-item"
                    >

                        <i class="fas fa-user mr-2"></i>
                        Profile

                    </a>


                    <div class="dropdown-divider"></div>


                    <a
                        href="<?= site_url('auth/logout'); ?>"
                        class="dropdown-item"
                    >

                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout

                    </a>

                </div>

            </li>

        <?php endif; ?>

    </ul>

</nav>
