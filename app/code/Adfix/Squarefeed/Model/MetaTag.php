<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Adfix\Squarefeed\Helper\Data;
use Adfix\Squarefeed\Logger\Logger;
use Adfix\Squarefeed\Api\MetaTagInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MetaTag implements MetaTagInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * MetaTag constructor.
     *
     * @param Logger $logger
     * @param Config $config
     * @param TypeListInterface $cacheTypeList
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Logger $logger,
        Config $config,
        TypeListInterface $cacheTypeList,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->resourceConfig = $config;
        $this->scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Save meta tag element
     *
     * @param string $metaElement
     * @return array
     */
    public function save($metaElement)
    {
        try {
            $this->resourceConfig->saveConfig(Data::XML_META_TAG, $metaElement, 'default', 0);
            $this->cleanCache();
            $response = ['status' => 'OK'];
        } catch (\Exception $e) {
            $this->logError($e);
            $response['status'] = 'FAILED';
            $response['message'] = $e->getMessage() . '. For more info please check magento squarefeed log file.';

        }
        return [$response];
    }

    /**
     * Delete meta tag element
     *
     * @return array
     */
    public function delete()
    {
        try {
            $connection = $this->resourceConfig->getConnection();
            $connection->delete(
                $this->resourceConfig->getMainTable(),
                [
                    $connection->quoteInto('path = ?', Data::XML_META_TAG),
                ]
            );
            $this->cleanCache();
            $response = ['status' => 'OK'];
        } catch (\Exception $e) {
            $this->logError($e);
            $response['status'] = 'FAILED';
            $response['message'] = $e->getMessage() . '. For more info please check magento squarefeed log file.';

        }
        return [$response];
    }

    /**
     * Log error
     *
     * @param \Exception $e
     */
    protected function logError(\Exception $e)
    {
        $this->logger->info('[MetaTag] ERROR: ' . $e->getMessage());
        $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
        $this->logger->info($e->getTraceAsString());
    }

    /**
     * Clear magento cache
     */
    protected function cleanCache()
    {
        if (method_exists($this->scopeConfig, 'clean')) {
            $this->scopeConfig->clean();
        }
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType('full_page');
        $this->cacheTypeList->cleanType('block_html');
    }
}
