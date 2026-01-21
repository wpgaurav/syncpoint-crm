/**
 * Starter CRM Admin Scripts
 *
 * @package StarterCRM
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * SCRM Admin object.
     */
    window.SCRMAdmin = {
        /**
         * Initialize.
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind events.
         */
        bindEvents: function () {
            // Confirm delete actions.
            $(document).on('click', '.scrm-delete', this.confirmDelete);

            // AJAX form submissions.
            $(document).on('submit', '.scrm-ajax-form', this.handleAjaxForm);

            // Cancel sync.
            $(document).on('click', '.scrm-cancel-sync', this.handleCancelSync);

            // Tag color picker.
            $('.scrm-color-picker').wpColorPicker();
        },

        /**
         * Confirm delete action.
         *
         * @param {Event} e Click event.
         * @return {boolean} Whether to proceed.
         */
        confirmDelete: function (e) {
            if (!confirm(scrm.i18n.confirm_delete)) {
                e.preventDefault();
                return false;
            }
            return true;
        },

        /**
         * Handle AJAX form submission.
         *
         * @param {Event} e Submit event.
         */
        handleAjaxForm: function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submit = $form.find('[type="submit"]');
            var originalText = $submit.text();

            $submit.prop('disabled', true).text(scrm.i18n.saving);

            $.ajax({
                url: scrm.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&_wpnonce=' + scrm.nonce,
                success: function (response) {
                    if (response.success) {
                        $submit.text(scrm.i18n.saved);
                        setTimeout(function () {
                            $submit.text(originalText).prop('disabled', false);
                        }, 2000);
                    } else {
                        alert(response.data || scrm.i18n.error);
                        $submit.text(originalText).prop('disabled', false);
                    }
                },
                error: function () {
                    alert(scrm.i18n.error);
                    $submit.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Handle sync cancellation.
         *
         * @param {Event} e Click event.
         */
        handleCancelSync: function (e) {
            e.preventDefault();

            var $button = $(this);
            var logId = $button.data('id');

            if (!confirm(scrm.i18n.confirm_cancel || 'Are you sure you want to cancel this sync?')) {
                return;
            }

            $button.prop('disabled', true).text('...');

            $.ajax({
                url: scrm.ajax_url,
                type: 'POST',
                data: {
                    action: 'scrm_cancel_sync',
                    log_id: logId,
                    nonce: scrm.nonce
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || scrm.i18n.error);
                        $button.prop('disabled', false).text('Cancel');
                    }
                },
                error: function () {
                    alert(scrm.i18n.error);
                    $button.prop('disabled', false).text('Cancel');
                }
            });
        },

        /**
         * Format currency.
         *
         * @param {number} amount Amount.
         * @param {string} currency Currency code.
         * @return {string} Formatted currency.
         */
        formatCurrency: function (amount, currency) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency || 'USD'
            }).format(amount);
        },

        /**
         * Show notification.
         *
         * @param {string} message Message.
         * @param {string} type Type (success, error, warning).
         */
        notify: function (message, type) {
            type = type || 'success';

            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').after($notice);

            // Auto-dismiss after 5 seconds.
            setTimeout(function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        SCRMAdmin.init();
    });

})(jQuery);
