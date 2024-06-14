<?php
declare(strict_types=1);

namespace Swissup\Email\Plugin\Model;

use Magento\Framework\DataObject;
use Swissup\Email\Model\Service;

class ServiceOAuth2TokenPlugin
{
    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var \Magento\Framework\Url
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var \Swissup\Email\Model\Data\Token
     */
    private $token;

    public function __construct(
        \Swissup\Email\Model\ServiceRepository $serviceRepository,
        \Magento\Framework\Url $urlBuilder,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Swissup\Email\Model\Data\Token $token
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->urlBuilder = $urlBuilder;
        $this->urlEncoder = $urlEncoder;
        $this->token = $token;
    }

    public function afterAfterCommitCallback(Service $subject): void
    {
        if ($subject->getAuth() !== Service::AUTH_TYPE_XOAUTH2 ||
            $subject->getType() !== Service::TYPE_GMAILOAUTH2) {
            return;
        }

        $tokenOptions = $subject->getToken();
        if (empty($tokenOptions)) {
            $urlBuilder = $this->urlBuilder;
            $refererUrl = $urlBuilder->getCurrentUrl();
            $refererUrl = $this->urlEncoder->encode($refererUrl);
            $callbackUrl = $urlBuilder->getUrl(
                'swissup_email/gmail/getOAuth2Token',
                [
                    '_nosid' => true,
                    '_query' => [
                        'id' => $subject->getId(),
                        'referer' => $refererUrl,
                        'token' => $this->token->getToken(),
                    ]
                ]
            );
            $subject->setData('callback_url', $callbackUrl);
        }
    }

    public function afterAfterLoad(Service $subject): void
    {
        if ($subject->getAuth() !== Service::AUTH_TYPE_XOAUTH2 ||
            $subject->getType() !== Service::TYPE_GMAILOAUTH2) {
            return;
        }

        $tokenOptions = $subject->getToken();
        if (empty($tokenOptions)) {
            return;
        }
        $storedToken = new \League\OAuth2\Client\Token\AccessToken($tokenOptions);
        $refreshToken = $storedToken->getRefreshToken();

        if ($storedToken->hasExpired() && !empty($refreshToken)) {
            $urlBuilder = $this->urlBuilder;
            $redirectUri = $urlBuilder->getUrl('swissup_email/gmail/getOAuth2Token');
            /* @var \League\OAuth2\Client\Provider\Google $provider */
            $provider = new \League\OAuth2\Client\Provider\Google([
                'clientId' => $subject->getUser(),
                'clientSecret' => $subject->getPassword(),
                'redirectUri'  => $redirectUri,
//                'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
                'scopes' => ['https://mail.google.com/'],
                'accessType' => 'offline'
            ]);
            $token = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken
            ]);
            $tokenOptions = array_merge($token->jsonSerialize(), ['refresh_token' => $refreshToken]);
            $subject->setToken($tokenOptions);
            $this->serviceRepository->save($subject);
        }
    }
}
