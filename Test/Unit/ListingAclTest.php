<?php
declare(strict_types=1);

namespace Swissup\Email\Test\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListingAclTest extends TestCase
{
    public static function listingProvider(): array
    {
        return [
            'service grid' => ['email_service_listing', 'Swissup_Email::service'],
            'history grid' => ['email_history_listing', 'Swissup_Email::history'],
        ];
    }

    #[DataProvider('listingProvider')]
    public function testDataProviderDeclaresAclResource(string $listing, string $resource): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../view/adminhtml/ui_component/' . $listing . '.xml');
        $nodes = $xml->xpath(
            '//dataSource/argument[@name="dataProvider"]/argument[@name="data"]'
            . '/item[@name="config"]/item[@name="aclResource"]'
        );

        $this->assertCount(1, $nodes);
        $this->assertSame($resource, (string) $nodes[0]);
    }
}
