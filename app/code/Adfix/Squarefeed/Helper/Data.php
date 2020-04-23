<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Helper;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface as IntegrationOauthService;

class Data extends AbstractHelper
{
    const STATUS_OK = 'OK';
    const STATUS_FAILED = 'FAILED';

    const PRODUCT_UPDATED_AT_TIME_ATTR = 'sf_updated_at';

    const IFRAME_URL = 'https://core.squarefeed.io';
    const XML_META_TAG = 'squarefeed/settings/meta_tag';
    const API_INTEGRATION_NAME = 'Squarefeed';
    const API_INTEGRATION_EMAIL = 'support@squarefeed.io';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var IntegrationOauthService
     */
    protected $integrationOauthService;

    /**
     * Data constructor.
     * @param Context $context
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param IntegrationServiceInterface $integrationService
     * @param IntegrationOauthService $integrationOauthService
     */
    public function __construct(
        Context $context,
        Logger $logger,
        ResolverInterface $localeResolver,
        IntegrationServiceInterface $integrationService,
        IntegrationOauthService $integrationOauthService
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->localeResolver = $localeResolver;
        $this->scopeConfig = $context->getScopeConfig();
        $this->integrationService = $integrationService;
        $this->integrationOauthService = $integrationOauthService;
    }

    /**
     * Retrieve google meta tag
     *
     * @return mixed
     */
    public function getGoogleMetaTag()
    {
        $value = $this->scopeConfig->getValue(
            self::XML_META_TAG,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null
        );

        return $value;
    }

    /**
     * Retrieve integration using name
     *
     * @return \Magento\Integration\Model\Integration
     */
    public function getIntegration()
    {
        /** @var \Magento\Integration\Model\Integration $integration */
        $integration = $this->integrationService->findByName(self::API_INTEGRATION_NAME);
        $consumerId = $integration->getConsumerId();

        /**
         * Set consumer information
         */
        $consumer = $this->getConsumer($consumerId);
        if ($consumer) {
            $integration->setData('consumer_key', $consumer->getKey());
            $integration->setData('consumer_secret', $consumer->getSecret());
        }

        /**
         * Set access token information
         */
        $accessToken = $this->getAccessToken($consumerId);
        if ($accessToken) {
            $integration->setData('token', $accessToken->getToken());
            $integration->setData('token_secret', $accessToken->getSecret());
        }

        return $integration;
    }

    /**
     * Retrieve store locale
     *
     * @param string|null $storeCode
     * @return mixed
     */
    public function getStoreLocale($storeCode = null)
    {
        $locale = $this->scopeConfig->getValue(
            $this->localeResolver->getDefaultLocalePath(),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode);
        if (!$locale) {
            $locale = \Magento\Framework\Locale\Resolver::DEFAULT_LOCALE;
        }

        return $locale;
    }

    /**
     * Use store view code in the base url
     *
     * @return bool
     */
    public function useStoreViewCode()
    {
        return (bool) $this->scopeConfig->getValue(
            \Magento\Store\Model\Store::XML_PATH_STORE_IN_URL,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null);
    }

    /**
     * Retrieve consumer object
     *
     * @param $consumerId
     * @return bool|\Magento\Integration\Model\Oauth\Consumer
     */
    protected function getConsumer($consumerId)
    {
        try {
            return $this->integrationOauthService->loadConsumer($consumerId);
        } catch (\Exception $e) {
            $this->logger->info('[DataHelper.getConsumer] ERROR: ' . $e->getMessage());
            $this->logger->info($e->getTraceAsString());
        }

        return false;
    }

    /**
     * Retrieve token object
     *
     * @param $consumerId
     * @return bool|\Magento\Integration\Model\Oauth\Token
     */
    protected function getAccessToken($consumerId)
    {
        try {
            return $this->integrationOauthService->getAccessToken($consumerId);
        } catch (\Exception $e) {
            $this->logger->info('[DataHelper.getAccessToken] ERROR: ' . $e->getMessage());
            $this->logger->info($e->getTraceAsString());
        }

        return false;
    }
}
