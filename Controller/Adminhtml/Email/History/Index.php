<?php
namespace Swissup\Email\Controller\Adminhtml\Email\History;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Email::history';

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
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Swissup_Email::history');
        $resultPage->addBreadcrumb(__('Email Logs'), __('Email Logs'));
        $resultPage->addBreadcrumb(__('Manage Email Logs'), __('Manage Email Logs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Email Logs'));

        return $resultPage;
    }
}
