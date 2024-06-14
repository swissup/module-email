<?php
namespace Swissup\Email\Model\Data\Token;

use Magento\Framework\Encryption\Helper\Security;

class Validator
{
    /**
     * @var \Swissup\Email\Model\Data\Token
     */
    private $token;

    /**
     * @param \Swissup\Email\Model\Data\Token $token
     */
    public function __construct(\Swissup\Email\Model\Data\Token $token)
    {
        $this->token = $token;
    }

    /**
     * Validate
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function validate(\Magento\Framework\App\RequestInterface $request)
    {
        $token = $request->getParam('token', null);
        return $token && Security::compareStrings($token, $this->token->getToken());
    }
}
