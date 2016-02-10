<?php
namespace Swissup\Email\Controller\Adminhtml\Email\Service;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;
use Swissup\Email\Api\Data\ServiceInterface;
use Swissup\Email\Model\Transport\Factory;

class Check extends Action
{
    /**
     * @var Factory
     */
    protected $transportFactory;

    /**
     * @param Action\Context $context
     * @param Factory $transportFactory
     */
    public function __construct(
        Action\Context $context,
        Factory $transportFactory
    ) {
        parent::__construct($context);
        $this->transportFactory = $transportFactory;
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
            $mailMessage = new \Magento\Framework\Mail\Message();
            $mailMessage->setBodyText('This is test transport mail.');
            $mailMessage->setFrom($email, 'test');
            $mailMessage->addTo($email, 'test');
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
                    . ' Please check your inbox to verify this final.'
                );
                $this->messageManager->addSuccess($successMessage);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
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
