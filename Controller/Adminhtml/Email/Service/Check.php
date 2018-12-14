<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;
use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Mail\Transport\Factory;

class Check extends Action
{
    /**
     * @var Factory
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
     * @param Factory $transportFactory
     * @param \Magento\Framework\Math\Random $random
     */
    public function __construct(
        Action\Context $context,
        Factory $transportFactory,
        \Magento\Framework\Math\Random $random
    ) {
        parent::__construct($context);

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

            $email = $data['email'];
            if (empty($email)) {
                $email = $data['user'];
            }
            $verifyCode = $this->random->getRandomString(5);
            $mailMessage = new \Magento\Framework\Mail\Message();
            $messageText = "This is test transport mail. Verification code : {$verifyCode} .";
            $mailMessage->setBodyText($messageText);
            $mailMessage->setBodyHtml("<p>{$messageText}</p>");
            $mailMessage->setFrom($email, 'test');
            $mailMessage->addTo($email, 'test');

            $webTesterEmail = str_replace('xxxxx', $verifyCode, 'web-xxxxx@mail-tester.com');
            $mailMessage->addTo($webTesterEmail, 'web mail tester');

            $mailMessage->setSubject('Test Email Transport ()');

            try {
                $args = [
                    'message' => $mailMessage,
                    'config' => $data
                ];
                switch ($data['type']) {
                    case ServiceInterface::TYPE_GMAIL:
                        $type = 'Gmail';
                        break;
                    case ServiceInterface::TYPE_SMTP:
                        $type = 'Smtp';
                        break;
                    case ServiceInterface::TYPE_SES:
                        $type = 'Ses';
                        break;
                    case ServiceInterface::TYPE_MANDRILL:
                        $type = 'Mandrill';
                        break;
                    case ServiceInterface::TYPE_SENDMAIL:
                    default:
                        $type = 'Sendmail';
                        break;
                }
                $transport = $this->transportFactory->create($type, $args);

                $transport->sendMessage();
                $successMessage = __(
                    'Connection with mail server was succesfully established.'
                    . ' Please check your inbox ' . $email . ' to verify.'
                    . " Verification code : {$verifyCode}."
                    . ' Or click <a href="https://www.mail-tester.com/' . $webTesterEmail . '">here</a>.'
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
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while checking the service.')
                    . $e->getMessage()
                );
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
