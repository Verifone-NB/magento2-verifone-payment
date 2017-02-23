define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (Component,
    rendererList) {
    'use strict';
    rendererList.push(
      {
        type: 'verifone_payment',
        component: 'Verifone_Payment/js/view/payment/method-renderer/verifone-method'
      }
    );
    /** Add view logic here if needed */
    return Component.extend({});
  }
);