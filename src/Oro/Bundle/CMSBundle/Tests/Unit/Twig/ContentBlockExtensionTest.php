<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockRenderer;
use Oro\Bundle\CMSBundle\Twig\ContentBlockExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ContentBlockExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ContentBlockRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $renderer;

    /** @var ContentBlockExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(ContentBlockRenderer::class);

        $container = self::getContainerBuilder()
            ->add('oro_cms.content_block.renderer', $this->renderer)
            ->getContainer($this);

        $this->extension = new ContentBlockExtension($container);
    }

    public function testContentBlockFunction(): void
    {
        $alias = 'block_alias';
        $content = '<div>rendered content block</div>';

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($alias)
            ->willReturn($content);

        $this->assertEquals(
            $content,
            self::callTwigFunction($this->extension, 'content_block', [$alias])
        );
    }
}
