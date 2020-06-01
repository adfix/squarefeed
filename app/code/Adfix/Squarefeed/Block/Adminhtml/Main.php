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
use Adfix\Squarefeed\Helper\Data as DataHelper;
use Magento\Framework\App\ProductMetadataInterface;

class Main extends Template
{
    const PLATFORM = 'magento';
    const PLATFORM_FOR_SELECTION = 'squarefeed';
    const STORE_URL_FOR_SELECTION = 'https://squarefeed.io';
    const APP_VERSION = '2.0.6';
    const API_URI = 'rest/V1/squarefeed/json';

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
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Retrieve iframe url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIframeUrl()
    {
        return DataHelper::IFRAME_URL . '?platform=' . $this->getPlatformInfo();
    }

    /**
     * Retrieve platform info
     *
     * @return false|string
     */
    public function getPlatformData()
    {
        try {
            $version = $this->getMagentoVersion();
            $credentials = $this->getCredentialsData();
            $stores = $this->_storeManager->getStores();
            $useStoreViewCode = $this->dataHelper->useStoreViewCode();

            /** @var \Magento\Store\Model\Store $store */
            foreach ($stores as $store) {
                if ($useStoreViewCode === false && !$store->isDefault()) {
                    continue;
                }

                $storeData[] = [
                    'credentials' => $credentials,
                    'storeCurrency' => $store->getDefaultCurrencyCode(),
                    'storeLocale' => $this->dataHelper->getStoreLocale($store->getCode()),
                    'storeUrl' => $store->getBaseUrl(),
                    'apiUrl' => $store->getBaseUrl() . self::API_URI,
                    'version' => $version,
                    'platform' => self::PLATFORM,
                    'appVersion' => self::APP_VERSION
                ];
            }
        } catch (\Exception $e) {
            $storeData = [];
        }

        return json_encode($storeData, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Retrieve user data
     *
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUserData()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->getStore();
        $userData = [
            'command' => 'setUser',
            'storeCurrency' => $store->getDefaultCurrencyCode(),
            'storeLocale' => $this->dataHelper->getStoreLocale($store->getCode()),
            'version' => $this->getMagentoVersion(),
            'appVersion' => self::APP_VERSION
        ];

        $allStores = $this->_storeManager->getStores();
        if (count($allStores) > 1) {
            $userData['storeUrl'] = self::STORE_URL_FOR_SELECTION;
            $userData['platform'] = self::PLATFORM_FOR_SELECTION;
        } else {
            $userData['credentials'] = $this->getCredentialsData();
            $userData['storeUrl'] = $store->getBaseUrl();
            $userData['apiUrl'] = $store->getBaseUrl() . self::API_URI;
            $userData['platform'] = self::PLATFORM;
        }

        return json_encode($userData, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Retrieve integration credentials
     *
     * @return array
     */
    protected function getCredentialsData()
    {
        return [
            'consumerKey' => $this->getIntegration()->getConsumerKey(),
            'consumerSecret' => $this->getIntegration()->getConsumerSecret(),
            'accessToken' => $this->getIntegration()->getToken(),
            'accessTokenSecret' => $this->getIntegration()->getTokenSecret()
        ];
    }

    /**
     * Retrieve integration
     *
     * @return Integration
     */
    protected function getIntegration()
    {
        if (!$this->integration) {
            $this->integration = $this->dataHelper->getIntegration();
        }
        return $this->integration;
    }

    /**
     * Retrieve magento version
     *
     * @return string
     */
    protected function getMagentoVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Retrieve store base url
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreBaseUrl()
    {
        return $this->getStore()->getBaseUrl();
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
     */
    protected function getPlatformInfo()
    {
        try {
            $platformInfo = [
                'platform' => 'magento',
                'version' => $this->getMagentoVersion(),
                'url' => $this->getStoreBaseUrl()
            ];
        } catch (\Exception $e) {
            $platformInfo = [];
        }

        return urlencode(json_encode($platformInfo));
    }
}
