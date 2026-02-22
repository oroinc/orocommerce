<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\WysiwygWidgetIconRenderer;
use Oro\Bundle\CMSBundle\Twig\WidgetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WidgetExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private WidgetExtension $extension;
    private ContentWidgetRenderer|MockObject $contentWidgetRenderer;
    private WysiwygWidgetIconRenderer|MockObject $wysiwygWidgetIconRenderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentWidgetRenderer = $this->createMock(ContentWidgetRenderer::class);
        $this->wysiwygWidgetIconRenderer = $this->createMock(WysiwygWidgetIconRenderer::class);

        $container = self::getContainerBuilder()
            ->add(ContentWidgetRenderer::class, $this->contentWidgetRenderer)
            ->add(WysiwygWidgetIconRenderer::class, $this->wysiwygWidgetIconRenderer)
            ->getContainer($this);

        $this->extension = new WidgetExtension($container);
    }

    public function testWidgetFunction(): void
    {
        $renderedWidget = '<div>rendered widget</div>';
        $this->contentWidgetRenderer->expects($this->once())
            ->method('render')
            ->with($name = 'widget_name')
            ->willReturn($renderedWidget);

        $this->assertEquals(
            $renderedWidget,
            self::callTwigFunction($this->extension, 'widget', [$name])
        );
    }

    public function testWidgetIconFunction(): void
    {
        $renderedWidget = '<div>rendered widget</div>';
        $this->wysiwygWidgetIconRenderer->expects($this->once())
            ->method('render')
            ->with($name = 'widget_name', $options = ['id' => 'widget-id'])
            ->willReturn($renderedWidget);

        $this->assertEquals(
            $renderedWidget,
            self::callTwigFunction($this->extension, 'widget_icon', [$name, $options])
        );
    }
}
