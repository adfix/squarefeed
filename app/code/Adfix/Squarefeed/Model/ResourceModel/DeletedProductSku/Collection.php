<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\ResourceModel\DeletedProductSku;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            \Adfix\Squarefeed\Model\DeletedProductSku::class,
            \Adfix\Squarefeed\Model\ResourceModel\DeletedProductSku::class
        );
    }

    /**
     * Returns pairs entity_id - sku
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('entity_id', 'sku');
    }
}
