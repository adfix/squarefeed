<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\ProductLinks;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;

class ProductOptions implements ProductOptionsInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $productTypes;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ProductOptions constructor.
     *
     * @param Logger $logger
     * @param ObjectManagerInterface $objectManager
     * @param array $productTypes
     */
    public function __construct(Logger $logger, ObjectManagerInterface $objectManager, $productTypes = [])
    {
        $this->logger = $logger;
        $this->productTypes = $productTypes;
        $this->objectManager = $objectManager;
    }

    /**
     * Prepare product linked options
     *
     * @param string $lastUpdateDate
     * @return array
     */
    public function prepareData($lastUpdateDate = '')
    {
        $productOptionsData = [];
        foreach ($this->productTypes as $type => $className) {
            try {
                $productOptionsData[$type] = $this->objectManager->get($className)->prepareData($lastUpdateDate);
            } catch (\Exception $e) {
                $productOptionsData[$type] = 'Internal Error, please check magento squarefeed log file';
                $this->logger->info('[ProductOptions] ERROR: ' . $e->getMessage());
                $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
                $this->logger->info($e->getTraceAsString());
            }
        }

        return $productOptionsData;
    }
}
