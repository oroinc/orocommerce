<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\Search;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\Search\ProjectNameSearchListener;
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

    public function testCollectEntityMapEventWhenRfqProjectNameDisabled(): void
    {
        $config = [
            Request::class => [
                'fields' => [
                    ['name' => 'poNumber', 'target_type' => 'text', 'target_fields' => ['poNumber']]
                ]
            ]
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_rfp.enable_rfq_project_name')
            ->willReturn(false);

        $event = new SearchMappingCollectEvent($config);
        $this->listener->collectEntityMapEvent($event);

        self::assertSame($config, $event->getMappingConfig());
    }

    public function testCollectEntityMapEventWhenRfqProjectNameEnabled(): void
    {
        $config = [
            Request::class => [
                'fields' => [
                    ['name' => 'poNumber', 'target_type' => 'text', 'target_fields' => ['poNumber']]
                ]
            ]
        ];

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_rfp.enable_rfq_project_name')
            ->willReturn(true);

        $event = new SearchMappingCollectEvent($config);
        $this->listener->collectEntityMapEvent($event);

        self::assertSame(
            [
                Request::class => [
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
