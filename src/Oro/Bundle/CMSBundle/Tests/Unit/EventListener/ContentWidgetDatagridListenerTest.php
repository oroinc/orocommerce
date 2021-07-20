<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\EventListener\ContentWidgetDatagridListener;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;

class ContentWidgetDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->contentWidgetTypeRegistry->expects($this->any())
            ->method('getTypes')
            ->willReturn(
                [
                    new ContentWidgetTypeStub(),
                    new ImageSliderContentWidgetType($this->createMock(ManagerRegistry::class)),
                ]
            );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     */
    public function testOnPreBuild(bool $isInline, array $expected): void
    {
        $event = new PreBuild(DatagridConfiguration::create([]), new ParameterBag());

        $listener = new ContentWidgetDatagridListener($this->contentWidgetTypeRegistry, $isInline);
        $listener->onPreBuild($event);

        $this->assertEquals($expected, $event->getParameters()->get('contentWidgetTypes'));
    }

    public function onPreBuildDataProvider(): array
    {
        return [
            'inline' => [
                'isInline' => true,
                'expected' => [ContentWidgetTypeStub::getName()],
            ],
            'block' => [
                'isInline' => false,
                'expected' => [ImageSliderContentWidgetType::getName()],
            ],
        ];
    }
}
