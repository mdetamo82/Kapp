(function ($) {

    'use strict';

    window.NebatApp = window.NebatApp || {};

    /*
     * ---------------------------------------------------------
     * SweetAlert
     * ---------------------------------------------------------
     */

    NebatApp.alert = function (options) {

        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire(options);
    };


    /*
     * ---------------------------------------------------------
     * Toast
     * ---------------------------------------------------------
     */

    NebatApp.toast = function (options) {

        if (typeof Swal === 'undefined') {
            return;
        }

        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        Toast.fire(options);
    };


    /*
     * ---------------------------------------------------------
     * Dark Mode
     * ---------------------------------------------------------
     */

    NebatApp.initDarkMode = function () {

        var toggle = document.getElementById(
            'darkModeToggle'
        );

        if (!toggle) {
            return;
        }

        toggle.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                if (
                    typeof window.NebatAjax === 'undefined'
                ) {
                    window.location.href = toggle.href;
                    return;
                }

                NebatAjax.post(
                    toggle.href,
                    {},
                    function (response) {

                        if (
                            !response ||
                            typeof response.dark_mode === 'undefined'
                        ) {
                            return;
                        }

                        var enabled =
                            !!response.dark_mode;

                        document.body.classList.toggle(
                            'dark-mode',
                            enabled
                        );

                        var icon =
                            toggle.querySelector('i');

                        if (!icon) {
                            return;
                        }

                        icon.classList.toggle(
                            'fa-moon',
                            !enabled
                        );

                        icon.classList.toggle(
                            'fa-sun',
                            enabled
                        );
                    }
                );
            }
        );
    };


    /*
     * ---------------------------------------------------------
     * Flash Messages
     * ---------------------------------------------------------
     */

    NebatApp.initFlashMessages = function () {

        if (typeof Swal === 'undefined') {
            return;
        }

        <?php /* Intentionally handled server-side below. */ ?>

    };


    /*
     * ---------------------------------------------------------
     * Initialization
     * ---------------------------------------------------------
     */

    $(function () {

        NebatApp.initDarkMode();

    });

})(jQuery);