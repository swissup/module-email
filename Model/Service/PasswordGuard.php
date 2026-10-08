<?php

declare(strict_types=1);

namespace Swissup\Email\Model\Service;

use Magento\Framework\Exception\LocalizedException;
use Swissup\Email\Api\Data\ServiceInterface;

/**
 * The service form never shows the stored password, so an empty password field means "keep it".
 * The stored password is only kept while it still goes to the same host with the same user,
 * otherwise it could be sent to an arbitrary server.
 */
class PasswordGuard
{
    private const BOUND_FIELDS = ['host', 'port', 'user'];

    /**
     * @param ServiceInterface $service Stored service
     * @param array $data Submitted form data
     * @return array Form data that is safe to merge into the stored service
     * @throws LocalizedException
     */
    public function apply(ServiceInterface $service, array $data): array
    {
        if (!$service->getId() || (string) ($data['password'] ?? '') !== '') {
            return $data;
        }

        foreach (self::BOUND_FIELDS as $field) {
            if (isset($data[$field]) && (string) $data[$field] !== (string) $service->getData($field)) {
                throw new LocalizedException(
                    __('Please enter the password again when the host, port or user is changed.')
                );
            }
        }
        unset($data['password']);

        return $data;
    }
}
