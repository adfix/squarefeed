<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Plugin\Catalog\Model\Indexer;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\EntityManager\MetadataPool;

class SetUpdatedAtTime
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Http
     */
    protected $request;

    /**
     * Resource instance
     *
     * @var Resource
     */
    protected $resource;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * SetUpdatedAtTime constructor.
     *
     * @param Http $request
     * @param Logger $logger
     * @param DateTime $dateTime
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Http $request,
        Logger $logger,
        DateTime $dateTime,
        MetadataPool $metadataPool,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        $this->metadataPool = $metadataPool;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\Framework\Indexer\ActionInterface $subject
     * @param $ids
     * @throws \Exception
     */
    public function beforeExecute(\Magento\Framework\Indexer\ActionInterface $subject, $ids)
    {
        $this->setProductUpdateAtAttribute($ids);
    }

    /**
     * @param \Magento\Framework\Indexer\ActionInterface $subject
     * @param array $ids
     * @throws \Exception
     */
    public function beforeExecuteList(\Magento\Framework\Indexer\ActionInterface $subject, array $ids)
    {
        $this->setProductUpdateAtAttribute($ids);

    }

    /**
     * @param \Magento\Framework\Indexer\ActionInterface $subject
     * @param $id
     * @throws \Exception
     */
    public function beforeExecuteRow(\Magento\Framework\Indexer\ActionInterface $subject, $id)
    {
        $this->setProductUpdateAtAttribute([$id]);
    }

    /**
     * Retrieve store id
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Set products updated time by ids
     *
     * @param $productIds
     * @return $this
     * @throws \Exception
     */
    protected function setProductUpdateAtAttribute($productIds)
    {
        $actionName = strtolower($this->request->getFullActionName());
        if (strpos($actionName, 'delete') !== false) {
            return $this;
        }

        $parentIds = $this->getRelationsByChild($productIds);
        $productIds = $parentIds ? array_unique(array_merge($parentIds, $productIds)) : $productIds;

        try {
            $this->objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\Action::class)
                ->updateAttributes(
                    $productIds,
                    [\Adfix\Squarefeed\Helper\Data::PRODUCT_UPDATED_AT_TIME_ATTR => $this->dateTime->gmtDate()],
                    $this->getStoreId()
                );
        } catch (\Exception $e) {
            $this->logger->info('[SetUpdatedAtTime] ERROR: ' . $e->getMessage());
            $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
            $this->logger->info($e->getTraceAsString());
        }

        return $this;
    }

    /**
     * Retrieve product relations by children
     *
     * @param $childIds
     * @return array
     * @throws \Exception
     */
    protected function getRelationsByChild($childIds)
    {
        try {
            $connection = $this->resource->getConnection();
            $linkField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
            $select = $connection->select()->from(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'entity_id'
            )->join(
                ['relation' => $this->resource->getTableName('catalog_product_relation')],
                'relation.parent_id = cpe.' . $linkField
            )->where('child_id IN(?)', $childIds);
            return $connection->fetchCol($select);
        } catch (\Exception $e) {
            return [];
        }
    }
}
