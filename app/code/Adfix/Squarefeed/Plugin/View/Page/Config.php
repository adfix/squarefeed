<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */
namespace Adfix\Squarefeed\Plugin\View\Page;

use Adfix\Squarefeed\Helper\Data;
use Magento\Framework\View\Page\Config as PageConfig;

class Config
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * Config constructor.
     *
     * @param Data $data
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    /**
     * @param PageConfig $subject
     *
     * @param $result
     * @return string
     */
    public function afterGetIncludes(PageConfig $subject, $result)
    {
        $googleMetaTag = $this->data->getGoogleMetaTag();
        if ($googleMetaTag !== null) {
            $result .= $googleMetaTag;
        }
        return $result;
    }
}
