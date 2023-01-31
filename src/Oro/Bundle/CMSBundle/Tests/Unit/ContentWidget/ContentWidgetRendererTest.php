<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetProvider;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\FrontendEmulator;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryInterface;
use Psr\Log\LoggerInterface;

class ContentWidgetRendererTest extends \PHPUnit\Framework\TestCase
{
    private const ERROR_TEMPLATE = <<<HTML
<div class="alert alert-error alert--compact" role="alert">
    <span class="fa-exclamation alert-icon" aria-hidden="true"></span>
    Rendering of the content widget "sample-widget" failed: %s
</div>
HTML;

    private const SAMPLE_WIDGET = 'sample-widget';
    private const SAMPLE_RESULT = 'sample-result';
    private const SAMPLE_SETTINGS = ['param' => 'value'];

    private ContentWidgetProvider|\PHPUnit\Framework\MockObject\MockObject $contentWidgetProvider;

    private LayoutManager|\PHPUnit\Framework\MockObject\MockObject $layoutManager;

    private FrontendHelper|\PHPUnit\Framework\MockObject\MockObject $frontendHelper;

    private FrontendEmulator|\PHPUnit\Framework\MockObject\MockObject $frontendEmulator;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private LayoutBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $layoutBuilder;

    protected function setUp(): void
    {
        $this->contentWidgetProvider = $this->createMock(ContentWidgetProvider::class);
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->frontendEmulator = $this->createMock(FrontendEmulator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $layoutFactory = $this->createMock(LayoutFactoryInterface::class);
        $this->layoutManager
            ->expects(self::any())
            ->method('getLayoutFactory')
            ->willReturn($layoutFactory);

        $this->layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $layoutFactory
            ->expects(self::any())
            ->method('createLayoutBuilder')
            ->willReturn($this->layoutBuilder);
    }

    private function getRenderer(bool $debug): ContentWidgetRenderer
    {
        $contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $contentWidgetTypeRegistry
            ->expects(self::any())
            ->method('getWidgetType')
            ->willReturnMap([
                [ContentWidgetTypeStub::getName(), new ContentWidgetTypeStub()],
            ]);

        $renderer = new ContentWidgetRenderer(
            $this->contentWidgetProvider,
            $contentWidgetTypeRegistry,
            $this->layoutManager,
            $this->frontendHelper,
            $this->frontendEmulator,
            $debug
        );

        $renderer->setLogger($this->logger);

        return $renderer;
    }

    private function getContentWidget(array|\Throwable $data): ContentWidget
    {
        $contentWidget = $this->createMock(ContentWidget::class);
        $contentWidget->expects(self::any())
            ->method('getName')
            ->willReturn(self::SAMPLE_WIDGET);
        $contentWidget->expects(self::once())
            ->method('getWidgetType')
            ->willReturn(ContentWidgetTypeStub::getName());
        if ($data instanceof \Throwable) {
            $contentWidget->expects(self::once())
                ->method('getSettings')
                ->willThrowException($data);
        } else {
            $contentWidget->expects(self::once())
                ->method('getSettings')
                ->willReturn($data);
        }

        return $contentWidget;
    }

    public function debugDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenNoContentWidget(bool $debug): void
    {
        $exception = new \RuntimeException('The context widget does not exist.');

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->frontendEmulator->expects(self::never())
            ->method(self::anything());

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        self::assertSame($expectedResult, $this->getRenderer($debug)->render(self::SAMPLE_WIDGET));
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenExceptionDuringRendering(bool $debug): void
    {
        $exception = new \Exception('some error');
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->frontendEmulator->expects(self::never())
            ->method(self::anything());

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $this->layoutBuilder
            ->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $this->layoutBuilder
            ->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);

        $layout
            ->expects(self::once())
            ->method('render')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        self::assertSame($expectedResult, $this->getRenderer($debug)->render(self::SAMPLE_WIDGET));
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenExceptionDuringGettingContextWidgetData(bool $debug): void
    {
        $exception = new \Error('some error');
        $contentWidget = $this->getContentWidget($exception);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->frontendEmulator->expects(self::never())
            ->method(self::anything());

        $this->contentWidgetProvider
            ->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $this->layoutBuilder
            ->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $this->layoutBuilder
            ->expects(self::never())
            ->method('getLayout');

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        self::assertSame($expectedResult, $this->getRenderer($debug)->render(self::SAMPLE_WIDGET));
    }

    public function testRender(): void
    {
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->frontendEmulator->expects(self::never())
            ->method(self::anything());

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $this->layoutBuilder
            ->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $this->layoutBuilder
            ->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);
        $layout
            ->expects(self::once())
            ->method('render')
            ->willReturn(self::SAMPLE_RESULT);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals(self::SAMPLE_RESULT, $this->getRenderer(false)->render(self::SAMPLE_WIDGET));
    }

    public function testRenderInBackoffice(): void
    {
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->frontendEmulator->expects(self::once())
            ->method('startFrontendRequestEmulation');
        $this->frontendEmulator->expects(self::once())
            ->method('stopFrontendRequestEmulation');

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $this->layoutBuilder->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $this->layoutBuilder
            ->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);
        $layout
            ->expects(self::once())
            ->method('render')
            ->willReturn(self::SAMPLE_RESULT);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals(self::SAMPLE_RESULT, $this->getRenderer(false)->render(self::SAMPLE_WIDGET));
    }

    public function testRenderInBackofficeWhenExceptionDuringRendering(): void
    {
        $exception = new \Exception('some error');
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->frontendEmulator->expects(self::once())
            ->method('startFrontendRequestEmulation');
        $this->frontendEmulator->expects(self::once())
            ->method('stopFrontendRequestEmulation');

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $this->layoutBuilder
            ->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $this->layoutBuilder
            ->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);
        $layout
            ->expects(self::once())
            ->method('render')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = sprintf(self::ERROR_TEMPLATE, $exception->getMessage());
        self::assertSame($expectedResult, $this->getRenderer(true)->render(self::SAMPLE_WIDGET));
    }
}
