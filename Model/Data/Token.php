<?php

namespace Swissup\Email\Model\Data;

class Token
{
    const CACHE_ID = '_swissup_email_token';
    const CACHE_TAG = 'swissup_email';
    const LIFETIME = 3600; // 1 hour

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $mathRandom;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $cache;

    /**
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontend
     */
    public function __construct(
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontend
    ) {
        $this->mathRandom = $mathRandom;
        $this->cache = $cacheFrontend->get(\Magento\Framework\App\Cache\Frontend\Pool::DEFAULT_FRONTEND_ID);
    }

    /**
     * Retrieve State Token
     *
     * @return string A 16 bit unique key
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getToken()
    {
        if (!$this->isPresent()) {
            $this->set($this->mathRandom->getRandomString(16));
        }
        return $this->cache->load(self::CACHE_ID);
    }

    /**
     * Determine if the token is present in the 'session'
     *
     * @return bool
     */
    public function isPresent()
    {
        return (bool) $this->cache->test(self::CACHE_ID);
    }

    /**
     * Save the value of the token
     *
     * @param string $value
     * @return void
     */
    public function set($value)
    {
        $this->cache->save((string)$value, self::CACHE_ID, [self::CACHE_TAG], self::LIFETIME);
    }
}
