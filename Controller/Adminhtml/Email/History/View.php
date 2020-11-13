<?php
namespace Swissup\Email\Controller\Adminhtml\Email\History;

class View extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     *
     * @var \Swissup\Email\Model\HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var \Magento\Framework\Filter\Input\MaliciousCode
     */
    protected $maliciousCode;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Swissup\Email\Model\HistoryFactory $historyFactory
     * @param \Magento\Framework\Filter\Input\MaliciousCode $maliciousCode
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Swissup\Email\Model\HistoryFactory $historyFactory,
        \Magento\Framework\Filter\Input\MaliciousCode $maliciousCode
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->historyFactory = $historyFactory;
        $this->maliciousCode = $maliciousCode;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Swissup_Email::history_save');
    }

    /**
     * View
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        $body = '';
        if ($id) {
            $historyEntry = $this->historyFactory->create();
            $historyEntry->load($id);

            $body = $historyEntry->getBody();
            $body = $this->maliciousCode->filter($body);
        }

        $this->getResponse()->setHeader('Content-Security-Policy', "script-src 'none'");
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($body);
    }
}
