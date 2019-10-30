<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\EventListener\ContentWidgetDatagridListener;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

class ContentWidgetDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject $contentWidgetTypeRegistry */
        $contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $contentWidgetTypeRegistry->expects($this->any())
            ->method('getWidgetType')
            ->willReturnMap(
                [
                    [ContentWidgetTypeStub::getName(), new ContentWidgetTypeStub()]
                ]
            );

        $this->listener = new ContentWidgetDatagridListener($contentWidgetTypeRegistry);
    }

    public function testOnResultAfter(): void
    {
        $event = new PreBuild(DatagridConfiguration::create([]), new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'properties' => [
                    'inline' => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                    ]
                ]
            ],
            $event->getConfig()->toArray()
        );
    }

    public function testOnPreBuild(): void
    {
        $record1 = new ResultRecord(['widgetType' => 'unknown_type']);
        $record2 = new ResultRecord(['widgetType' => ContentWidgetTypeStub::getName()]);

        $event = new OrmResultAfter(
            new Datagrid('test', DatagridConfiguration::create([]), new ParameterBag()),
            [$record1, $record2]
        );

        $this->listener->onResultAfter($event);

        $this->assertFalse($record1->getValue('inline'));
        $this->assertTrue($record2->getValue('inline'));
    }
}
