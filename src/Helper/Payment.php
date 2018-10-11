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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Payment extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);


        $this->_scopeConfig = $this->scopeConfig;
    }

    public function convertCountryCode2Numeric($cc)
    {
        $codes = [
            'AF' => 4, 'AL' => 8, 'DZ' => 12, 'AS' => 16, 'AD' => 20, 'AO' => 24, 'AI' => 660, 'AQ' => 10,
            'AG' => 28, 'AR' => 32, 'AM' => 51, 'AW' => 533, 'AU' => 36, 'AT' => 40, 'AZ' => 31, 'BS' => 44,
            'BH' => 48, 'BD' => 50, 'BB' => 52, 'BY' => 112, 'BE' => 56, 'BZ' => 84, 'BJ' => 204, 'BM' => 60,
            'BT' => 64, 'BO' => 68, 'BA' => 70, 'BW' => 72, 'BV' => 74, 'BR' => 76, 'IO' => 86, 'BN' => 96,
            'BG' => 100, 'BF' => 854, 'BI' => 108, 'KH' => 116, 'CM' => 120, 'CA' => 124, 'CV' => 132, 'KY' => 136,
            'CF' => 140, 'TD' => 148, 'CL' => 152, 'CN' => 156, 'CX' => 162, 'CC' => 166, 'CO' => 170, 'KM' => 174,
            'CG' => 178, 'CK' => 184, 'CR' => 188, 'CI' => 384, 'HR' => 191, 'CU' => 192, 'CY' => 196, 'CZ' => 203,
            'DK' => 208, 'DJ' => 262, 'DM' => 212, 'DO' => 214, 'TP' => 626, 'EC' => 218, 'EG' => 818, 'SV' => 222,
            'GQ' => 226, 'ER' => 232, 'EE' => 233, 'ET' => 231, 'FK' => 238, 'FO' => 234, 'FJ' => 242, 'FI' => 246,
            'FR' => 250, 'FX' => 249, 'GF' => 254, 'PF' => 258, 'TF' => 260, 'GA' => 266, 'GM' => 270, 'GE' => 268,
            'DE' => 276, 'GH' => 288, 'GI' => 292, 'GR' => 300, 'GL' => 304, 'GD' => 308, 'GP' => 312, 'GU' => 316,
            'GT' => 320, 'GN' => 324, 'GW' => 624, 'GY' => 328, 'HT' => 332, 'HM' => 334, 'VA' => 336, 'HN' => 340,
            'HK' => 344, 'HU' => 348, 'IS' => 352, 'IN' => 356, 'ID' => 360, 'IR' => 364, 'IQ' => 368, 'IE' => 372,
            'IL' => 376, 'IT' => 380, 'JM' => 388, 'JP' => 392, 'JO' => 400, 'KZ' => 398, 'KE' => 404, 'KI' => 296,
            'KP' => 408, 'KR' => 410, 'KW' => 414, 'KG' => 417, 'LA' => 418, 'LV' => 428, 'LB' => 422, 'LS' => 426,
            'LR' => 430, 'LY' => 434, 'LI' => 438, 'LT' => 440, 'LU' => 442, 'MO' => 446, 'MK' => 807, 'MG' => 450,
            'MW' => 454, 'MY' => 458, 'MV' => 462, 'ML' => 466, 'MT' => 470, 'MH' => 584, 'MQ' => 474, 'MR' => 478,
            'MU' => 480, 'YT' => 175, 'MX' => 484, 'FM' => 583, 'MD' => 498, 'MC' => 492, 'MN' => 496, 'MS' => 500,
            'MA' => 504, 'MZ' => 508, 'MM' => 104, 'NA' => 516, 'NR' => 520, 'NP' => 524, 'NL' => 528, 'AN' => 530,
            'NC' => 540, 'NZ' => 554, 'NI' => 558, 'NE' => 562, 'NG' => 566, 'NU' => 570, 'NF' => 574, 'MP' => 580,
            'NO' => 578, 'OM' => 512, 'PK' => 586, 'PW' => 585, 'PA' => 591, 'PG' => 598, 'PY' => 600, 'PE' => 604,
            'PH' => 608, 'PN' => 612, 'PL' => 616, 'PT' => 620, 'PR' => 630, 'QA' => 634, 'RE' => 638, 'RO' => 642,
            'RU' => 643, 'RW' => 646, 'KN' => 659, 'LC' => 662, 'VC' => 670, 'WS' => 882, 'SM' => 674, 'ST' => 678,
            'SA' => 682, 'SN' => 686, 'SC' => 690, 'SL' => 694, 'SG' => 702, 'SK' => 703, 'SI' => 705, 'SB' => 90,
            'SO' => 706, 'ZA' => 710, 'GS' => 239, 'ES' => 724, 'LK' => 144, 'SH' => 654, 'PM' => 666, 'SD' => 736,
            'SR' => 740, 'SJ' => 744, 'SZ' => 748, 'SE' => 752, 'CH' => 756, 'SY' => 760, 'TW' => 158, 'TJ' => 762,
            'TZ' => 834, 'TH' => 764, 'TG' => 768, 'TK' => 772, 'TO' => 776, 'TT' => 780, 'TN' => 788, 'TR' => 792,
            'TM' => 795, 'TC' => 796, 'TV' => 798, 'UG' => 800, 'UA' => 804, 'AE' => 784, 'GB' => 826, 'US' => 840,
            'UM' => 581, 'UY' => 858, 'UZ' => 860, 'VU' => 548, 'VE' => 862, 'VN' => 704, 'VG' => 92, 'VI' => 850,
            'WF' => 876, 'EH' => 732, 'YE' => 887, 'YU' => 891, 'ZR' => 180, 'ZM' => 894, 'ZW' => 716];

        if (isset($codes[$cc])) {
            return $codes[$cc];
        }

        return '246';  // default to Finland
    }

    public function convertCountryToISO4217($cc)
    {
        $codes = array(
            'AED' => '784', 'AFN' => '971,', 'ALL' => '8', 'AMD' => '51', 'ANG' => '532', 'AOA' => '973', 'ARS' => '32',
            'AUD' => '36', 'AWG' => '533', 'AZN' => '944', 'BAM' => '977', 'BBD' => '52', 'BDT' => '50', 'BGN' => '975',
            'BHD' => '48', 'BIF' => '108', 'BMD' => '60', 'BND' => '96', 'BOB' => '68', 'BOV' => '984', 'BRL' => '986',
            'BSD' => '44', 'BTN' => '64', 'BWP' => '72', 'BYR' => '974', 'BZD' => '84', 'CAD' => '124', 'CDF' => '976',
            'CHE' => '947', 'CHF' => '756', 'CHW' => '948', 'CLF' => '990', 'CLP' => '152', 'CNY' => '156', 'COP' => '170',
            'COU' => '970', 'CRC' => '188', 'CUC' => '931', 'CUP' => '192', 'CVE' => '132', 'CZK' => '203', 'DJF' => '262',
            'DKK' => '208', 'DOP' => '214', 'DZD' => '12', 'EGP' => '818', 'ERN' => '232', 'ETB' => '230', 'EUR' => '978',
            'FJD' => '242', 'FKP' => '238', 'GBP' => '826', 'GEL' => '981', 'GHS' => '936', 'GIP' => '292', 'GMD' => '270',
            'GNF' => '324', 'GTQ' => '320', 'GYD' => '328', 'HKD' => '344', 'HNL' => '340', 'HRK' => '191', 'HTG' => '332',
            'HUF' => '348', 'IDR' => '360', 'ILS' => '376', 'INR' => '356', 'IQD' => '368', 'IRR' => '364', 'ISK' => '352',
            'JMD' => '388', 'JOD' => '400', 'JPY' => '392', 'KES' => '404', 'KGS' => '417', 'KHR' => '116', 'KMF' => '174',
            'KPW' => '408', 'KRW' => '410', 'KWD' => '414', 'KYD' => '136', 'KZT' => '398', 'LAK' => '418', 'LBP' => '422',
            'LKR' => '144', 'LRD' => '430', 'LSL' => '426', 'LYD' => '434', 'MAD' => '504', 'MDL' => '498', 'MGA' => '969',
            'MKD' => '807', 'MMK' => '104', 'MNT' => '496', 'MOP' => '446', 'MRO' => '478', 'MUR' => '480', 'MVR' => '462',
            'MWK' => '454', 'MXN' => '484', 'MXV' => '979', 'MYR' => '458', 'MZN' => '943', 'NAD' => '516', 'NGN' => '566',
            'NIO' => '558', 'NOK' => '578', 'NPR' => '524', 'NZD' => '554', 'OMR' => '512', 'PAB' => '590', 'PEN' => '604',
            'PGK' => '598', 'PHP' => '608', 'PKR' => '586', 'PLN' => '985', 'PYG' => '600', 'QAR' => '634', 'RON' => '946',
            'RSD' => '941', 'RUB' => '643', 'RWF' => '646', 'SAR' => '682', 'SBD' => '90', 'SCR' => '690', 'SDG' => '938',
            'SEK' => '752', 'SGD' => '702', 'SHP' => '654', 'SLL' => '694', 'SOS' => '706', 'SRD' => '968', 'SSP' => '728',
            'STD' => '678', 'SYP' => '760', 'SZL' => '748', 'THB' => '764', 'TJS' => '972', 'TMT' => '934', 'TND' => '788',
            'TOP' => '776', 'TRY' => '949', 'TTD' => '780', 'TWD' => '901', 'TZS' => '834', 'UAH' => '980', 'UGX' => '800',
            'USD' => '840', 'USN' => '997', 'USS' => '998', 'UYI' => '940', 'UYU' => '858', 'UZS' => '860', 'VEF' => '937',
            'VND' => '704', 'VUV' => '548', 'WST' => '882', 'XAF' => '950', 'XAG' => '961', 'XAU' => '959', 'XBA' => '955',
            'XBB' => '956', 'XBC' => '957', 'XBD' => '958', 'XCD' => '951', 'XDR' => '960', 'XFU' => 'Nil', 'XOF' => '952',
            'XPD' => '964', 'XPF' => '953', 'XPT' => '962', 'XSU' => '994', 'XTS' => '963', 'XUA' => '965', 'XXX' => '999',
            'YER' => '886', 'ZAR' => '710', 'ZMW' => '967', 'ZWL' => '932');

        return isset($codes[$cc]) ? $codes[$cc] : $codes['EUR'];
    }

    public function getSavedCardsS2sPaymentLimit()
    {
        return $this->_scopeConfig->getValue(Path::XML_PATH_SAVED_PAYMENT_REST_LIMIT);
    }

    public function getTransactionTypeFromMap($_verifoneStatus)
    {
        $map = array(
            'committed' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
            'settled' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
            'verified' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,
            'refunded' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
            'authorized' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
            'cancelled' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
            'subscribed' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT,
            'initialized' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER
        );

        return isset($map[$_verifoneStatus]) ? $map[$_verifoneStatus] : \Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT;
    }

    public function getOrderStatusFromMap($_verifoneStatus)
    {
        $map = array(
            'committed' => \Magento\Sales\Model\Order::STATE_PROCESSING,
            'settled' => \Magento\Sales\Model\Order::STATE_PROCESSING,
            'verified' => \Magento\Sales\Model\Order::STATE_PROCESSING,
//            'refunded' => \Magento\Sales\Model\Order::STATE_CLOSED,
            'authorized' => \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
            'cancelled' => \Magento\Sales\Model\Order::STATE_CANCELED,
            //'subscribed' => \Magento\Sales\Model\Order::STATE_PROCESSING,
            //'initialized' => \Magento\Sales\Model\Order::STATE_PROCESSING
        );

        return isset($map[$_verifoneStatus]) ? $map[$_verifoneStatus] : '';
    }

    public function sanitize($value)
    {
        return str_replace('"', '', str_replace('\\', '', str_replace('-', ' ', $value)));
    }
}