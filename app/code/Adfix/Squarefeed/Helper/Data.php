<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Helper;

use Adfix\Squarefeed\Logger\Logger;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface as IntegrationOauthService;

class Data extends AbstractHelper
{
    const STATUS_OK = 'OK';
    const STATUS_FAILED = 'FAILED';

    const PRODUCT_UPDATED_AT_TIME_ATTR = 'sf_updated_at';

    const IFRAME_URL = 'https://m2test.squarefeed.io:8088';
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
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var IntegrationOauthService
     */
    protected $integrationOauthService;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param Logger $logger
     * @param IntegrationServiceInterface $integrationService
     * @param IntegrationOauthService $integrationOauthService
     */
    public function __construct(
        Context $context,
        Logger $logger,
        IntegrationServiceInterface $integrationService,
        IntegrationOauthService $integrationOauthService
    ) {
        parent::__construct($context);

        $this->logger = $logger;
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
