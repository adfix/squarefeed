<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Adfix\Squarefeed\Logger\Logger;
use Adfix\Squarefeed\Api\JsonInterface;
use Magento\Catalog\Model\ProductFactory;
use Adfix\Squarefeed\Api\ProductInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Api\ProductLinksInterface;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;

class Json implements JsonInterface
{
    const IN_STOCK_STATUS = 'in stock';
    const OUT_OF_STOCK_STATUS = 'out of stock';
    const MEDIA_IMAGE_PATH = 'catalog/products';
    const ATTRIBUTE_CODE_PREFIX = 'sf_';
    const PARENT_PRODUCT_ATTRIBUTE_SUFFIX = ' (parent)';

    protected $logger;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ProductInterface
     */
    protected $productApi;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ResourceProduct
     */
    protected $resourceProduct;

    /**
     * @var ProductLinksInterface
     */
    protected $productLinksApi;

    /**
     * @var ItemFactory
     */
    protected $stockItemFactory;

    /**
     * @var null|string
     */
    protected $baseUrl = null;

    /**
     * @var null|string
     */
    protected $weightUnit = null;

    /**
     * @var null|string
     */
    protected $baseMediaUrl = null;

    /**
     * @var null|string
     */
    protected $currencyCode = null;

    /**
     * @var array
     */
    protected $parentProducts = [];

    /**
     * Json constructor.
     * @param Logger $logger
     * @param DateTime $dateTime
     * @param ProductInterface $productApi
     * @param ItemFactory $stockItemFactory
     * @param ProductFactory $productFactory
     * @param ResourceProduct $resourceProduct
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductLinksInterface $productLinksApi
     */
    public function __construct(
        Logger $logger,
        DateTime $dateTime,
        ProductInterface $productApi,
        ItemFactory $stockItemFactory,
        ProductFactory $productFactory,
        ResourceProduct $resourceProduct,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductLinksInterface $productLinksApi
    ) {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->productApi = $productApi;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->resourceProduct = $resourceProduct;
        $this->productLinksApi = $productLinksApi;
        $this->stockItemFactory = $stockItemFactory;
    }

