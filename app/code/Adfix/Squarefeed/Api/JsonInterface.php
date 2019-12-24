<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Api;

interface JsonInterface
{
    /**
     * Retrieve json data
     *
     * @param int $lastUpdateTime
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList($lastUpdateTime = 0, $currentPage = 0, $pageSize = 0);
}
