<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Swissup_Email::service';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
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
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        // \Zend_Debug::dump(__METHOD__);
        // die;
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Swissup_Email::service');
        $resultPage->addBreadcrumb(__('Email Services'), __('Email Services'));
        $resultPage->addBreadcrumb(__('Manage Email Services'), __('Manage Email Services'));
        $resultPage->getConfig()->getTitle()->prepend(__('Email Services'));

        return $resultPage;
    }

}