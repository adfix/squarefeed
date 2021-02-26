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
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productCollection;

    /**
     * StockRegistry constructor.
     * @param DateTime $dateTime
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollection
     */
    public function __construct(
        DateTime $dateTime,
        StockConfigurationInterface $stockConfiguration,
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollection
    ) {
        $this->date = $dateTime;
        $this->stockConfiguration = $stockConfiguration;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->storeManager = $storeManager;
        $this->productCollection = $productCollection;
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
        $collection = $this->productCollection->create();
        $collection->addAttributeToSelect('*');
        $collection->addStoreFilter($this->storeManager->getStore());
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS);
        $collection->addAttributeToSelect('*')->setFlag('has_stock_status_filter', true);
        $collection->joinField('cataloginventory',
            'cataloginventory_stock_item',
            '*',
            'product_id=entity_id',
            null,
            'left'
        );

        $collection->getSelect()
            ->columns('type_id');

        if ((int)$currentPage !== 0 && (int)$pageSize !== 0) {
            $collection->setCurPage((int)$currentPage);
            $collection->setPageSize((int)$pageSize);
        }

        $items = [];
        foreach ($collection->getItems() as $item) {
            $items[] = $item->getData();
        }
        $itemsTotal = $collection->getSize();
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
