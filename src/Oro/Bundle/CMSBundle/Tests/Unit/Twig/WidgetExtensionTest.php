<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\Twig\WidgetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WidgetExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WidgetExtension */
    private $extension;

    /** @var ContentWidgetRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(ContentWidgetRenderer::class);
        $this->extension = new WidgetExtension($this->renderer);
    }

    public function testWidgetFunction(): void
    {
        $renderedWidget = '<div>rendered widget</div>';
        $this->renderer->expects($this->once())
            ->method('render')
            ->with($name = 'widget_name')
            ->willReturn($renderedWidget);

        $this->assertEquals(
            $renderedWidget,
            self::callTwigFunction($this->extension, 'widget', [$name])
        );
    }
}
