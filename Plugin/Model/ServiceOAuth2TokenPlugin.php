<?php
declare(strict_types=1);

namespace Swissup\Email\Plugin\Model;

use Swissup\Email\Model\Service;
use Swissup\OAuth2Client\Model\AccessTokenRepository;

class ServiceOAuth2TokenPlugin
{
    /**
     *
     * @var \Swissup\Email\Model\ServiceRepository
     */
    private $serviceRepository;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;

    public function __construct(
        \Swissup\Email\Model\ServiceRepository $serviceRepository,
        \Swissup\OAuth2Client\Model\AccessTokenRepository $accessTokenRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->logger = $logger;
    }

    private function isGoogleOAuth2(Service $service): bool
    {
        return $service->getAuth() === Service::AUTH_TYPE_XOAUTH2 && $service->getType() === Service::TYPE_GMAILOAUTH2;
    }

    public function afterAfterCommitCallback(Service $subject): void
    {
        if (!$this->isGoogleOAuth2($subject)) {
            return;
        }
        $tokenId = $subject->getTokenId();
        /** @var $accessToken \Swissup\OAuth2Client\Model\AccessToken */
        $accessToken = $this->accessTokenRepository->getById($tokenId);

        if (!$accessToken->isInitialized()) {
            $callbackUrl = $accessToken->getCallbackUrl();
            $subject->setData('callback_url', $callbackUrl);
        }
    }

    public function afterAfterLoad(Service $subject): void
    {
        if (!$this->isGoogleOAuth2($subject)) {
            return;
        }

        // create new access token record
        $tokenId = $subject->getTokenId();
        if (empty($tokenId)) {
            /** @var \Swissup\OAuth2Client\Model\AccessToken $accessToken */
            $accessToken = $this->accessTokenRepository->create();
            $accessToken->setHasDataChanges(true);
            $accessToken = $this->accessTokenRepository->save($accessToken);
            $tokenId = $accessToken->getId();

            $subject->setTokenId($tokenId);
            $this->serviceRepository->save($subject);
        }

        // refresh creadential
        $tokenId = $subject->getTokenId();
        /** @var \Swissup\OAuth2Client\Model\AccessToken $accessToken */
        $accessToken = $this->accessTokenRepository->getById($tokenId);
        $storedCredentialHash = $accessToken->getCredentialHash();
        $clientId = $subject->getUser();
        $clientSecret = $subject->getPassword();
        $scope = implode(' ', ['https://mail.google.com/']);
        $credential = $accessToken->getCredential();
        $credential->setClientId($clientId)
            ->setClientSecret($clientSecret)
            ->setScope($scope);
        $hash = $credential->getHash();
        if ($storedCredentialHash !== $hash || $credential->isExpired()) {
            $credential->save();
            $accessToken->setCredentialHash($hash);
            $this->accessTokenRepository->save($accessToken);
        }

        // refresh access_token is expired
        $accessToken = $accessToken->runRefreshToken();
        if ($accessToken) {
            $this->accessTokenRepository->save($accessToken);
        }

        $subject->setData('token', $accessToken->getData());
    }
}
