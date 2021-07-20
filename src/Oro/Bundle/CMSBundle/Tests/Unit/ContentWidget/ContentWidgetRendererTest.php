<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetRepository;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Psr\Log\LoggerInterface;

class ContentWidgetRendererTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const SAMPLE_WIDGET = 'sample-widget';
    private const SAMPLE_RESULT = 'sample-result';
    private const SAMPLE_TEMPLATE = 'sample-template';
    private const SAMPLE_SETTINGS = ['param' => 'value'];

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LayoutManager|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManager;

    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ContentWidgetRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->layoutManager = $this->createMock(LayoutManager::class);

        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->contentWidgetTypeRegistry->expects($this->any())
            ->method('getWidgetType')
            ->willReturnMap(
                [
                    [ContentWidgetTypeStub::getName(), new ContentWidgetTypeStub()]
                ]
            );

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer = new ContentWidgetRenderer(
            $this->doctrine,
            $this->layoutManager,
            $this->contentWidgetTypeRegistry
        );

        $this->setUpLoggerMock($this->renderer);
    }

    public function testRenderWhenNoContentWidget(): void
    {
        $this->assertLoggerErrorMethodCalled();

        $this->mockFindOneByName(null);

        $this->assertEquals('', $this->renderer->render(self::SAMPLE_WIDGET));
    }

    public function testRenderWhenException(): void
    {
        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $this->layoutManager
            ->method('getLayoutBuilder')
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->assertEquals(
            '',
            $this->renderer->render(self::SAMPLE_WIDGET)
        );
    }

    public function testRender(): void
    {
        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $layoutContext = $this->createMock(Layout::class);
        $layoutContext->expects($this->once())
            ->method('render')
            ->willReturn(self::SAMPLE_RESULT);

        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $layoutBuilder->expects($this->once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $layoutBuilder->expects($this->once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    [
                        'data' => [
                            'settings' => self::SAMPLE_SETTINGS,
                        ],
                        'content_widget' => $contentWidget,
                    ],
                    ['content_widget']
                )
            )
            ->willReturn($layoutContext);

        $this->layoutManager->expects($this->once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);

        $this->assertEquals(
            self::SAMPLE_RESULT,
            $this->renderer->render(self::SAMPLE_WIDGET)
        );
    }

    private function mockFindOneByName(?ContentWidget $contentWidget): void
    {
        $this->doctrine
            ->method('getManagerForClass')
            ->with(ContentWidget::class)
            ->willReturn($manager = $this->createMock(EntityManager::class));

        $manager
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo = $this->createMock(ContentWidgetRepository::class));

        $repo
            ->method('findOneBy')
            ->with(['name' => self::SAMPLE_WIDGET])
            ->willReturn($contentWidget);
    }

    /**
     * @return ContentWidget|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockContentWidget(): ContentWidget
    {
        $contentWidget = $this->createMock(ContentWidget::class);
        $contentWidget
            ->method('getWidgetType')
            ->willReturn(ContentWidgetTypeStub::getName());

        $contentWidget
            ->method('getLayout')
            ->willReturn(self::SAMPLE_TEMPLATE);

        $contentWidget
            ->method('getSettings')
            ->willReturn(self::SAMPLE_SETTINGS);

        return $contentWidget;
    }
}
