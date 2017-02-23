<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Verifone\Payment\Logger\VerifoneLogger;

class Info extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/verifone/info.log';

    /**
     * @var int
     */
    protected $loggerType = VerifoneLogger::VERIFONE_INFO;
}