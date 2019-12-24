<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Integration\Model\Integration;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Locale\ResolverInterface;
use Adfix\Squarefeed\Helper\Data as DataHelper;
use Magento\Framework\App\ProductMetadataInterface;

class Main extends Template
{
    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var Integration
     */
    protected $integration;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param ResolverInterface $resolver
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ResolverInterface $resolver,
        ProductMetadataInterface $productMetadata,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->localeResolver = $resolver;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Retrieve iframe url
     *
     * @return string
     */
    public function getIframeUrl()
    {
        return DataHelper::IFRAME_URL . '?platform=' .  $this->getPlatformInfo();
    }

    /**
     * Retrieve store base url
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreBaseUrl()
    {
        return $this->getStore()->getBaseUrl();
    }

    /**
     * Retrieves API url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiUrl()
    {
        return $this->getStore()->getBaseUrl() . 'rest/V1/squarefeed/json';
    }

    /**
     * Retrieve store locale
     *
     * @return string
     */
    public function getStoreLocale()
    {
        return $this->localeResolver->getLocale();
    }

    /**
     * Retrieve store base currency
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreBaseCurrency()
    {
        return $this->getStore()->getBaseCurrencyCode();
    }

    /**
     * Retrieve integration
     *
     * @return Integration
     */
    public function getIntegration()
    {
        if (!$this->integration) {
            $this->integration = $this->dataHelper->getIntegration();
        }
        return $this->integration;
    }

    public function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Retrieve store object
     *
     * @return StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStore()
    {
        if (!$this->store) {
            $this->store = $this->_storeManager->getStore();
        }

        return $this->store;
    }

    /**
     * Retrieve platform info
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPlatformInfo()
    {
        $platformInfo = [
            'platform' => 'magento',
            'version' => $this->getMagentoVersion(),
            'url' => $this->getStoreBaseUrl()
        ];

        return urlencode(json_encode($platformInfo));
    }
}
