<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\ProductLinks;

interface ProductOptionsInterface
{
    /**
     * Prepare product linked options
     *
     * @param string $lastUpdateDate
     * @return array
     */
    public function prepareData($lastUpdateDate = '');
}
