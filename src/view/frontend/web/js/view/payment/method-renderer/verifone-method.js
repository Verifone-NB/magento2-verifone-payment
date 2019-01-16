define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, customer, placeOrderAction, additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Verifone_Payment/payment/verifone-form',
                code: 'verifone_payment'
            },
            initialize: function () {
                var self = this;
                this._super();
                this.observerOnPaymentMethod();
            },
            getCode: function() {
                return this.code;
            },
            getData: function () {
                return {
                    "method": this.item.method,
                    "additional_data": this.getAdditionalData()
                };
            },
            getAdditionalData: function () {
                var ret = {};
                var send = false;

                var paymentMethod = jQuery("input[name=payment\\[additional_data\\]\\[payment-method\\]]:checked");

                if (!paymentMethod.length) {
                    paymentMethod = jQuery("select[name=payment\\[additional_data\\]\\[payment-method\\]] option:selected[value!='']");
                }

                if (paymentMethod.length) {
                    if (paymentMethod.is('[data-code]')) {
                        ret["payment-method"] = paymentMethod.data('code');
                        ret["payment-method-id"] = paymentMethod.val();
                    } else {
                        ret["payment-method"] = paymentMethod.val();
                    }
                    send = true;
                } else {
                    ret["payment-method"] = false;
                }

                var savePaymentMethodRadio = jQuery("input[name=payment\\[additional_data\\]\\[save-payment-method\\]]:checked");

                if (savePaymentMethodRadio.length) {
                    ret["save-payment-method"] = true;
                    send = true;
                } else {
                    ret["save-payment-method"] = false;
                }

                if (send) {
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
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
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
            isOnlyOnePaymentMethod: function () {
                var methods = this.getPaymentMethods();
                return (methods[0].payments.length + methods[1].payments.length) === 1;
            },
            getAllowSaveCC: function () {
                return window.checkoutConfig.payment.verifonePayment.allowSaveCC && customer.isLoggedIn();
            },
            getAllowSaveCCInfo: function () {
                return window.checkoutConfig.payment.verifonePayment.allowSaveCCInfo;
            },
            getSavedPaymentsMethods: function () {
                return window.checkoutConfig.payment.verifonePayment.savedPaymentMethods;
            },
            hasSavedPaymentsMethods: function () {
                return window.checkoutConfig.payment.verifonePayment.hasSavedPaymentMethods;
            },
            observerOnPaymentMethod: function () {

                var $obj = this;

                jQuery('#co-payment-form').on('change', '[name=payment\\[additional_data\\]\\[payment-method\\]]', function () {

                    var $this = jQuery(this);
                    var $group = $this.closest('.verifone-payment-method-group');
                    var value = $this.val();
                    var checked = $this.attr('checked');
                    var saveCC = $group.find('[name=payment\\[additional_data\\]\\[save-payment-method\\]]').attr('checked');

                    $obj.disableMethods();

                    if (checked || value !== '') {
                        $group.find('.verifone-payment-method-footer').removeClass('hidden');
                    }

                    if ($this.attr('type') === 'radio') {
                        $this.attr('checked', true);
                    } else {
                        // its select and we can reenable group
                        $this.val(value);
                        $group.find('select').attr('disabled', false);
                        $group.find('[id*=verifonepayment-mockup_]').attr('checked', true);

                        if ($this.find('option:selected').attr('data-code')) {
                            $group.find('.verifone-payment-saved-wrapper').addClass('hidden');
                        } else {
                            $group.find('.verifone-payment-saved-wrapper').removeClass('hidden');
                            $group.find('[name=payment\\[additional_data\\]\\[save-payment-method\\]]').attr('checked', saveCC);
                        }
                    }

                });
                jQuery('#co-payment-form').on('change', '[id*=verifonepayment-mockup_]', function () {

                    $obj.disableMethods();

                    var $group = jQuery(this).closest('.verifone-payment-method-group');

                    $group.find('select').attr('disabled', false);
                    $group.find('.verifone-payment-method-footer').removeClass('hidden');
                    $group.find('[id*=verifonepayment-mockup_]').attr('checked', true);
                });

                jQuery('#co-payment-form').on('change', '[name=payment\\[method\\]]', function () {

                    var $this = jQuery(this);

                    if(jQuery('#verifone_payment').is(':checked')) {
                        if($obj.isOnlyOnePaymentMethod()) {
                            jQuery('#verifone-payment-method-VerifonePayment').attr('checked', true);
                            jQuery('#verifone-payment-method-VerifonePayment').closest('.verifone-payment-method-group').addClass('hidden');
                        }
                    } else {
                        if($obj.isOnlyOnePaymentMethod()) {
                            jQuery('#verifone-payment-method-VerifonePayment').attr('checked', false);
                        }
                    }
                });
            },
            disableMethods: function () {
                // Show/hide save payment box
                jQuery('.verifone-payment-method-footer').addClass('hidden');
                jQuery("input[name=payment\\[additional_data\\]\\[save-payment-method\\]]").attr('checked', false);
                jQuery('[name=payment\\[additional_data\\]\\[payment-method\\]]').attr('checked', false);
                jQuery('select[name=payment\\[additional_data\\]\\[payment-method\\]]').val('');

                jQuery('.verifone-payment-method-group select').attr('disabled', true);
                jQuery('[id*=verifonepayment-mockup_]').attr('checked', false);
            }

        });
    }
);