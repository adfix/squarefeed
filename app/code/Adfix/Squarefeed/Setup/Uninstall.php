<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Setup;

use Adfix\Squarefeed\Helper\Data;
use Adfix\Squarefeed\Logger\Logger;
use Magento\Integration\Model\Integration;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Integration\Api\IntegrationServiceInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OauthServiceInterface
     */
    protected $oauthService;

    /**
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * Uninstall constructor.
     *
     * @param Data $data
     * @param OauthServiceInterface $oauthService
     * @param IntegrationServiceInterface $integrationService
     */
    public function __construct(
        Data $data,
        OauthServiceInterface $oauthService,
        IntegrationServiceInterface $integrationService
    ) {
        $this->data = $data;
        $this->oauthService = $oauthService;
        $this->integrationService = $integrationService;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /** @var Integration $integration */
        $integration = $this->data->getIntegration();
        $consumerId = $integration->getConsumerId();

        try {
            $this->integrationService->delete($integration->getId());
            $this->oauthService->deleteIntegrationToken($consumerId);
            $this->oauthService->deleteConsumer($consumerId);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            $this->logger->info($e->getTraceAsString());
        }

        $installer->endSetup();
    }
}
