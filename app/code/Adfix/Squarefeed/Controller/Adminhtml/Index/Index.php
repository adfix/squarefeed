<?php
/**
 * @package Adfix_Squarefeed
 * @author  Alona Tsarova
 */

namespace Adfix\Squarefeed\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Adfix_Squarefeed::squarefeed');
        $page->getConfig()->getTitle()->prepend(__('Google Shopping Ads by Squarefeed'));

        return $page;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Adfix_Squarefeed::squarefeed');
    }
}
