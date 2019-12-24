<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface ShippingInfoInterface
{
    /**
     * Retrieve shipping settings and all shipping methods
     *
     * @param int $activeMethods
     * @return array
     */
    public function getShippingInfo($activeMethods = 1);
}
