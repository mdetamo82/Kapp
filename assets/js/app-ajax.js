(function ($) {

    'use strict';


    /* ==============================================================
     * CSRF
     * ============================================================== */

    window.AppAjax = window.AppAjax || {};


    AppAjax.updateCsrf = function (response, xhr) {

        var name = null;
        var token = null;


        /*
         * JSON response token.
         */
        if (
            response &&
            response.csrf &&
            response.csrf.name &&
            response.csrf.token
        ) {
            name = response.csrf.name;
            token = response.csrf.token;
        }


        /*
         * Response header token.
         */
        var headerToken = null;

        if (xhr) {

            headerToken = xhr.getResponseHeader(
                'X-CSRF-TOKEN'
            );

        }


        if (headerToken) {
            token = headerToken;
        }


        /*
         * Update meta tags.
         */
        if (name) {

            $('meta[name="csrf-name"]')
                .attr('content', name);

        }


        if (token) {

            $('meta[name="csrf-token"]')
                .attr('content', token);

        }


        /*
         * Update every CSRF hidden input.
         *
         * This is especially important when CI3 is configured
         * with CSRF regeneration after every request.
         */
        if (name && token) {

            $('input[name="' + name + '"]')
                .val(token);

        }


        return {
            name: name,
            token: token
        };
    };


    /* ==============================================================
     * REQUEST DATA
     * ============================================================== */

    AppAjax.prepareFormData = function ($form) {

        var data = $form.serialize();

        var csrfName = $('meta[name="csrf-name"]')
            .attr('content');

        var csrfToken = $('meta[name="csrf-token"]')
            .attr('content');


        /*
         * If a fresh meta token exists, make sure the
         * serialized form contains the current token.
         */
        if (csrfName && csrfToken) {

            var params = new URLSearchParams(data);

            params.set(
                csrfName,
                csrfToken
            );

            data = params.toString();

        }


        return data;
    };


    /* ==============================================================
     * RESPONSE MESSAGE
     * ============================================================== */

    AppAjax.responseMessage = function (
        response,
        fallback
    ) {

        if (
            response &&
            response.message
        ) {
            return response.message;
        }

        return fallback;
    };


    /* ==============================================================
     * SUCCESS ALERT
     * ============================================================== */

    AppAjax.success = function (
        title,
        message
    ) {

        if (
            typeof Swal === 'undefined'
        ) {
            alert(message);
            return;
        }


        Swal.fire({

            icon: 'success',

            title: title || 'Success',

            text: message || 'Operation completed successfully.',

            position: 'top-end',

            showConfirmButton: false,

            timer: 1800

        });

    };


    /* ==============================================================
     * ERROR ALERT
     * ============================================================== */

    AppAjax.error = function (
        title,
        message
    ) {

        if (
            typeof Swal === 'undefined'
        ) {
            alert(message);
            return;
        }


        Swal.fire({

            icon: 'error',

            title: title || 'Operation failed',

            text: message || 'The operation could not be completed.'

        });

    };


    /* ==============================================================
     * CONFIRMATION
     * ============================================================== */

    AppAjax.confirm = function (
        title,
        text,
        confirmText
    ) {

        if (
            typeof Swal === 'undefined'
        ) {

            return Promise.resolve(
                window.confirm(text || title)
            );

        }


        return Swal.fire({

            icon: 'warning',

            title: title || 'Are you sure?',

            text: text || 'This action cannot be undone.',

            showCancelButton: true,

            confirmButtonText:
                confirmText || 'Yes',

            cancelButtonText:
                'Cancel',

            reverseButtons: true

        }).then(function (result) {

            return result.isConfirmed;

        });

    };


    /* ==============================================================
     * BUTTON LOADING
     * ============================================================== */

    AppAjax.loadingButton = function (
        $button,
        text
    ) {

        if (!$button || !$button.length) {
            return '';
        }


        var original = $button.html();


        $button
            .prop('disabled', true)
            .html(
                '<i class="fas fa-spinner fa-spin"></i> ' +
                (text || 'Processing...')
            );


        return original;
    };


    AppAjax.restoreButton = function (
        $button,
        html
    ) {

        if (!$button || !$button.length) {
            return;
        }


        $button
            .prop('disabled', false)
            .html(html);

    };


    /* ==============================================================
     * CUSTOMER DELETE
     * ============================================================== */

    $(document).on(
        'submit',
        '.customer-delete-form',
        function (event) {

            event.preventDefault();


            var $form = $(this);

            var $button = $form.find(
                'button[type="submit"]'
            );

            var $row = $form.closest('tr');

            var customerName =
                $form.attr('data-customer-name')
                || 'this customer';


            if ($button.prop('disabled')) {
                return;
            }


            AppAjax.confirm(

                'Delete customer?',

                'Are you sure you want to delete ' +
                customerName +
                '?',

                'Delete'

            ).then(function (confirmed) {

                if (!confirmed) {
                    return;
                }


                var originalHtml =
                    AppAjax.loadingButton(
                        $button,
                        'Deleting...'
                    );


                $.ajax({

                    url: $form.attr('action'),

                    type: 'POST',

                    data: AppAjax.prepareFormData(
                        $form
                    ),

                    dataType: 'json'


                }).done(function (
                    response,
                    textStatus,
                    xhr
                ) {


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    if (
                        !response ||
                        response.success !== true
                    ) {

                        AppAjax.error(
                            'Delete failed',
                            AppAjax.responseMessage(
                                response,
                                'Unable to delete the customer.'
                            )
                        );


                        AppAjax.restoreButton(
                            $button,
                            originalHtml
                        );

                        return;
                    }


                    $row.fadeOut(
                        300,
                        function () {

                            $(this).remove();

                        }
                    );


                    AppAjax.success(
                        'Deleted',
                        response.message ||
                        'Customer deleted successfully.'
                    );


                }).fail(function (
                    xhr
                ) {


                    var response =
                        xhr.responseJSON;


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    AppAjax.error(
                        'Delete failed',
                        AppAjax.responseMessage(
                            response,
                            'The customer could not be deleted.'
                        )
                    );


                    AppAjax.restoreButton(
                        $button,
                        originalHtml
                    );

                });

            });

        }
    );


    /* ==============================================================
     * CUSTOMER RESTORE
     * ============================================================== */

    $(document).on(
        'submit',
        '.customer-restore-form',
        function (event) {

            event.preventDefault();


            var $form = $(this);

            var $button = $form.find(
                'button[type="submit"]'
            );

            var $row = $form.closest('tr');

            var customerName =
                $form.attr('data-customer-name')
                || 'this customer';


            if ($button.prop('disabled')) {
                return;
            }


            AppAjax.confirm(

                'Restore customer?',

                'Are you sure you want to restore ' +
                customerName +
                '?',

                'Restore'

            ).then(function (confirmed) {

                if (!confirmed) {
                    return;
                }


                var originalHtml =
                    AppAjax.loadingButton(
                        $button,
                        'Restoring...'
                    );


                $.ajax({

                    url: $form.attr('action'),

                    type: 'POST',

                    data: AppAjax.prepareFormData(
                        $form
                    ),

                    dataType: 'json'


                }).done(function (
                    response,
                    textStatus,
                    xhr
                ) {


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    if (
                        !response ||
                        response.success !== true
                    ) {

                        AppAjax.error(
                            'Restore failed',
                            AppAjax.responseMessage(
                                response,
                                'Unable to restore the customer.'
                            )
                        );


                        AppAjax.restoreButton(
                            $button,
                            originalHtml
                        );

                        return;
                    }


                    $row.fadeOut(
                        300,
                        function () {

                            $(this).remove();

                            /*
                             * If there are no deleted records
                             * remaining, replace the table.
                             */
                            if (
                                $('.customer-restore-form')
                                    .length === 0
                            ) {

                                $('.table-responsive')
                                    .replaceWith(

                                        '<div class="alert alert-info">' +
                                        'There are no deleted customer records.' +
                                        '</div>'

                                    );

                            }

                        }
                    );


                    AppAjax.success(
                        'Restored',
                        response.message ||
                        'Customer restored successfully.'
                    );


                }).fail(function (
                    xhr
                ) {


                    var response =
                        xhr.responseJSON;


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    AppAjax.error(
                        'Restore failed',
                        AppAjax.responseMessage(
                            response,
                            'The customer could not be restored.'
                        )
                    );


                    AppAjax.restoreButton(
                        $button,
                        originalHtml
                    );

                });

            });

        }
    );


    /* ==============================================================
     * ATTACHMENT DELETE
     * ============================================================== */

    $(document).on(
        'submit',
        '.attachment-delete-form',
        function (event) {

            event.preventDefault();


            var $form = $(this);

            var $button = $form.find(
                'button[type="submit"]'
            );

            var $row = $form.closest('tr');

            var attachmentName =
                $form.attr('data-attachment-name')
                || 'this document';


            if ($button.prop('disabled')) {
                return;
            }


            AppAjax.confirm(

                'Delete document?',

                'Are you sure you want to delete ' +
                attachmentName +
                '? This cannot be undone.',

                'Delete'

            ).then(function (confirmed) {

                if (!confirmed) {
                    return;
                }


                var originalHtml =
                    AppAjax.loadingButton(
                        $button,
                        'Deleting...'
                    );


                $.ajax({

                    url: $form.attr('action'),

                    type: 'POST',

                    data: AppAjax.prepareFormData(
                        $form
                    ),

                    dataType: 'json'


                }).done(function (
                    response,
                    textStatus,
                    xhr
                ) {


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    if (
                        !response ||
                        response.success !== true
                    ) {

                        AppAjax.error(
                            'Delete failed',
                            AppAjax.responseMessage(
                                response,
                                'Unable to delete the document.'
                            )
                        );


                        AppAjax.restoreButton(
                            $button,
                            originalHtml
                        );

                        return;
                    }


                    $row.fadeOut(
                        300,
                        function () {

                            $(this).remove();

                        }
                    );


                    AppAjax.success(
                        'Deleted',
                        response.message ||
                        'Document deleted successfully.'
                    );


                }).fail(function (
                    xhr
                ) {


                    var response =
                        xhr.responseJSON;


                    AppAjax.updateCsrf(
                        response,
                        xhr
                    );


                    AppAjax.error(
                        'Delete failed',
                        AppAjax.responseMessage(
                            response,
                            'The document could not be deleted.'
                        )
                    );


                    AppAjax.restoreButton(
                        $button,
                        originalHtml
                    );

                });

            });

        }
    );

})(jQuery);
