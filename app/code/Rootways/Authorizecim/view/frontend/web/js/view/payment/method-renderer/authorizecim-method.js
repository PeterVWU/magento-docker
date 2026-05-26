define([
    'Magento_Payment/js/view/payment/cc-form',
    'jquery',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Ui/js/modal/alert',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Payment/js/model/credit-card-validation/validator'
    //'Magento_Checkout/js/action/place-order',
], function (
    Component,
    $,
    VaultEnabler,
    fullScreenLoader,
    alert,
    additionalValidators
) {
    'use strict';

    var configAuthorizenet = window.checkoutConfig.payment.rootways_authorizecim_option;
    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/payment/authorizecim',
            cToken: null,
            captchaLoaded: false,
            generateC: 0,
            hostedform_exp_year: null,
            hostedform_exp_month: null,
            hostedform_cc_number_last4: null,
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());
            return this;
        },

        initObservable: function () {
            this._super();
            var self = this;

            if (this.isAcceptjs() && this.isAcceptUIjs() == 0) {
                this.includeAcceptjs('rwAcceptjsSandbox' , 'rwAcceptjs', this);
            }

            if (this.isAcceptUIjs() == 1) {
                var includeAcceptUIForm = function () {
                    if (!jQuery('.rw-authorizecim-payment-method')[0]) {
                        setTimeout(function(){includeAcceptUIForm();}, 500);
                        return;
                    }
                    var button = document.getElementById(self.getCode() + '_submitbtn');
                    //button.className = "AcceptUI action primary checkout";
                    button.setAttribute("data-billingAddressOptions", '{"show":false, "required":false}' );
                    button.setAttribute("data-apiLoginID", self.getApiLoginId());
                    button.setAttribute("data-clientKey", self.getClientKey());
                    button.setAttribute("data-acceptUIFormBtnTxt", configAuthorizenet.enableAcceptUiBtnTxt);
                    button.setAttribute("data-acceptUIFormHeaderTxt", configAuthorizenet.enableAcceptUiHeaderTxt);
                    button.setAttribute("data-responseHandler", self.item.method + '_acceptjs_response_fun');
                    self.includeAcceptjs('rwAcceptUiJsSandbox' , 'rwAcceptUiJs', self);
                };
                includeAcceptUIForm();
            }

            return this;
        },

        getCode: function() {
            return 'rootways_authorizecim_option';
        },

        getData: function() {
            var data = this._super();
            this.vaultEnabler.visitAdditionalData(data);
            if (this.isAcceptjs() || this.isAcceptUIjs()) {
                delete data.additional_data.cc_number;
                $.extend(
                    true,
                    data,
                    {
                        'additional_data': {
                            'data_value': $('#'+this.getCode()+'_data_value').val(),
                            'data_descriptor': $('#'+this.getCode()+'_data_descriptor').val(),
                            'captcha_string': this.cToken
                        }
                    }
                );
                if (this.isAcceptUIjs()) {
                    data['additional_data']['cc_number'] = this.hostedform_cc_number_last4;
                    data['additional_data']['cc_exp_month'] = this.hostedform_exp_month;
                    data['additional_data']['cc_exp_year'] = this.hostedform_exp_year;
                }
            } else {
                $.extend(
                    true,
                    data,
                    {
                        'additional_data': {
                            'captcha_string': this.cToken
                        }
                    }
                );
            }

            return data;
        },

        includeAcceptjs: function(standbox, production, self) {
            if (configAuthorizenet.environment == 'sandbox') {
                require(
                    [standbox],
                    self.initAcceptJs.bind(self)
                );
            } else {
                require(
                    [production],
                    self.initAcceptJs.bind(self)
                );
            }
        },

        ccLogoLocation: function() {
            return configAuthorizenet.cclogolocation;
        },

        initAcceptJs: function() {
            window[this.item.method + '_acceptjs_response_fun'] = this.transactionResponseHandler.bind(this);
            window.isReady = true;
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },

        /**
         * @returns {Boolean}
         */
        IsCcAlwaysSave: function () {
            return configAuthorizenet.ccAlwaysSave
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return configAuthorizenet.ccVaultCode;
        },

        /**
         * @returns {Boolean}
         */
        isAcceptjs: function () {
            return configAuthorizenet.enableAcceptjs;
        },

        /**
         * @returns {Boolean}
         */
        isAcceptUIjs: function() {
            return configAuthorizenet.enableAcceptUijs;
        },

        showNote: function () {
            if (configAuthorizenet.topNote != '' &&
                configAuthorizenet.topNote != null
               ) {
                return true;
            } else {
                return false;
            }
        },

        getTopNote: function () {
            return configAuthorizenet.topNote;
        },

        /**
         * @returns {Boolean}
         */
        isCaptchaEnabled: function() {
            return configAuthorizenet.iscaptchaenable;
        },

        /**
         * @returns {String}
         */
        cSiteKey: function() {
            return configAuthorizenet.captchasitekey;
        },

        /**
         * @returns {String}
         */
        getClientKey: function() {
            return configAuthorizenet.apiClientKey;
        },

        /**
         * @returns {String}
         */
        getApiLoginId: function() {
            return configAuthorizenet.apiLoginId;
        },

        /**
         * @returns {Boolean}
         */
        isActive: function() {
            return true;
        },

        /**
         * @returns {Boolean}
         */
        isCustLoggedIn: function () {
            return (configAuthorizenet.isCustLoggedIn == false) ? false: true;
        },

        validate: function() {
            var $form = $('#' + this.getCode() + '-form');
            return $form.validation() && $form.validation('isValid');
        },

        getCcMonths: function() {
            return configAuthorizenet.ccMonths;
        },

        getCcYears: function() {
            return configAuthorizenet.ccYears;
        },

        getCcAvailableTypes: function() {
            return configAuthorizenet.availableCardTypes;
        },

        getCcMonthsValues: function() {
            return _.map(this.getCcMonths(), function(value, key) {
                return {
                    'value': key,
                    'month': value
                }
            });
        },

        getCcYearsValues: function() {
            return _.map(this.getCcYears(), function(value, key) {
                return {
                    'value': key,
                    'year': value
                }
            });
        },

        getCcAvailableTypesValues: function() {
            return _.map(this.getCcAvailableTypes(), function(value, key) {
                return {
                    'value': key,
                    'type': value
                }
            });
        },

        transactionResponseHandler: function (response) {
            if (response.messages.resultCode === 'Error') {
                this.handleResponseError(response);
            } else {
                this.handleResponseSuccess(response);
            }
        },

        handleResponseError: function (response) {
            this.clearAcceptJSData.bind(response);

            var errorMsg = 'Error processing your order';
            for (var i = 0; i < response.messages.message.length; i++) {
                if (typeof response.messages.message[i].text !== 'undefined') {
                    errorMsg = 'Error processing your order, Error: '+response.messages.message[i].text;
                }
            }
            alert({
                title: $.mage.__('Error'),
                content: $.mage.__(errorMsg),
                actions: {
                    always: function() {
                        //self.loadHostedForm();
                    }
                }
            });
        },

        handleResponseSuccess: function (response) {
            if (this.isAcceptUIjs() && typeof response.encryptedCardData !== 'undefined') {
                if (typeof response.encryptedCardData.cardNumber !== 'undefined') {
                    this.hostedform_cc_number_last4 = response.encryptedCardData.cardNumber;
                }
                if (typeof response.encryptedCardData.expDate !== 'undefined') {
                    var expDate = response.encryptedCardData.expDate.split("/");
                    this.hostedform_exp_month = expDate[0];
                    this.hostedform_exp_year = expDate[1];
                }
            }

            jQuery('#'+this.getCode()+'_data_value').val(response.opaqueData.dataValue);
            jQuery('#'+this.getCode()+'_data_descriptor').val(response.opaqueData.dataDescriptor);

            this.placeOrder();
        },

        getPlaceOrderDeferredObject: function () {
            return this._super()
                       .fail(this.clearAcceptJSData.bind(this));
        },

        clearAcceptJSData: function (response) {
            jQuery('#'+this.getCode()+'_data_value').val('');
            jQuery('#'+this.getCode()+'_data_descriptor').val('');
        },

        acceptJSCaller: function() {
            var  secureData  =  {}  ,  authData  =  {}  ,  cardData  =  {};
            cardData.cardNumber  =   $('#'+this.getCode()+'_cc_number').val();
            cardData.cardCode = $('#'+this.getCode()+'_cc_cid').val();
            cardData.month  =  $('#'+this.getCode()+'_expiration').val();
            cardData.year  =  $('#'+this.getCode()+'_expiration_yr').val();
            secureData.cardData  =  cardData;

            authData.clientKey  =  this.getClientKey();
            authData.apiLoginID  =  this.getApiLoginId();
            secureData.authData  =  authData;

            Accept.dispatchData(secureData, this.item.method + '_acceptjs_response_fun');
        },

        placeOrder: function (data, event) {
            var self = this;

            if (self.validate() &&
                additionalValidators.validate() &&
                self.isPlaceOrderActionAllowed() === true
            ) {
                if (this.isAcceptUIjs()) {
                    if (jQuery('#'+this.getCode()+'_data_value').val() == '' &&
                        jQuery('#'+this.getCode()+'_data_descriptor').val() == ''
                    ) {
                        return false;
                    } else {
                        return self._super(data, event);
                    }
                } else {
                    if (this.isAcceptjs() &&
                        jQuery('#'+this.getCode()+'_data_value').val() == '' &&
                        jQuery('#'+this.getCode()+'_data_descriptor').val() == ''
                    ) {
                        this.acceptJSCaller();
                    } else {
                        if (this.isAcceptjs() == 1 ||
                            self.isCaptchaEnabled() != 3
                        ) {
                            return self._super(data, event);
                        } else {
                            if (self.generateC == 1) {
                                self.generateC = 0;
                                return self._super(data, event);
                            } else {
                                grecaptcha.ready(function() {
                                    grecaptcha.execute(self.cSiteKey(), {action: 'submit'}).then(function(token) {
                                        self.cToken = token;
                                        self.generateOrder();
                                    });
                                });
                            }
                        }
                    }
                }
            }
        },

        generateOrder: function() {
            this.generateC = 1;
            this.placeOrder();
        }
    });
});
