<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;

class Save extends Action
{
    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    protected $serviceRepository;

    /**
     *
     * @var \Magento\Backend\Model\Session
     */
    private $session;

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
        $this->session = $context->getSession();
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Swissup_Email::service_save');
    }

    /**
     * save Email service
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $model = $this->serviceRepository->create();

            $id = (int) $request->getParam('id');
            if ($id) {
                $model = $this->serviceRepository->getById($id);
            } else {
                unset($data['id']);
            }

            $model->addData($data);
            // $this->_eventManager->dispatch(
            //     'swissup_email_service_prepare_save',
            //     ['item' => $model, 'request' => $request]
            // );

            try {
                $this->serviceRepository->save($model);
                $this->messageManager->addSuccess(__('Service succesfully saved.'));
                $this->session->setFormData(false);

                if ($model->hasData('callback_url')) {
                     $callbackUrl = $model->getData('callback_url');
                     return $resultRedirect->setUrl($callbackUrl);
                }

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['id' => $model->getId(), '_current' => true]
                    );
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while saving the service.')
                );
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $request->getParam('id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
