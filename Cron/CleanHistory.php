<?php

declare(strict_types=1);

namespace Swissup\Email\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Removes logged emails older than the configured retention period.
 */
class CleanHistory
{
    public const XML_PATH_RETENTION_DAYS = 'email/default/log_retention_days';

    private ResourceConnection $resource;

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ResourceConnection $resource, ScopeConfigInterface $scopeConfig)
    {
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(): void
    {
        $days = (int) $this->scopeConfig->getValue(self::XML_PATH_RETENTION_DAYS);
        if ($days <= 0) {
            return;
        }

        $connection = $this->resource->getConnection();
        $connection->delete(
            $this->resource->getTableName('swissup_email_history'),
            ['created_at < ?' => gmdate('Y-m-d H:i:s', time() - $days * 86400)]
        );
    }
}
