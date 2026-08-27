(function ($) {
    'use strict';

    window.SampleAjax = {

        /**
         * Create sample.
         */
        create: function (data, onSuccess, onError) {

            return AppAjax.post(
                BASE_URL + 'sample/create',
                data,

                function (response) {

                    if (
                        response &&
                        response.success
                    ) {
                        if (
                            typeof onSuccess === 'function'
                        ) {
                            onSuccess(response);
                        }

                        return;
                    }

                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Unable to create sample.'
                            }
                        );
                    }
                },

                function (
                    jqXHR,
                    textStatus,
                    errorThrown,
                    response
                ) {
                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Server error while creating sample.'
                            }
                        );
                    }
                }
            );
        },

        /**
         * Update sample.
         */
        update: function (
            id,
            data,
            onSuccess,
            onError
        ) {

            return AppAjax.post(
                BASE_URL + 'sample/edit/' + id,
                data,

                function (response) {

                    if (
                        response &&
                        response.success
                    ) {
                        if (
                            typeof onSuccess === 'function'
                        ) {
                            onSuccess(response);
                        }

                        return;
                    }

                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Unable to update sample.'
                            }
                        );
                    }
                },

                function (
                    jqXHR,
                    textStatus,
                    errorThrown,
                    response
                ) {
                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Server error while updating sample.'
                            }
                        );
                    }
                }
            );
        },

        /**
         * Soft delete sample.
         */
        delete: function (
            id,
            onSuccess,
            onError
        ) {

            return AppAjax.post(
                BASE_URL + 'sample/delete/' + id,
                {},

                function (response) {

                    if (
                        response &&
                        response.success
                    ) {
                        if (
                            typeof onSuccess === 'function'
                        ) {
                            onSuccess(response);
                        }

                        return;
                    }

                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Unable to delete sample.'
                            }
                        );
                    }
                },

                function (
                    jqXHR,
                    textStatus,
                    errorThrown,
                    response
                ) {
                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Server error while deleting sample.'
                            }
                        );
                    }
                }
            );
        },

        /**
         * Restore sample.
         */
        restore: function (
            id,
            onSuccess,
            onError
        ) {

            return AppAjax.post(
                BASE_URL + 'sample/restore/' + id,
                {},

                function (response) {

                    if (
                        response &&
                        response.success
                    ) {
                        if (
                            typeof onSuccess === 'function'
                        ) {
                            onSuccess(response);
                        }

                        return;
                    }

                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Unable to restore sample.'
                            }
                        );
                    }
                },

                function (
                    jqXHR,
                    textStatus,
                    errorThrown,
                    response
                ) {
                    if (
                        typeof onError === 'function'
                    ) {
                        onError(
                            response || {
                                success: false,
                                message:
                                    'Server error while restoring sample.'
                            }
                        );
                    }
                }
            );
        }
    };

})(jQuery);