    /**
     * Retrieves products in json format
     *
     * @param int $lastUpdateTime
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList($lastUpdateTime = 0, $currentPage = 0, $pageSize = 0)
    {
        $response = [
            'json' => '',
            'status' => \Adfix\Squarefeed\Helper\Data::STATUS_OK,
            'timestamp' => $this->dateTime->gmtTimestamp(),
        ];
        try {
            $productOptions = $this->productLinksApi->getList($lastUpdateTime);
            $productOptions = $productOptions[0];
            if ($productOptions['status'] === \Adfix\Squarefeed\Helper\Data::STATUS_FAILED) {
                throw new \Exception($productOptions['message']);
            }

            $products = $this->productApi->getList($lastUpdateTime, $currentPage, $pageSize);
            if ($products[0]['status'] === \Adfix\Squarefeed\Helper\Data::STATUS_FAILED) {
                throw new \Exception($products['message']);
            }

            if ((int)$lastUpdateTime > 0) {
                $productData = $products[0];
                $products = [];
                foreach ($productData as $key => $data) {
                    if ($key === 'added' || $key === 'updated') {
                        $products[$key] = $this->rebuildData($data, $productOptions);
                    } elseif ($key === 'deleted') {
                        $products[$key] = $data;
                    }
                }
            } else {
                $products = $products[0]['products'];
                if (count($products) < 1) {
                    return [$response];
                }

                $products = array_values($this->rebuildData($products, $productOptions));
                $productsTotal = count($products);
                $response['page'] = ((int)$currentPage === 0) ? 1 : (int)$currentPage;
                $response['pageSize'] = $productsTotal;
                $response['total'] = (int)$this->resourceProduct->countAll();
            }

            $response['json'] = json_encode($products);
        } catch (\Exception $e) {
            $response['status'] = \Adfix\Squarefeed\Helper\Data::STATUS_FAILED;
            $response['message'] = $e->getMessage();
            $this->logger->info('[Json] ERROR: ' . $e->getMessage());
            $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
            $this->logger->info($e->getTraceAsString());
        }

        return [$response];
    }

    /**
     * Rebuild products data
     *
     * @param $products
     * @param $productOptions
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function rebuildData($products, $productOptions)
    {
        $parentProductIds = [];
        $productsOptionsAssociation = $this->buildProductsOptionsAssociation($productOptions);
        foreach ($products as &$product) {
            $product = $this->setProductData($product);

            if (isset($productsOptionsAssociation[$product['sku']])) {
                $product['link_key'] = $productsOptionsAssociation[$product['sku']]['options'];
                $parentProductIds[$product['entity_id']] = $productsOptionsAssociation[$product['sku']]['parent_id'];
            }
        }

        foreach ($parentProductIds as $productId => $parentProductId) {
            if (!isset($products[$productId])) {
                continue;
            }

            $parenProductData = $this->setParentProductData($products, $parentProductId);
            if (!isset($products[$productId]['link'])) {
                $products[$productId]['link'] = $this->getBaseUrl() .
                    $parenProductData['url_key' . self::PARENT_PRODUCT_ATTRIBUTE_SUFFIX];
            }

            if (isset($products[$productId]['link_key'])) {
                $products[$productId]['link'] .= '?' . $products[$productId]['link_key'];
            }

            $products[$productId] = array_merge($products[$productId], $parenProductData);
        }

        return $products;
    }

    /**
     * Set formatted prouct data
     *
     * @param $product
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function setProductData($product)
    {
        $this->setSalePrice($product, $this->getCurrencyCode());
        $this->setImagesPath($product, $this->getBaseMediaUrl());

        if (!isset($product['gtin'])) {
            if (isset($product['upc'])) {
                $product['gtin'] = $product['upc'];
            } elseif (isset($product['ean'])) {
                $product['gtin'] = $product['ean'];
            } else {
                $product['gtin'] = '';
            }
        }

        if (isset($product['manufacturer'])) {
            $product['brand'] = $product['manufacturer'];
        } elseif (!isset($product['brand'])) {
            $product['brand'] = '';
        }

        $product['price'] = isset($product['price']) ?
            round($product['price'], 2) . $this->getCurrencyCode() : '';
        $product['availability'] = $this->getProductAvailability($product['entity_id']);
        $product['shipping_weight'] = (isset($product['weight'])) ?
            round($product['weight'], 2) . $this->getWeightUnit() : '';

        if (isset($product['url_key']) && $product['url_key']) {
            $product['link'] = $this->getBaseUrl() . $product['url_key'];
        }

        return $product;
    }

    /**
     * Set formatted parent product data
     *
     * @param $products
     * @param $productId
     * @return array|mixed
     */
    protected function setParentProductData($products, $productId)
    {
        if (!isset($this->parentProducts[$productId]) && !isset($products[$productId])) {
            /** @var \Magento\Catalog\Model\Product $product */
            try {
                $product = $this->productFactory->create()->load($productId);
                $productData = $this->setProductData($product->getData());
                $this->parentProducts[$productId] = $this->getParentProductAttrArray($productData);
            } catch (\Exception $e) {
                $this->logger->info('[Json] ERROR: ' . $e->getMessage());
                $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
                $this->logger->info($e->getTraceAsString());
            }
        }

        if (isset($products[$productId])) {
            $this->parentProducts[$productId] = $this->getParentProductAttrArray($products[$productId]);
        }

        return (isset($this->parentProducts[$productId])) ? $this->parentProducts[$productId] : [];
    }

    /**
     * Retrieve parent product data array
     *
     * @param $product
     * @return array
     */
    protected function getParentProductAttrArray($product)
    {
        $productData = [];
        foreach ($product as $key => $value) {
            $productData[$key . self::PARENT_PRODUCT_ATTRIBUTE_SUFFIX] = $value;
        }

        return $productData;
    }

