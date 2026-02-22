<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockRenderer;
use Oro\Bundle\CMSBundle\Twig\ContentBlockExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContentBlockExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ContentBlockRenderer&MockObject $renderer;
    private ContentBlockExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->renderer = $this->createMock(ContentBlockRenderer::class);

        $container = self::getContainerBuilder()
            ->add(ContentBlockRenderer::class, $this->renderer)
            ->getContainer($this);

        $this->extension = new ContentBlockExtension($container);
    }

    public function testContentBlockFunction(): void
    {
        $alias = 'block_alias';
        $content = '<div>rendered content block</div>';

        $this->renderer->expects(self::once())
            ->method('render')
            ->with($alias)
            ->willReturn($content);

        self::assertEquals(
            $content,
            self::callTwigFunction($this->extension, 'content_block', [$alias])
        );
    }
}
