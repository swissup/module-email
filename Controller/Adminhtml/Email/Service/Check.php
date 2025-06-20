<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;

class Check extends Action
{
    /**
     * @var \Swissup\Email\Model\ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @var \Swissup\Email\Service\EmailTestService
     */
    private $emailTestService;

    /**
     * @var \Magento\Backend\Model\Session
     */
    private $session;

    /**
     * @param Action\Context $context
     * @param \Swissup\Email\Model\ServiceRepository $serviceRepository
     * @param \Swissup\Email\Service\EmailTestService $emailTestService
     * @param \Magento\Framework\Math\Random $random
     */
    public function __construct(
        Action\Context $context,
        \Swissup\Email\Model\ServiceRepository $serviceRepository,
        \Swissup\Email\Service\EmailTestService $emailTestService
    ) {
        parent::__construct($context);

        $this->serviceRepository = $serviceRepository;
        $this->emailTestService = $emailTestService;
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
     * Check Email transport service
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $request = $this->getRequest();
        $uenc = $request->getParam('uenc');
        $uenc = base64_decode($uenc, true);
        $data = [];
        parse_str($uenc, $data);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $id = $data['id'];

            $service = $this->serviceRepository->create();
            if ($id) {
                $service = $this->serviceRepository->getById($id);
            }

            $service->addData($data);

            $email = $data['email'];
            if (empty($email)) {
                $email = $data['user'];
            }
            try {
                $this->emailTestService
                    ->setService($service)
                    ->setFrom($email)
                    ->send();
                $successMessage = $this->emailTestService->getSuccessMessage();
                $successMessage = __($successMessage);
                $this->messageManager->addSuccess($successMessage);
                $this->session->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['id' => $id, '_current' => true]
                    );
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError(
                    __('Something went wrong while checking the service.')
                    . " Original error message : ". $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError(
                    __('Something went wrong while checking the service.')
                    . " Original error message : " . $e->getMessage()
                );
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while checking the service.')
                    . " Original error message : " . $e->getMessage()
                );
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
