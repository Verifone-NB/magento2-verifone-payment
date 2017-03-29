define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order'
    ],
    function ($, Component, customer, placeOrderAction) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Verifone_Payment/payment/verifone-form'
            },
            getData: function () {
                return {
                    "method": this.item.method,
                    "additional_data": this.getAdditionalData()
                };
            },
            getAdditionalData: function () {
                var ret = {};

                var paymentMethodRadio = jQuery("input[name=payment\\[additional_data\\]\\[payment-method\\]]:checked");
                if (paymentMethodRadio.length) {
                    ret["payment-method"] = paymentMethodRadio.val()
                } else {
                    ret["payment-method"] = false;
                }

                var savePaymentMethodRadio = jQuery("input[name=payment\\[additional_data\\]\\[save-payment-method\\]]:checked");

                if (savePaymentMethodRadio.length) {
                    ret["save-payment-method"] = true;
                } else {
                    ret["save-payment-method"] = false;
                }

                if (ret["save-payment-method"] === true || ret["payment-method"] !== false) {
                    return ret;
                }

                return null;
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);
                    $.when(placeOrder).done(function () {
                        $.mage.redirect(window.checkoutConfig.payment.verifonePayment.redirectUrl);
                    }).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    });
                    return true;
                }
                return false;
            },
            getPaymentMethods: function () {
                return window.checkoutConfig.payment.verifonePayment.paymentMethods;
            },
            getAllowSaveCC: function () {
                return window.checkoutConfig.payment.verifonePayment.allowSaveCC;
            },
            getAllowSaveCCInfo: function () {
                return window.checkoutConfig.payment.verifonePayment.allowSaveCCInfo;
            },
            getSavedPaymentsMethods: function () {
                return window.checkoutConfig.payment.verifonePayment.savedPaymentMethods;
            },
            hasSavedPaymentsMethods: function () {
                return window.checkoutConfig.payment.verifonePayment.hasSavedPaymentMethods;
            }

        });
    }
);