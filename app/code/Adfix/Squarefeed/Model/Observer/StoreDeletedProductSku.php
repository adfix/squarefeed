<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\Observer;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Model\DeletedProductSkuFactory;

class StoreDeletedProductSku implements ObserverInterface
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var DeletedProductSkuFactory
     */
    protected $deletedProductSkuFactory;

    /**
     * StoreDeletedProductSku constructor.
     *
     * @param Logger $logger
     * @param DateTime $dateTime
     * @param DeletedProductSkuFactory $deletedProductSkuFactory
     */
    public function __construct(Logger $logger, DateTime $dateTime, DeletedProductSkuFactory $deletedProductSkuFactory)
    {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->deletedProductSkuFactory = $deletedProductSkuFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            /** @var Adfix\Squarefeed\Model\DeletedProductSku $deletedProductSku */
            $deletedProductSku = $this->deletedProductSkuFactory->create();
            $deletedProductSku->addData([
                'sku' => $product->getSku(),
                'deleted_at' => $this->dateTime->date()
            ]);
            $deletedProductSku->save();
        } catch (\Exception $e) {
            $this->logger->info('[Json] ERROR: ' . $e->getMessage());
            $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
            $this->logger->info($e->getTraceAsString());
        }
    }
}
