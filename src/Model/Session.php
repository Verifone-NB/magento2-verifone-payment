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

namespace Verifone\Payment\Model;

/**
 * Transaction model
 *
 * @method int getLastOrderId()
 * @method Session setLastOrderId(int)
 * @method array getOrderCreateData()
 * @method Session setOrderCreateData(array)
 * @method string getPaymentMethod()
 * @method Session setPaymentMethod(string)
 * @method int getLastOrderIncrementId()
 * @method Session setLastOrderIncrementId()
 */
class Session extends \Magento\Framework\Session\SessionManager
{

}