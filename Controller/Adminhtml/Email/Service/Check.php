<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;
use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Mail\TransportFactory;

class Check extends Action
{
    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @var TransportFactory
     */
    protected $transportFactory;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     *
     * @var \Magento\Backend\Model\Session
     */
    private $session;

    /**
     * @param Action\Context $context
     * @param \Swissup\Email\Model\ServiceRepository $serviceRepository
     * @param TransportFactory $transportFactory
     * @param \Magento\Framework\Math\Random $random
     */
    public function __construct(
        Action\Context $context,
        \Swissup\Email\Model\ServiceRepository $serviceRepository,
        TransportFactory $transportFactory,
        \Magento\Framework\Math\Random $random
    ) {
        parent::__construct($context);

        $this->serviceRepository = $serviceRepository;
        $this->transportFactory = $transportFactory;
        $this->random = $random;
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

            // $verifyCode = $this->random->getRandomString(5);
            // $verifyCode = $this->random->getRandomNumber(0, 99999) / 100000;
            $verifyCode = (float)rand()/(float)getrandmax();
            $verifyCode = (string) $verifyCode;
            $verifyCode = trim($verifyCode);
            if (substr($verifyCode, 0, 2) === "0.") {
                $verifyCode = substr($verifyCode, 2);
            }
            $verifyCode = base_convert($verifyCode, 10, 36);
            // $verifyCode .= '1234567';
            $verifyCode = substr($verifyCode, 2, 11);

            $mailMessage = new \Magento\Framework\Mail\Message();
            $messageText = "This is test transport mail. Verification code : {$verifyCode} .";
            // $mailMessage->setBodyText($messageText);
            try {
                $mailMessage->setBodyHtml("<p>{$messageText}</p>");
                $mailMessage->setFrom($email/*, 'test'*/);

                $replacePlaceholder = str_repeat('x', 9);
                $webTesterPrefix = str_replace($replacePlaceholder, $verifyCode, 'test-' . $replacePlaceholder);
                $webTesterEmail = $webTesterPrefix . '@srv1.mail-tester.com';

                $mailMessage->addTo($webTesterEmail/*, 'webtester'*/);
    //            $mailMessage->addTo($email, 'test');

                $mailMessage->setSubject("Test Email Transport ({$verifyCode})");

                $transport = $this->transportFactory->create([
                    'message' => $mailMessage
                ]);
                $transport->setService($service);

                $transport->sendMessage();
                $successMessage = __(
                    'Connection with mail server was successfully established.'
                    . ' Please check your inbox ' . $email . ' to verify.'
                    . " Verification code : {$verifyCode}."
                    . ' Or click <a href="https://www.mail-tester.com/' . $webTesterPrefix . '">here</a>.'
                );
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
//                 $this->messageManager->addError($e->getTraceAsString());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError(
                    __('Something went wrong while checking the service.')
                    . " Original error message : " . $e->getMessage()
                );
//                $this->messageManager->addError($e->getTraceAsString());
            } catch (\Exception $e) {
                $this->messageManager->addError(
//                    $e,
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
