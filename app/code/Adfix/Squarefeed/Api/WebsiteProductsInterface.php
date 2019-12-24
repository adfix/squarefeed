<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface WebsiteProductsInterface
{
    /**
     * Retrieve website ids list and assigned products
     *
     * @return array
     */
    public function getWebsiteProducts();
}
