define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    return function (config, element) {
        const $form = $(element);
        const $selectAll = $('#select-all-items');
        const $checkboxes = $('.withdrawal-item-checkbox');
        const $selectedCount = $('#selected-count');

        // Update count display
        function updateCount() {
            const checkedCount = $checkboxes.filter(':checked').length;
            const totalCount = $checkboxes.length;
            $selectedCount.text('(' + checkedCount + ' ' + $.mage.__('of') + ' ' + totalCount + ' ' + $.mage.__('selected') + ')');
        }

        // Select all functionality
        $selectAll.on('change', function () {
            $checkboxes.prop('checked', this.checked);
            updateCount();
        });

        // Update select-all state when individual checkboxes change
        $checkboxes.on('change', function () {
            const allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
            $selectAll.prop('checked', allChecked);
            updateCount();
        });

        // Initial count
        updateCount();

        // Form submission with validation
        $form.on('submit', function (e) {
            e.preventDefault();
            const self = this;
            const checkedCount = $checkboxes.filter(':checked').length;

            if (checkedCount === 0) {
                alert($.mage.__('Please select at least one item to withdraw.'));
                return false;
            }

            const message = $.mage.__('You are about to withdraw %1 item(s) from this order.').replace('%1', checkedCount) +
                          ' ' + $.mage.__('This action cannot be undone.');

            confirm({
                title: $.mage.__('Confirm Withdrawal'),
                content: message,
                actions: {
                    confirm: function () {
                        self.submit();
                    },
                    cancel: function () {
                        return false;
                    }
                },
                buttons: [{
                    text: $.mage.__('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $.mage.__('Yes, Submit Withdrawal'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        });
    };
});
