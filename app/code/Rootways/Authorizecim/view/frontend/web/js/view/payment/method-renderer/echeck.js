define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators'
], function (
    Component,
    $,
    fullScreenLoader,
    additionalValidators
) {
    'use strict';

    var configAuthorizenet = window.checkoutConfig.payment.rootways_authorizecim_echeck;
    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/payment/echeck',
        },

        /**
         * @returns {Boolean}
         */
        isActive: function() {
            return true;
        },

        getCode: function() {
            return 'rootways_authorizecim_echeck';
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'nameOnAccount',
                    'accountNumber',
                    'routingNumber',
                    'accountType'
                ]);

            return this;
        },

        getData: function() {
            var data = this._super();

             $.extend(
                true,
                data,
                {
                    'additional_data': {
                        'name_on_account': this.nameOnAccount(),
                        'account_number': this.accountNumber(),
                        'routing_number': this.routingNumber(),
                        'account_type': this.accountType()
                    }
                }
            );

            return data;
        },

        /**
         * Get list of account types
         * @returns {Object}
         */
        getAccountTypes: function () {
            return configAuthorizenet.accountTypes;
        },

        /**
         * Get list of available Account Types Vaules
         * @returns {Object}
         */
        getAccountTypesValues: function () {
            return _.map(this.getAccountTypes(), function (value, key) {
                return {
                    'value': key,
                    'label': value
                };
            });
        },

        /**
         * @returns {boolean}
         */
        showNote: function () {
            return configAuthorizenet.topNote != '' &&
                configAuthorizenet.topNote != null;
        },

        getTopNote: function () {
            return configAuthorizenet.topNote;
        },

        /**
         * @returns {boolean}
         */
        isCustLoggedIn: function () {
            return (configAuthorizenet.isCustLoggedIn == false) ? false: true;
        },

        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return $form.validation() && $form.validation('isValid');
        }
    });
});
