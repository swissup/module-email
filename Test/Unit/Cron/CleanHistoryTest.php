<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\TestCase;
use Swissup\Email\Cron\CleanHistory;

class CleanHistoryTest extends TestCase
{
    private function create(string $days, AdapterInterface $connection): CleanHistory
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('getValue')->with('email/default/log_retention_days')->willReturn($days);

        return new CleanHistory($resource, $config);
    }

    public function testOldRowsAreDeleted(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->once())->method('delete')->with(
            'swissup_email_history',
            $this->callback(function (array $where): bool {
                $limit = strtotime($where['created_at < ?'] . ' UTC');
                return abs($limit - (time() - 7 * 86400)) < 5;
            })
        );

        $this->create('7', $connection)->execute();
    }

    public function testZeroKeepsEverything(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->never())->method('delete');

        $this->create('0', $connection)->execute();
    }
}
