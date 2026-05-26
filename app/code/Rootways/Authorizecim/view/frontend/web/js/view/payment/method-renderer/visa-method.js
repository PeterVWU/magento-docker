var globThis = null
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/modal/alert'
], function (
       Component,
        $,
        url,
        quote,
        fullScreenLoader,
        additionalValidators,
        alert
       ) {
    'use strict';

    var paymentConfig = window.checkoutConfig.payment.rootways_authorizecim_option_visa;
    var agreementsConfig = window.checkoutConfig ? window.checkoutConfig.checkoutAgreements : {};
    var agreementsInputPath = '#rootways_authorizecim_option_visa_wrapper div.checkout-agreements input';

    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/payment/visa',
            callid: null,
            encPaymentData: null,
            encKey: null,
            loadIframe: true,
            grandTotalAmount: 0,
        },
        agreements: agreementsConfig.agreements,

        initObservable: function () {
            this._super();
            this.loadHostedForm();
            this.agreementListner();

            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            quote.totals.subscribe(function () {
                if (this.grandTotalAmount !== quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                }
            }.bind(this));

            return this;
        },

        /**
         * @returns {boolean}
         */
        isActive: function() {
            return true;
        },

        getCode: function() {
            return 'rootways_authorizecim_option_visa';
        },

        getData: function () {
            if (this.loadIframe) {
                this.loadHostedForm();
            }
            this.loadIframe = false;

            var data = this._super();

            $.extend(
                true,
                data,
                {
                    'additional_data': {
                        'callid': this.callid,
                        'encPaymentData': this.encPaymentData,
                        'encKey': this.encKey
                    }
                }
            );

            return data;
        },

        agreementValidation: function() {
            if (additionalValidators.validate()) {
                $('.' + this.getCode() + '_iframe_wrapper').removeClass('agreement_missed');
            } else {
                $('.' + this.getCode() + '_iframe_wrapper').addClass('agreement_missed');
            }
        },

        agreementListner: function() {
            var self = this;
            var setAgreeListner = function () {
                if (!jQuery('#' + self.getCode() + '_wrapper .checkout-agreements')[0]) {
                    setTimeout(function(){setAgreeListner();}, 500);
                    return;
                }

                var isValid = true;
                $(agreementsInputPath).each(function (index, element) {
                    element.addEventListener('change', self.agreementValidation.bind(self), false);
                    if (!$.validator.validateSingleElement(element, {
                        errorElement: 'div',
                        hideError: false
                    })) {
                        isValid = false;
                    }
                });
                if (isValid == false) {
                    $('.' + self.getCode() + '_iframe_wrapper').addClass('agreement_missed');
                }
            };
            setAgreeListner();
        },

        loadHostedForm: function() {
            var self = this;
            globThis = self;

            var includeHostedForm = function () {
                if (!jQuery('.v-button')[0]) {
                    setTimeout(function(){includeHostedForm();}, 300);
                    return;
                }

                var visaJsUrl = 'visaCheckoutProduction';
                if (paymentConfig.environment == 'sandbox') {
                    var visaJsUrl = 'visaCheckout';
                }
                self.includeAcceptjs(visaJsUrl, self);

            };
            includeHostedForm();
        },

        includeAcceptjs: function(url, self) {
            require(
                [url],
                self.initAcceptJs.bind(self)
            );
        },

        initAcceptJs: function() {
            window[this.item.method + '_acceptjs_response_fun'] = this.transactionResponseHandler.bind(this);
            window.isReady = true;
        },

        transactionResponseHandler: function (response) {
            //alert('bind response')  ;
        },

        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return $form.validation() && $form.validation('isValid');
        },

        /**
         * @returns {String}
         */
        getVisaSrc: function () {
            if (paymentConfig.environment == 'sandbox') {
                return 'https://sandbox.secure.checkout.visa.com/wallet-services-web/xo/button.png';
            } else {
                return 'https://secure.checkout.visa.com/wallet-services-web/xo/button.png';
            }
        },

        getVisaCheckoutLabelImg: function () {
            return paymentConfig.visaCheckoutLabelImg;
        },

        /**
         * @returns {boolean}
         */
        showNote: function () {
            return paymentConfig.topNote != '' &&
                paymentConfig.topNote != null;
        },

        getTopNote: function () {
            return paymentConfig.topNote;
        },

        /**
         * @returns {String}
         */
        getApikey: function () {
            return paymentConfig.apiKey;
        },

        /**
         * @returns {String}
         */
        getCurrency: function () {
            return paymentConfig.visaCheckoutCurrency;
        },

        /**
         * @returns {String}
         */
        getLocale: function () {
            return paymentConfig.getLocale;
        },

        /**
         * @returns {Number}
         */
        getOrderTotal: function () {
            return this.grandTotalAmount;
        },

        handleErrors: function(msg) {
            this.callid = '';
            this.encPaymentData = '';
            this.encKey = '';

            //this.rwStopLoader();
            alert({
                content: jQuery.mage.__(msg)
            });
        },

        getOrderData: function(visaResult) {
            this.callid = visaResult.callid;
            this.encPaymentData = visaResult.encPaymentData;
            this.encKey = visaResult.encKey;
            this.placeOrder();
        }
    });
});

function onVisaCheckoutReady() {
    V.init({
        apikey: globThis.getApikey(),
        //encryptionKey: "",
        paymentRequest:{
            currencyCode: globThis.getCurrency(),
            subtotal: globThis.getOrderTotal()
        },
        settings: {
            locale: "en_US",
            shipping: {
                acceptedRegions: ["US", "CA"],                      // REMOVE IT.
                collectShipping: "false"
            }
        }
    });

    V.on("payment.success", function(payment) {
        //console.log('Visa Checkout Response = '+payment);
        /*
        var result = JSON.stringify(payment);
        console.log(result);

        var visaResult = JSON.parse(result);
        console.log(visaResult);
        */
        globThis.getOrderData(payment);
    });

    V.on("payment.cancel", function(payment) {
        console.log('VISA Checkout Cancel  = '+JSON.stringify(payment));
        var msg = 'Your transaction has been cancelled.';
        globThis.handleErrors(msg);
    });

    V.on("payment.error", function(payment, error) {
        console.log('VISA Checkout Error  = '+JSON.stringify(error));
        var msg = 'There has been error processing your request, please try again or contact us.';
        globThis.handleErrors(msg);
    });
}
