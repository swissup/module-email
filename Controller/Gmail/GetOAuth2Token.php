<?php
namespace Swissup\Email\Controller\Gmail;

class GetOAuth2Token extends \Magento\Framework\App\Action\Action
{
    const SESSION_ID_KEY = 'swissup_email_service_id';
    const FLOW_STATE_KEY = 'oauth2state';

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $session;

    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var \Swissup\OAuth2Client\Model\Data\FlowToken
     */
    private $tokenValidator;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Swissup\Email\Model\ServiceRepository $serviceRepository,
        \Magento\Framework\Url\DecoderInterface $urldecoder,
        \Swissup\OAuth2Client\Model\Data\FlowToken $tokenValidator
    ) {
        parent::__construct($context);
        $this->session = $session;
        $this->serviceRepository = $serviceRepository;
        $this->urlDecoder = $urldecoder;
        $this->tokenValidator = $tokenValidator;
    }

    private function isLoggedIn()
    {
        $request = $this->getRequest();
        $serviceId = $request->getParam('id', false);
        $sessionIdKey = self::SESSION_ID_KEY;

        if (!empty($serviceId)) {
            if ($this->tokenValidator->validateRequest($request)) {
                $this->session->setData($sessionIdKey, $serviceId);
            } else {
                $this->session->setData($sessionIdKey, null);
            }
        }

        return (bool) $this->session->getData($sessionIdKey);
    }

    private function getServiceId()
    {
        $sessionIdKey = self::SESSION_ID_KEY;
        return (int) $this->session->getData($sessionIdKey);
    }

    private function redirectReferer()
    {
        $refererUrl = $this->session->getData('referer');
        $refererUrl = empty($refererUrl) ? $this->_redirect->getRedirectUrl() : $refererUrl;
        return $this->_redirect($refererUrl);
    }

    /**
     * Post user question
     *
     * @inherit
     */
    public function execute()
    {
        if (!$this->isLoggedIn()) {
            return $this->redirectReferer();
        }

        $id = $this->getServiceId();
        $service = $this->serviceRepository->getById($id);

        $request = $this->getRequest();
        $refererUrl = $request->getParam('referer');
        $refererUrl = $this->urlDecoder->decode($refererUrl);
        if (!empty($refererUrl)) {
            $this->session->setData('referer', $refererUrl);
        }

        /* @var \League\OAuth2\Client\Provider\Google $provider */
        $provider = new \League\OAuth2\Client\Provider\Google([
            'clientId'     => $service->getUser(),
            'clientSecret' => $service->getPassword(),
            'redirectUri'  => $this->_url->getUrl('*/*/*'),
//            'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
            'scopes' => ['https://mail.google.com/'],
            'accessType' => 'offline'
        ]);

        $errorParam = $request->getParam('error');
        $codeParam = $request->getParam('code');
        $stateParam = $request->getParam('state');

        if (!empty($errorParam)) {
            $this->messageManager->addErrorMessage(
                htmlspecialchars($errorParam, ENT_QUOTES, 'UTF-8')
            );
            return $this->redirectReferer();
        } elseif (empty($codeParam)) {

            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl(/*['scope' => ['https://mail.google.com/']]*/);
            $this->session->setData(self::FLOW_STATE_KEY, $provider->getState());

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($authUrl);

            return $resultRedirect;
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($stateParam) || ($stateParam !== $this->session->getData(self::FLOW_STATE_KEY))) {
            $this->session->setData(self::FLOW_STATE_KEY, null);
            $this->messageManager->addErrorMessage(
                __('Invalid state')
            );
            return $this->redirectReferer();
        } else {
            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $codeParam
            ]);

            $refreshToken = $token->getRefreshToken();
            if (empty($refreshToken)) {
                $authUrl = $provider->getAuthorizationUrl(['prompt' => 'consent', 'access_type' => 'offline']);
                $this->session->setData(self::FLOW_STATE_KEY, $provider->getState());

                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($authUrl);
                return $resultRedirect;
            }
            $this->session->setData(self::FLOW_STATE_KEY, null);

            $tokenOptions = array_merge($token->jsonSerialize(), ['refresh_token' => $refreshToken]);
            $service->setToken($tokenOptions);
            $this->serviceRepository->save($service);
        }

        return $this->redirectReferer();
    }
}
