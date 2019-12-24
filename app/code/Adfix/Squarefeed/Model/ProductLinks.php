<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Adfix\Squarefeed\Api\ProductLinksInterface;
use Adfix\Squarefeed\Model\ProductLinks\ProductOptionsInterface;

class ProductLinks implements ProductLinksInterface
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ProductOptionsInterface
     */
    protected $productOptions;

    /**
     * ProductLinks constructor.
     *
     * @param DateTime $dateTime
     * @param ProductOptionsInterface $productOptions
     */
    public function __construct(DateTime $dateTime, ProductOptionsInterface $productOptions)
    {
        $this->dateTime = $dateTime;
        $this->productOptions = $productOptions;
    }

    /**
     * Retrieves products list with linked children and their options
     *
     * @param int $lastUpdateTime
     * @return array
     */
    public function getList($lastUpdateTime = 0)
    {
        $lastUpdateTime = ((int)$lastUpdateTime === 0) ?
            '' :
            $this->dateTime->gmtDate(null, (int)$lastUpdateTime);

        $productLinks = $this->productOptions->prepareData($lastUpdateTime);
        $response = [
            'status' => 'OK',
            'productLink' => $productLinks,
            'timestamp' => $this->dateTime->gmtTimestamp()
        ];
        return [$response];
    }
}
