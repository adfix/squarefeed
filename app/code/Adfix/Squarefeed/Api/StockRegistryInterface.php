<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface StockRegistryInterface
{
    /**
     * Retrieve list of all stock items
     *
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList($currentPage = 0, $pageSize = 0);
}
