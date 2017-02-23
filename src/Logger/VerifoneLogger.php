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

namespace Verifone\Payment\Logger;

use Monolog\Logger;

class VerifoneLogger extends Logger
{

    const VERIFONE_INFO = 201;
    const VERIFONE_RESULT = 202;

    protected static $levels = [
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
        201 => 'VERIFONE_INFO',
        202 => 'VERIFONE_RESULT'
    ];

    /**
     * Adds a log record at the VERIFONE_INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return bool
     */
    public function addVerifoneInfo($message, array $context = [])
    {
        return $this->addRecord(static::VERIFONE_INFO, $message, $context);
    }

    /**
     * Adds a log record at the VERIFONE_RESULT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return bool
     */
    public function addVerifoneResult($message, array $context = [])
    {
        return $this->addRecord(static::VERIFONE_RESULT, $message, $context);
    }

    /**
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     *
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = [])
    {
        $context['is_exception'] = $message instanceof \Exception;
        return parent::addRecord($level, $message, $context);
    }
}