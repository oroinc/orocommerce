<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Widget;

use Oro\Bundle\CMSBundle\Widget\WidgetInterface;
use Oro\Bundle\CMSBundle\Widget\WidgetRegistry;
use Psr\Log\LoggerInterface;

class WidgetRegistryTest extends \PHPUnit\Framework\TestCase
{
    const WIDGET_ALIAS = 'widget_alias';
    /** @var WidgetRegistry */
    private $registry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->registry = new WidgetRegistry($this->logger);
    }

    public function testGetWidget()
    {
        $renderedContent = '<div>rendered content</div>';
        /** @var WidgetInterface|\PHPUnit\Framework\MockObject\MockObject $widget */
        $widget = $this->createMock(WidgetInterface::class);
        $widget->expects($this->once())
            ->method('render')
            ->with(['foo' => 'bar'])
            ->willReturn($renderedContent);

        $this->registry->registerWidget(self::WIDGET_ALIAS, $widget);

        $this->assertEquals(
            $renderedContent,
            $this->registry->getWidget(self::WIDGET_ALIAS, ['foo' => 'bar'])
        );
    }

    public function testGetNotRegisteredWidget()
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Widget with alias "{alias}" not registered.', ['alias' => self::WIDGET_ALIAS]);

        $this->assertEquals('', $this->registry->getWidget(self::WIDGET_ALIAS, ['foo' => 'bar']));
    }
}