    /**
     * Retrieve current store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Retrieve store currency code
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrencyCode()
    {
        if ($this->currencyCode === null) {
            $this->currencyCode = $this->getStore()->getCurrentCurrency()->getCode();
        }

        return $this->currencyCode;
    }

    /**
     * Retrieve default weight unit
     *
     * @return mixed
     */
    protected function getWeightUnit()
    {
        if ($this->weightUnit === null) {
            $this->weightUnit = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $this->weightUnit;
    }

    /**
     * @return |null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = $this->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
        }

        return $this->baseUrl;
    }

    /**
     * @return |null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getBaseMediaUrl()
    {
        if ($this->baseMediaUrl === null) {
            $this->baseMediaUrl = $this->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true);
        }

        return $this->baseMediaUrl;
    }

    /**
     * Retrieve products availability
     *
     * @param $productId
     * @return string
     */
    protected function getProductAvailability($productId)
    {
        try {
            $stockItem = $this->stockItemFactory->create()->load($productId, 'product_id');
            $availability = (!$stockItem->getIsInStock()) ?
                self::OUT_OF_STOCK_STATUS : self::IN_STOCK_STATUS;
        } catch (\Exception $e) {
            $availability = self::OUT_OF_STOCK_STATUS;
        }

        return $availability;
    }

    /**
     * Set full url for product's images
     *
     * @param $product
     * @param $baseMediaPath
     * @return mixed
     */
    protected function setImagesPath(&$product, $baseMediaPath)
    {
        if (isset($product['image'])) {
            $product['image'] = $baseMediaPath . self::MEDIA_IMAGE_PATH . $product['image'];
        }

        if (isset($product['small_image'])) {
            $product['small_image'] = $baseMediaPath . self::MEDIA_IMAGE_PATH . $product['small_image'];
        }

        if (isset($product['thumbnail'])) {
            $product['thumbnail'] = $baseMediaPath . self::MEDIA_IMAGE_PATH . $product['thumbnail'];
        }

        return $product;
    }

    /**
     * Set product sale price
     *
     * @param $product
     * @param $currency
     * @return mixed
     */
    protected function setSalePrice(&$product, $currency)
    {
        $product['sale_price'] = '';
        if (!isset($product['special_price']) || (float)$product['special_price'] == 0) {
            return $product;
        }

        $currentDateTime = $this->dateTime->date();
        if (isset($product['special_from_date']) && $product['special_from_date'] > $currentDateTime) {
            return $product;
        }

        if (isset($product['special_to_date']) && $product['special_to_date'] < $currentDateTime) {
            return $product;
        }

        $product['sale_price'] = round($product['special_price'], 2) . $currency;
        return $product;
    }

    /**
     * Build product options association
     *
     * @param $productOptions
     * @return array
     */
    protected function buildProductsOptionsAssociation($productOptions)
    {
        $productsOptionsAssociation = [];
        $productOptions = $productOptions['productLink'];
        $productTypes = [
            \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
        ];
        foreach ($productTypes as $type) {
            if (!isset($productOptions[$type]) || !is_array($productOptions[$type])) {
                continue;
            }

            foreach ($productOptions[$type] as $parentId => $options) {
                if (!isset($options['options']) || !is_array($options['options'])) {
                    continue;
                }

                foreach ($options['options'] as $sku => $option) {
                    $attributesString = '';
                    $productsOptionsAssociation[$sku]['parent_id'] = $parentId;
                    foreach ($option as $code => $value) {
                        $attributesString .= self::ATTRIBUTE_CODE_PREFIX . $code . '=' . $value . '&';
                    }
                    $productsOptionsAssociation[$sku]['options'] = trim($attributesString, '&');
                }
            }
        }

        return $productsOptionsAssociation;
    }
}
