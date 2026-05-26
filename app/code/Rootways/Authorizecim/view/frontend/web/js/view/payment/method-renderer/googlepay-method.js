define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/modal/alert'
], function (
    Component,
    $,
    quote,
    fullScreenLoader,
    additionalValidators,
    alert
) {
    'use strict';

    var configAuthorizenetGpay = window.checkoutConfig.payment.rootways_authorizecim_option_googlepay;
    var agreementsConfig = window.checkoutConfig ? window.checkoutConfig.checkoutAgreements : {};
    var agreementsInputPath = '#rootways_authorizecim_option_googlepay_wrapper div.checkout-agreements input';

    /**
     * Define the version of the Google Pay API referenced when creating your
     * configuration
     */
    const baseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0
    };

    /**
     * Card networks supported by your site and your gateway
     */
    const allowedCardNetworks = configAuthorizenetGpay.googlePayCcAvailableCcTypes;

    /**
     * Card authentication methods supported by your site and your gateway
     */
    const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

    const tokenizationSpecification = {
        type: 'PAYMENT_GATEWAY',
        parameters: {
            'gateway': 'authorizenet',
            'gatewayMerchantId': configAuthorizenetGpay.g_id
        }
    };

    const baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: allowedCardAuthMethods,
            allowedCardNetworks: allowedCardNetworks
        }
    };

    const cardPaymentMethod = Object.assign(
        {},
        baseCardPaymentMethod,
        {
            tokenizationSpecification: tokenizationSpecification
        }
    );

    /**
     * An initialized google.payments.api.PaymentsClient object or null if not yet set
     *
     * @see {@link getGooglePaymentsClient}
     */
    let paymentsClient = null;

    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/payment/googlepay',
            grandTotalAmount: 0,
            gToken: null
        },
        agreements: agreementsConfig.agreements,

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();

            var prevAddress;
            quote.billingAddress.subscribe(function (newAddress) {
                if ((newAddress && newAddress.getKey() !== undefined) || prevAddress.getKey() !== undefined) {
                    if (!newAddress ^ !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                        prevAddress = newAddress;
                        if (newAddress) {
                            //self.initializeGPay();
                        }
                    }
                }
            });
            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            quote.totals.subscribe(function () {
                if (this.grandTotalAmount != quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                    //self.initializeGPay();
                }
            }.bind(this));

            return this;
        },

        initObservable: function () {
            this._super();
            this.initializeGPay();
            this.agreementListner();
            return this;
        },

        /**
         * @returns {boolean}
         */
        isActive: function() {
            return true;
        },

        getCode: function() {
            return 'rootways_authorizecim_option_googlepay';
        },

        getData: function () {
            var data = this._super();
            $.extend(
                true,
                data,
                {
                    'additional_data': {
                        'payment_method_nonce': this.gToken
                    }
                }
            );
            return data;
        },

        /**
         * Return image url for the Google Pay mark
         */
        getPaymentMarkSrc: function () {
            return configAuthorizenetGpay.paymentMarkSrc;
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

        getGoogleIsReadyToPayRequest: function() {
            return Object.assign(
                {},
                baseRequest,
                {
                    allowedPaymentMethods: [baseCardPaymentMethod]
                }
            );
        },

        getGooglePaymentsClient: function() {
            if (paymentsClient === null) {
                paymentsClient = new google.payments.api.PaymentsClient({environment: configAuthorizenetGpay.gpay_env});
            }
            return paymentsClient;
        },

        initializeGPay: function() {
            var self = this;
            var initializeGPayButton = function () {
                if (!jQuery('#' + self.getCode() + '_wrapper .rw-gpay-container')[0]) {
                    setTimeout(function(){initializeGPayButton();}, 500);
                    return;
                }

                const paymentsClient = self.getGooglePaymentsClient();
                paymentsClient.isReadyToPay(self.getGoogleIsReadyToPayRequest())
                    .then(function(response) {
                        if (response.result) {
                            self.addGooglePayButton();
                            // @todo prefetch payment data to improve performance after confirming site functionality
                            // prefetchGooglePaymentData();
                        }
                    })
                    .catch(function(err) {
                        // show error in developer console for debugging
                        console.log(err);
                    });
            };
            initializeGPayButton();

        },

        addGooglePayButton: function() {
            var self = this;
            const paymentsClient = self.getGooglePaymentsClient();
            const button =
                paymentsClient.createButton({
                    onClick: self.onGooglePaymentButtonClicked.bind(self),
                    allowedPaymentMethods: [baseCardPaymentMethod]
                });
            document.getElementById('rw-gpay-container').appendChild(button);
        },

        onGooglePaymentButtonClicked: function() {
            var self = this;
            const paymentDataRequest = self.getGooglePaymentDataRequest();
            paymentDataRequest.transactionInfo = self.getGoogleTransactionInfo();

            const paymentsClient = self.getGooglePaymentsClient();
            paymentsClient.loadPaymentData(paymentDataRequest)
                .then(function(paymentData) {
                    // handle the response
                    self.processPayment(paymentData);
                })
                .catch(function(err) {
                    // show error in developer console for debugging
                    console.log(err);
                });
        },

        getGooglePaymentDataRequest: function() {
            var self = this;
            const paymentDataRequest = Object.assign({}, baseRequest);
            paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
            paymentDataRequest.transactionInfo = self.getGoogleTransactionInfo();
            paymentDataRequest.merchantInfo = {
                merchantName: configAuthorizenetGpay.merchant_name
            };
            return paymentDataRequest;
        },

        getGoogleTransactionInfo: function() {
            return {
                countryCode: configAuthorizenetGpay.googlepayMerchantCountryCode,
                currencyCode: configAuthorizenetGpay.googlepayCurrency,
                totalPriceStatus: 'FINAL',
                // set to cart total
                totalPrice: this.grandTotalAmount
            };
        },

        processPayment: function(paymentData) {
             // show returned data in developer console for debugging
            //console.log(paymentData);
            var paymentToken = paymentData.paymentMethodData.tokenizationData.token;
            var encTest = window.btoa(paymentToken);

            this.gToken = encTest;
            this.generateOrder();
        },

        generateOrder: function() {
            this.placeOrder();
        }
    });
});
