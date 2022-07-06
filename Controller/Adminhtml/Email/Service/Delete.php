<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;

class Delete extends \Magento\Backend\App\Action
{
    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @param Action\Context $context
     * @param \Swissup\Email\Model\ServiceRepository $serviceRepository
     */
    public function __construct(
        Action\Context $context,
        \Swissup\Email\Model\ServiceRepository $serviceRepository
    ) {
        parent::__construct($context);
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Swissup_Email::service_save');
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                // init model and delete
                $this->serviceRepository->deleteById($id);
                // display success message
                $this->messageManager->addSuccess(__('The email service has been deleted.'));
                // go to grid
                return $resultRedirect->setPath('*/*/index');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a row to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
