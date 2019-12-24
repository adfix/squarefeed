<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DeletedProductSku extends AbstractDb
{
    public function _construct()
    {
        $this->_init("sf_deleted_products_sku", "entity_id");
    }

    /**
     * Truncate table
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function truncateTable()
    {
        try {
            $this->getConnection()->truncateTable($this->getMainTable());
        } catch (\Exception $e) {

        }
    }
}
