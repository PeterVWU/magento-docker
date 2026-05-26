define([
    "jquery",
    "ko",
    'Magento_Ui/js/modal/alert',
    "mage/translate",
    'mage/validation'
], function(jQuery, ko, alert) {
    "use strict";

    jQuery.widget('mage.rwFormAcceptjs', {

        _create: function() {

            this.form = this.options.formSelector ? jQuery(this.options.formSelector) : this.element;

            this.isSubmitActionAllowed = ko.observable(null);
            this.acceptJsKey = ko.observable(null);
            this.acceptJsValue = ko.observable(null);
            this.creditCardLast4 = ko.observable(null);
            this.creditCardBin = ko.observable(null);
            this.creditCardType = ko.observable(null);

            if (this.options.environment == 'sandbox') {
                require(
                    ['rwAcceptjsSandbox'],
                    this.initAcceptJs.bind(this)
                );
            } else {
                require(
                    ['rwAcceptjs'],
                    this.initAcceptJs.bind(this)
                );
            }
        },

        isActivePaymentMethod: function() {
            // Check the selected method.
            if (this.element) {
                if (typeof window.checkoutConfig !== 'undefined'
                    && typeof window.checkoutConfig.selectedPaymentMethod !== 'undefined'
                    && window.checkoutConfig.selectedPaymentMethod === this.options.method) {
                    return true;
                }

                if (typeof this.form !== 'undefined'
                    && this.form.find('[name="payment[method]"]:checked').val() === this.options.method) {
                    return true;
                }

                if (typeof this.form !== 'undefined'
                    && this.form.find('[name="method"]').val() === this.options.method) {
                    return true;
                }
            }

            return false;
        },

        initAcceptJs: function() {
            window[this.options.method + '_acceptjs_response_fun'] = this.transactionResponseHandler.bind(this);
            window.isReady = true;

            this.form.on('submit', this.handleFormSubmit.bind(this));
        },

        transactionResponseHandler: function (response) {
            if (response.messages.resultCode === 'Error') {
                this.handleResponseError(response);
            } else {
                this.handleResponseSuccess(response);
            }
        },

        handleResponseError: function (response) {
            jQuery('#rootways_authorizecim_option_data_value').val('');
            jQuery('#rootways_authorizecim_option_data_descriptor').val('');

            for (var i = 0; i < response.messages.message.length; i++) {
                alert(jQuery.mage.__('Error processing your order, Error: '+response.messages.message[i].text));
            }
        },

        handleResponseSuccess: function (response) {
            jQuery('#rootways_authorizecim_option_data_value').val(response.opaqueData.dataValue);
            jQuery('#rootways_authorizecim_option_data_descriptor').val(response.opaqueData.dataDescriptor);
            jQuery(this.options.submitSelector).first().trigger('click');
        },

        handleFormSubmit: function(event) {
            if (this.isActivePaymentMethod()) {

                if (jQuery('#rootways_authorizecim_option_data_value').val() != '') {
                    this.form.trigger('realOrder');
                    return;
                }

                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }


                this.sendPaymentInfo();
                this.form.data('preventSave', true);
            }
        },

        /**
         * Send payment info via Accept.js
         */
        sendPaymentInfo: function () {

            var  secureData  =  {}  ,  authData  =  {}  ,  cardData  =  {};

            // Extract the card number and expiration date.
            cardData.cardNumber = jQuery('#'+this.options.method+'_cc_number').val();
            cardData.cardCode = jQuery('#'+this.options.method+'_cc_cid').val();
            cardData.month = jQuery('#'+this.options.method+'_expiration').val();
            cardData.year = jQuery('#'+this.options.method+'_expiration_yr').val().trim();
            secureData.cardData  =  cardData;

            // The Authorize.Net Client Key is used in place of the traditional Transaction Key. The Transaction Key
            // is a shared secret and must never be exposed. The Client Key is a public key suitable for use where
            // someone outside the merchant might see it.

            authData.clientKey  =  this.options.clientKey;
            authData.apiLoginID  = this.options.apiLoginId;
            secureData.authData  =  authData;

            // Pass the card number and expiration date to Accept.js for submission to Authorize.Net.
            Accept.dispatchData(secureData, this.options.method + '_acceptjs_response_fun');
        },
        /**
         * Show the spinner effect on the CC fields while loading.
         */
        startLoadWaiting: function () {
            /*
            this.alreadyProcessing = true;
            this.isSubmitActionAllowed(false);
            this.form.trigger('processStart');
            */
        },

        /**
         * Remove the spinner effect on the CC fields.
         */
        stopLoadWaiting: function (error) {
            /*
            this.alreadyProcessing = false;
            this.onFieldChange();
            this.form.trigger('processStop');

            if (error) {
                alert({content:error});
            }
            */
        }
    });

    return jQuery.mage.rwFormAcceptjs;
});
