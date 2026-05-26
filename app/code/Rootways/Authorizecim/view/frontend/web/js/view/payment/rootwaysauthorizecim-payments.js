define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'rootways_authorizecim_option',
                component: 'Rootways_Authorizecim/js/view/payment/method-renderer/authorizecim-method'
            },
            {
                type: 'rootways_authorizecim_echeck',
                component: 'Rootways_Authorizecim/js/view/payment/method-renderer/echeck'
            },
            {
                type: 'rootways_authorizecim_option_hosted',
                component: 'Rootways_Authorizecim/js/view/payment/method-renderer/authorizechosted-method'
            },
            {
                type: 'rootways_authorizecim_option_visa',
                component: 'Rootways_Authorizecim/js/view/payment/method-renderer/visa-method'
            },
            {
                type: 'rootways_authorizecim_option_googlepay',
                component: 'Rootways_Authorizecim/js/view/payment/method-renderer/googlepay-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
