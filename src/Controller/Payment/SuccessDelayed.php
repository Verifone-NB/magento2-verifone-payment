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

namespace Verifone\Payment\Controller\Payment;

use Verifone\Payment\Controller\AbstractPayment;

class SuccessDelayed extends AbstractPayment
{
    public function execute()
    {
        return $this->_handleSuccess(true);
    }
}