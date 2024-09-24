<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentBlock;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockRenderer;
use Oro\Bundle\CMSBundle\ContentBlock\Model\ContentBlockView;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentBlockDataProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ContentBlockRendererTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ContentBlockDataProvider|MockObject $contentBlockDataProvider;

    private Environment|MockObject $twig;

    private LoggerInterface|MockObject $logger;

    private ContentBlockRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentBlockDataProvider = $this->createMock(ContentBlockDataProvider::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer = new ContentBlockRenderer($this->contentBlockDataProvider, $this->twig);

        $this->setUpLoggerMock($this->renderer);
    }

    public function testRenderWhenNoContentBlock(): void
    {
        $this->assertLoggerErrorMethodCalled();

        $this->contentBlockDataProvider->expects($this->once())
            ->method('hasContentBlockView')
            ->with('sample-block')
            ->willReturn(false)
        ;
        $this->assertEquals('', $this->renderer->render('sample-block'));
    }

    public function testRenderWhenException(): void
    {
        $blockView = new ContentBlockView('block', new ArrayCollection(), true, 'content', 'style');

        $this->contentBlockDataProvider->expects($this->once())
            ->method('hasContentBlockView')
            ->with('sample-block')
            ->willReturn(true)
        ;
        $this->contentBlockDataProvider->expects($this->once())
            ->method('getContentBlockView')
            ->with('sample-block')
            ->willReturn($blockView)
        ;
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                $this->isType('string'),
                ['contentBlock' => $blockView]
            )
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();
        $this->assertEquals('', $this->renderer->render('sample-block'));
    }

    public function testRenderWhenRecursiveRender(): void
    {
        $blockView = new ContentBlockView('block', new ArrayCollection(), true, 'content', 'style');

        $this->contentBlockDataProvider->expects($this->once())
            ->method('hasContentBlockView')
            ->with('sample-block')
            ->willReturn(true)
        ;

        $this->contentBlockDataProvider->expects($this->once())
            ->method('getContentBlockView')
            ->with('sample-block')
            ->willReturn($blockView)
        ;

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@OroCMS/ContentBlock/widget.html.twig', ['contentBlock' => $blockView])
            ->willReturnCallback(
                function () {
                    return $this->renderer->render('sample-block');
                }
            );

        $this->assertLoggerErrorMethodCalled();
        $this->assertEquals('', $this->renderer->render('sample-block'));
    }

    public function testRenderWhenContentBlockIsInvisible(): void
    {
        $this->contentBlockDataProvider->expects($this->once())
            ->method('hasContentBlockView')
            ->with('sample-block')
            ->willReturn(true)
        ;
        $this->contentBlockDataProvider->expects($this->once())
            ->method('getContentBlockView')
            ->with('sample-block')
            ->willReturn(null)
        ;
        $this->twig->expects($this->never())
            ->method('render');

        $this->assertLoggerNotCalled();
        $this->assertEquals('', $this->renderer->render('sample-block'));
    }

    public function testRender(): void
    {
        $blockView = new ContentBlockView('block', new ArrayCollection(), true, 'content', 'style');

        $this->contentBlockDataProvider->expects($this->once())
            ->method('hasContentBlockView')
            ->with('sample-block')
            ->willReturn(true)
        ;
        $this->contentBlockDataProvider->expects($this->once())
            ->method('getContentBlockView')
            ->with('sample-block')
            ->willReturn($blockView)
        ;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('@OroCMS/ContentBlock/widget.html.twig', ['contentBlock' => $blockView])
            ->willReturn('sample-result')
        ;
        $this->assertLoggerNotCalled();
        $this->assertEquals('sample-result', $this->renderer->render('sample-block'));
    }
}
