<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\ProductLinks\Types;

use Magento\Store\Model\StoreManagerInterface;
use Adfix\Squarefeed\Model\ProductLinks\ProductOptionsInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Type;

class Configurable implements ProductOptionsInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Configurable constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(CollectionFactory $collectionFactory, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Prepare product linked options
     *
     * @param string $lastUpdateDate
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareData($lastUpdateDate = '')
    {
        $productCollection = $this->collectionFactory->create();
        $productCollection->addStoreFilter($this->storeManager->getStore());
        $productCollection->addAttributeToFilter('type_id', ['eq' => Type::TYPE_CODE]);

        if ($lastUpdateDate) {
            $productCollection->joinAttribute(
                \Adfix\Squarefeed\Helper\Data::PRODUCT_UPDATED_AT_TIME_ATTR,
                'catalog_product/' . \Adfix\Squarefeed\Helper\Data::PRODUCT_UPDATED_AT_TIME_ATTR,
                'entity_id',
                null,
                'left'
            );
            $productCollection->addAttributeToFilter(
                [
                    [
                        'attribute' => \Adfix\Squarefeed\Helper\Data::PRODUCT_UPDATED_AT_TIME_ATTR,
                        'gteq' => $lastUpdateDate
                    ],
                    ['attribute' => 'created_at', 'gteq' => $lastUpdateDate],
                    ['attribute' => 'updated_at', 'gteq' => $lastUpdateDate],
                ]
            );
        }

        $configurableData = [];
        foreach ($productCollection as $product) {
            $variations = [];
            $variationsLabels = [];
            $productAttributesOptions = $product->getTypeInstance()->getConfigurableOptions($product);
            foreach ($productAttributesOptions as $productAttributeOption) {
                foreach ($productAttributeOption as $optValues) {
                    $variations[$optValues['sku']][$optValues['attribute_code']] = $optValues['option_title'];

                    if (!empty($optValues['super_attribute_label'])) {
                        $variationsLabels[$optValues['attribute_code']] = $optValues['super_attribute_label'];
                    }
                }
            }


            $configurableData[$product->getId()] = [
                'options' => $variations,
                'options_label' => $variationsLabels
            ];
        }

        return $configurableData;
    }
}
