define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'mage/url',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Ui/js/modal/alert'
], function (
    Component,
    $,
    url,
    ko,
    quote,
    fullScreenLoader,
    additionalValidators,
    alert
) {
    'use strict';

    window.AuthorizeNetIFrame = {};
    var configAuthorizenet = window.checkoutConfig.payment.rootways_authorizecim_option_hosted;
    var configAuthorizenetHosted = window.checkoutConfig.payment.rootways_authorizecim_option_hosted;
    var agreementsConfig = window.checkoutConfig ? window.checkoutConfig.checkoutAgreements : {};
    var agreementsInputPath = '#rootways_authorizecim_option_hosted_wrapper div.checkout-agreements input';

    return Component.extend({
        defaults: {
            template: 'Rootways_Authorizecim/payment/authorizehosted',
            hostedPaymentResponseData: null,
            loadIframe: true,
            grandTotalAmount: 0,
            rwEmail: ko.observable(quote.guestEmail)
        },
        agreements: agreementsConfig.agreements,

        initialize: function () {
            this._super();
            var self = this;

            var prevAddress;
            quote.billingAddress.subscribe(function (newAddress) {
                if ((newAddress && newAddress.getKey() !== undefined) || prevAddress.getKey() !== undefined) {
                    if (!newAddress ^ !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                        prevAddress = newAddress;
                        if (newAddress) {
                            self.loadHostedForm();
                        }
                    }
                }
            });
            this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
            quote.totals.subscribe(function () {
                if (this.grandTotalAmount != quote.totals()['base_grand_total']) {
                    this.grandTotalAmount = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                    self.loadHostedForm();
                }
            }.bind(this));

            return this;
        },

        initObservable: function () {
            //this._super();

            this._super()
                .observe(['rwEmail']);

            this.loadHostedForm();
            this.agreementListner();

            return this;
        },

        getCode: function() {
            return 'rootways_authorizecim_option_hosted';
        },

        getData: function () {
            if (this.loadIframe) {
                this.loadHostedForm();
            }
            this.loadIframe = false;

            var data = {
                'method': this.getCode(),
                'additional_data': {
                    'payment_method_nonce': this.hostedPaymentResponseData
                }
            };
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

            fullScreenLoader.startLoader();
            var includeHostedForm = function () {
                if (!jQuery('.rootways_authorizecim_option_hosted_iframe_wrapper')[0]) {
                    setTimeout(function(){includeHostedForm();}, 300);
                    return;
                }
                $("#rw_authorizecim_iframe").remove();
                $('.rootways_authorizecim_option_hosted_iframe_wrapper').html('<div id="rw_authorizecim_iframe">' +
                    '<iframe id="add_payment" className="embed-responsive-item panel" name="add_payment" ' +
                    'width="100%" height="300px;" frameBorder="0" scrolling="yes" hidden="true"></iframe>' +
                    '</div>');

                var validateUrl = url.build('rootways_authorizecim/hostedform/index');
                var cEmail = '';
                if(quote.guestEmail) {
                    cEmail = quote.guestEmail;
                } else {
                    cEmail = window.checkoutConfig.customerData.email;
                }
                var DataArray = { "amount": self.grandTotalAmount};
                if (quote.billingAddress()) {
                    var billingAddress = quote.billingAddress();
                    DataArray['billfirstname'] = billingAddress.firstname;
                    DataArray['billlastname'] = billingAddress.lastname;
                    DataArray['billcompany'] = billingAddress.company;
                    DataArray['billaddress'] = billingAddress.street;
                    DataArray['billcity'] = billingAddress.city;
                    DataArray['billstate'] = billingAddress.regionCode;
                    DataArray['billzip'] = billingAddress.postcode;
                    DataArray['billconid'] = billingAddress.countryId;
                    DataArray['billphone'] = billingAddress.telephone;
                    DataArray['email'] = cEmail;
                }
                if (quote.shippingAddress()) {
                    var shippingAddress = quote.shippingAddress();
                    DataArray['shipfirstname'] = shippingAddress.firstname;
                    DataArray['shiplastname'] = shippingAddress.lastname;
                    DataArray['shipcompany'] = shippingAddress.company;
                    DataArray['shipaddress'] = shippingAddress.street;
                    DataArray['shipcity'] = shippingAddress.city;
                    DataArray['shipstate'] = shippingAddress.regionCode;
                    DataArray['shipzip'] = shippingAddress.postcode;
                    DataArray['shipconid'] = shippingAddress.countryId;
                }
                if (self.validate() && additionalValidators.validate()) {
                //if (cEmail != '' && cEmail != undefined) {
                    $('.rw_iframe_placeholder').hide();
                    $.ajax({
                        url : validateUrl,
                        data: DataArray,
                        type: "GET",
                        success: function(data)
                        {
                            $('#token').val(data).change();
                            $("#add_payment").show();
                            $("#send_token").attr({ "action": self.getHostedGatewayUrl(), "target": "add_payment" }).submit();
                            $(window).scrollTop($('#add_payment').offset().top - 50);
                        }
                    }).done(function() {
                        fullScreenLoader.stopLoader();
                    });
                    AuthorizeNetIFrame.onReceiveCommunication = self.hostedResponseHandler.bind(self);
                } else {
                    fullScreenLoader.stopLoader();
                    $('.rw_iframe_placeholder').show();
                }
            };
            includeHostedForm();
        },

        parseQueryString: function(str) {
            var vars = [];
            var arr = str.split('&');
            var pair;
            for (var i = 0; i < arr.length; i++) {
                pair = arr[i].split('=');
                vars[pair[0]] = unescape(pair[1]);
            }
            return vars;
        },

        hostedResponseHandler: function (querystr) {
            var self = this;
            var params = self.parseQueryString(querystr);
            switch (params["action"]) {
                case "successfulSave":
                    break;
                case "cancel":
                    location.reload();
                    break;
                case "resizeWindow":
                    var w = parseInt(params["width"]);
                    var h = parseInt(params["height"]);
                    var ifrm = document.getElementById("add_payment");
                    ifrm.style.width = w.toString() + "px";
                    ifrm.style.height = h.toString() + "px";
                    break;
                case "transactResponse":
                    var errorMsg = '';
                    var responseData = querystr.split('response=');
                    var responseOjbject = JSON.parse(responseData[1]);
                    if (responseOjbject.responseCode !== undefined &&
                        (responseOjbject.responseCode == 1 || responseOjbject.responseCode == 4)
                        ) {
                            self.hostedPaymentResponseData = responseData[1];
                            self.placeOrder();
                    } else {
                        errorMsg = 'There is an error in payment processing, please try again.';
                        //for (var i = 0; i < responseOjbject.messages.message.length; i++) {
                            //errorMsg = 'Error processing your order, Error: '+response.messages.message[i].text
                        //}
                    }

                    if (errorMsg != '') {
                        alert({
                            title: $.mage.__('Error'),
                            content: $.mage.__(errorMsg),
                            actions: {
                                always: function() {
                                    self.loadHostedForm();
                                }
                            }
                        });
                    }
                }
        },

        showNote: function () {
            if (configAuthorizenetHosted.topNote != '' &&
                configAuthorizenetHosted.topNote != null
               ) {
                return true;
            } else {
                return false;
            }
        },

        getTopNote: function () {
            return configAuthorizenetHosted.topNote;
        },

        /**
         * @returns {String}
         */
        getHostedGatewayUrl: function() {
            return configAuthorizenetHosted.hostedGatewayUrl;
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
        }
    });
});
