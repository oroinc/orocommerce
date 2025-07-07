<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Search;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\Search\ProjectNameSearchListener;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectNameSearchListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ProjectNameSearchListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ProjectNameSearchListener($this->configManager);
    }

    public function testCollectEntityMapEventWhenQuoteProjectNameDisabled(): void
    {
        $config = [
            Quote::class => [
                'fields' => [
                    ['name' => 'poNumber', 'target_type' => 'text', 'target_fields' => ['poNumber']]
                ]
            ]
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_sale.enable_quote_project_name')
            ->willReturn(false);

        $event = new SearchMappingCollectEvent($config);
        $this->listener->collectEntityMapEvent($event);

        self::assertSame($config, $event->getMappingConfig());
    }

    public function testCollectEntityMapEventWhenQuoteProjectNameEnabled(): void
    {
        $config = [
            Quote::class => [
                'fields' => [
                    ['name' => 'poNumber', 'target_type' => 'text', 'target_fields' => ['poNumber']]
                ]
            ]
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_sale.enable_quote_project_name')
            ->willReturn(true);

        $event = new SearchMappingCollectEvent($config);
        $this->listener->collectEntityMapEvent($event);

        self::assertSame(
            [
                Quote::class => [
                    'fields' => [
                        ['name' => 'poNumber', 'target_type' => 'text', 'target_fields' => ['poNumber']],
                        ['name' => 'projectName', 'target_type' => 'text', 'target_fields' => ['projectName']]
                    ]
                ]
            ],
            $event->getMappingConfig()
        );
    }
}
