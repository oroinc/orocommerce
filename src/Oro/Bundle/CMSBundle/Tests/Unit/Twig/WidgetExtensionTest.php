<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\Twig\WidgetExtension;
use Oro\Bundle\CMSBundle\Widget\WidgetRegistry;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WidgetExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WidgetExtension */
    private $extension;

    /** @var WidgetRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetRegistry;

    protected function setUp()
    {
        $this->widgetRegistry = $this->createMock(WidgetRegistry::class);
        $this->extension = new WidgetExtension($this->widgetRegistry);
    }

    public function testWidgetFunction()
    {
        $renderedWidget = '<div>rendered widget</div>';
        $widgetOptions = ['foo' => 'bar'];
        $this->widgetRegistry->expects($this->once())
            ->method('getWidget')
            ->with('widget_name', $widgetOptions)
            ->willReturn($renderedWidget);

        $this->assertEquals(
            $renderedWidget,
            self::callTwigFunction($this->extension, 'widget', ['widget_name', $widgetOptions])
        );
    }
}
