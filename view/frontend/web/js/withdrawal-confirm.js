define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    return function (config, element) {
        $(element).on('submit', function (e) {
            e.preventDefault();
            var form = this;

            confirm({
                title: $.mage.__('Confirm Withdrawal'),
                content: $.mage.__('Are you sure you want to submit the withdrawal for this order? This action cannot be undone.'),
                actions: {
                    confirm: function () {
                        form.submit();
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
