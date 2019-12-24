<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Api\WebsiteProductsInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Model\ResourceModel\Product\WebsiteFactory;

class WebsiteProducts implements WebsiteProductsInterface
{
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Website
     */
    protected $websiteResource;

    /**
     * WebsiteProducts constructor.
     *
     * @param DateTime $dateTime
     * @param Logger $logger
     * @param WebsiteFactory $websiteFactory
     */
    public function __construct(
        DateTime $dateTime,
        Logger $logger,
        WebsiteFactory $websiteFactory
    ) {
        $this->date = $dateTime;
        $this->logger = $logger;
        $this->websiteResource = $websiteFactory->create();
    }

    /**
     * Retrieve website ids list and assigned products
     *
     * @return array
     */
    public function getWebsiteProducts()
    {
        $response = [
            'status' => 'OK',
            'timestamp' => $this->date->gmtTimestamp()
        ];
        /** @var AdapterInterface $connection */
        $connection = $this->websiteResource->getConnection();
        try {
            $select = $connection->select()->from(
                $this->websiteResource->getMainTable(),
                ['product_id', 'website_id']
            );
            $rowSet = $connection->fetchAll($select);
            foreach ($rowSet as $row) {
                $response['websites'][$row['website_id']][] = $row['product_id'];
            }
        } catch (\Exception $e) {
            $response['status'] = 'FAILED';
            $response['message'] = $e->getMessage() . '. For more info please check magento squarefeed log file.';
            $this->logger->info('[WebsiteProducts] ERROR: ' . $e->getMessage());
            $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
            $this->logger->info($e->getTraceAsString());
        }

        return [$response];
    }
}
