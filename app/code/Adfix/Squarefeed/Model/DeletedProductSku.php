<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Magento\Framework\Model\AbstractModel;

class DeletedProductSku extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Adfix\Squarefeed\Model\ResourceModel\DeletedProductSku::class);
    }
}
