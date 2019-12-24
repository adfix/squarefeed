<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;

/**
 * Class StockRegistry
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockRegistry implements StockRegistryInterface
{
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    protected $criteriaFactory;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var StockItemRepositoryInterface
     */
    protected $stockItemRepository;

    /**
     * StockRegistry constructor.
     *
     * @param DateTime $dateTime
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     */
    public function __construct(
        DateTime $dateTime,
        StockConfigurationInterface $stockConfiguration,
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory
    ) {
        $this->date = $dateTime;
        $this->stockConfiguration = $stockConfiguration;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
    }

    /**
     * Retrieves a list of stock items
     *
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList($currentPage = 0, $pageSize = 0)
    {
        $scopeId = $this->stockConfiguration->getDefaultScopeId();

        /** @var \Magento\CatalogInventory\Api\StockItemCriteriaInterface $criteria */
        $criteria = $this->criteriaFactory->create();
        $criteria->setScopeFilter($scopeId);

        if ((int)$currentPage !== 0 && (int)$pageSize !== 0) {
            $criteria->setLimit((int)$currentPage, (int)$pageSize);
        }

        /** @var \Magento\CatalogInventory\Api\Data\StockItemCollectionInterface $list */
        $list = $this->stockItemRepository->getList($criteria);

        $items = [];
        foreach ($list->getItems() as $item) {
            $items[] = $item->getData();
        }
        $itemsTotal = $list->getTotalCount();
        $response = [
            'status' => 'OK',
            'total' => $itemsTotal,
            'page' => ((int)$currentPage === 0) ? 1 : (int)$currentPage,
            'pageSize' => ((int)$pageSize === 0) ? $itemsTotal : (int)$pageSize,
            'stockItems' => $items,
            'timestamp' => $this->date->gmtTimestamp()
        ];
        return [$response];
    }
}
