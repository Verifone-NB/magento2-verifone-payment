<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright  Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author     Szymon Nosal <simon@lamia.fi>
 *
 */

namespace Verifone\Payment\Helper;

class Path
{
    const XML_PATH_MERCHANT_CODE = 'payment/verifone_payment/merchant_agreement_code';
    const XML_PATH_KEY_SHOP = 'payment/verifone_payment/shop_private_keyfile';
    const XML_PATH_KEY_VERIFONE = 'payment/verifone_payment/pay_page_public_keyfile';

    const XML_PATH_IS_LIVE_MODE = 'payment/verifone_payment/is_live_mode';
    const XML_PATH_SERVER_URL = 'payment/verifone_payment/server_url_';
    const XML_PATH_PAYMENT_URL = 'payment/verifone_payment/pay_page_url_';

    const XML_PATH_PAYMENT_METHODS = 'payment/verifone_payment/paymentsgroups_array';
    const XML_PATH_CARD_METHODS = 'payment/verifone_payment/cardpaymentsgroup_array';
    const XML_PATH_PAYMENT_DEFAULT_GROUP = 'payment/verifone_payment/group_for_default_view';
    const XML_PATH_SAVED_PAYMENT_REST_LIMIT = 'payment/verifone_payment/saved_cards_s2s_payment_limit';

    const XML_PATH_VALIDATE_URL = 'payment/verifone_payment/validate_url';
    const XML_PATH_SKIP_CONFIRMATION_PAGE = 'payment/verifone_payment/skip_confirmation_page';
    const XML_PATH_DISABLE_RSA_BLINDING = 'payment/verifone_payment/disable_rsa_blinding';

    const XML_PATH_BASKET_ITEM_SENDING = 'payment/verifone_payment/basket_item_sending';
    const XML_PATH_COMBINE_INVOICE_BASKET_ITEMS = 'payment/verifone_payment/combine_invoice_basket_items';
    const XML_PATH_ALLOW_TO_SAVE_CC = 'payment/verifone_payment/allow_to_save_cc';
    const XML_PATH_REMEMBER_CC_INFO = 'payment/verifone_payment/remember_cc_info';
    const XML_PATH_SAVE_MASKED_PAN_NUMBER = 'payment/verifone_payment/save_masked_pan_number';

    const XML_PATH_ORDER_STATUS_NEW = 'payment/verifone_payment/order_status_new';
    const XML_PATH_ORDER_STATUS_PROCESSING = 'payment/verifone_payment/order_status_processing';

    const XML_PATH_EXTERNAL_CUSTOMER_ID = 'payment/verifone_payment/external_customer_id';
    const XML_PATH_EXTERNAL_CUSTOMER_ID_FIELD = 'payment/verifone_payment/external_customer_id_field';

}