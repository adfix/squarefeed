<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface ProductLinksInterface
{
    /**
     * Retrieve product links list
     * configurable/bundle/grouped and their children
     *
     * @param int $lastUpdateTime
     * @return array
     */
    public function getList($lastUpdateTime = 0);
}
