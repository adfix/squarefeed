<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/squarefeed.log';
}
