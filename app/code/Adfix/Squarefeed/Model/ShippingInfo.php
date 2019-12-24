<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Api\ShippingInfoInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ShippingInfo implements ShippingInfoInterface
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $shippingConfig;

    /**
     * ShippingInfo constructor.
     *
     * @param DateTime $dateTime
     * @param Config $shippingConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DateTime $dateTime,
        Config $shippingConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Retrieves shipping settings and methods
     *
     * @param int $activeMethods
     * @return array
     */
    public function getShippingInfo($activeMethods = 1)
    {
        $response = [
            'status' => 'OK',
            'settings' => $this->scopeConfig->getValue(
                'shipping/origin',
                ScopeInterface::SCOPE_STORE
            )
        ];

        $carriers = $this->shippingConfig->getAllCarriers();
        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierModel->isActive() && (bool)$activeMethods === true) {
                continue;
            }
            $carrierMethods = $carrierModel->getAllowedMethods();
            if (!$carrierMethods) {
                continue;
            }

            $response['methods'][] = $this->removeSecureInfo(
                $this->scopeConfig->getValue(
                    'carriers/' . $carrierCode,
                    ScopeInterface::SCOPE_STORE
                )
            );
        }
        $response['timestamp'] = $this->dateTime->gmtTimestamp();
        return [$response];
    }

    /**
     * Clear method info array from secure info
     *
     * @param array $methodInfo
     * @return mixed
     */
    protected function removeSecureInfo($methodInfo)
    {
        if (!is_array($methodInfo)) {
            return [];
        }

        foreach ($methodInfo as $key => $value) {
            if (strpos($key, 'password') !== false ||
                strpos($key, 'user') !== false ||
                strpos($key, 'model') !== false
            ) {
                unset($methodInfo[$key]);
            }
        }

        return $methodInfo;
    }
}
