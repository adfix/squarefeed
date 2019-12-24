<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Setup;

use Adfix\Squarefeed\Helper\Data;
use Adfix\Squarefeed\Logger\Logger;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Integration\Model\Integration;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Api\IntegrationServiceInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OauthServiceInterface
     */
    protected $oauthService;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * UpgradeData constructor.
     *
     * @param Logger $logger
     * @param EavSetupFactory $eavSetupFactory
     * @param OauthServiceInterface $oauthService
     * @param IntegrationServiceInterface $integrationService
     */
    public function __construct(
        Logger $logger,
        EavSetupFactory $eavSetupFactory,
        OauthServiceInterface $oauthService,
        IntegrationServiceInterface $integrationService
    )
    {
        $this->logger = $logger;
        $this->oauthService = $oauthService;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->integrationService = $integrationService;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $integrationData = [
                'name' => Data::API_INTEGRATION_NAME,
                'email' => Data::API_INTEGRATION_EMAIL,
                'resource' => $this->getIntegrationResources()
            ];

            try {
                /** @var Integration $integration */
                $integration = $this->integrationService->create($integrationData);
                if ($this->oauthService->createAccessToken($integration->getConsumerId(), 0)) {
                    $integration->setStatus(Integration::STATUS_ACTIVE)->save();
                }
            } catch (\Exception $e) {
                $this->logger->info('[UpgradeData] ERROR: ' . $e->getMessage());
                $this->logger->info('Error Code - ' . $e->getCode());
                $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
                $this->logger->info($e->getTraceAsString());
            }
        }

        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                \Adfix\Squarefeed\Helper\Data::PRODUCT_UPDATED_AT_TIME_ATTR,
                [
                    'wysiwyg_enabled' => false,
                    'html_allowed_on_front' => false,
                    'used_for_sort_by' => false,
                    'filterable' => false,
                    'filterable_in_search' => false,
                    'used_in_grid' => false,
                    'visible_in_grid' => false,
                    'filterable_in_grid' => false,
                    'position' => 0,
                    'apply_to' => 'simple,downloadable,virtual,bundle,configurable,grouped',
                    'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                    'searchable' => false,
                    'visible_in_advanced_search' => false,
                    'comparable' => false,
                    'used_for_promo_rules' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'visible' => false,
                    'scope' => 'global',
                    'input' => 'date',
                    'entity_type_id' => 4,
                    'required' => false,
                    'user_defined' => false,
                    'label' => 'Squarefeed Product Updated At',
                    'type' => 'datetime',
                    'unique' => false,
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            try {
                /** @var Integration $integration */
                $integration = $this->integrationService->findByName(Data::API_INTEGRATION_NAME);
                if ($integration->getId()) {
                    $integrationData = $integration->getData();
                    $integrationData['resource'] = $this->getIntegrationResources();

                    $this->integrationService->update($integrationData);
                }
            } catch (\Exception $e) {
                $this->logger->info('[UpgradeData] ERROR: ' . $e->getMessage());
                $this->logger->info('Error Code - ' . $e->getCode());
                $this->logger->info('Line - ' . $e->getLine() . ', ' . $e->getFile());
                $this->logger->info($e->getTraceAsString());
            }
        }

        $installer->endSetup();
    }

    /**
     * Retrieve list of resources for integration
     *
     * @return array
     */
    protected function getIntegrationResources()
    {
        return [
            'Magento_Catalog::catalog',
            'Magento_Catalog::catalog_inventory',
            'Magento_Catalog::products',
            'Magento_Catalog::categories',
            'Magento_Backend::store',
            'Magento_Backend::stores',
            'Magento_Backend::stores_settings',
            'Magento_Config::config',
            'Magento_GoogleAnalytics::google',
            'Magento_Contact::contact',
            'Magento_Shipping::carriers',
            'Magento_Config::config_general',
            'Magento_Config::web',
            'Magento_Tax::config_tax',
            'Magento_Backend::stores_attributes',
            'Magento_Catalog::attributes_attributes',
            'Magento_Catalog::sets',
            'Adfix_Squarefeed::squarefeed'
        ];
    }
}
