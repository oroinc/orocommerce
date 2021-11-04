<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetProvider;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
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

    /** @var ContentWidgetProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetProvider;

    /** @var LayoutManager|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->contentWidgetProvider = $this->createMock(ContentWidgetProvider::class);
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function getRenderer(bool $debug): ContentWidgetRenderer
    {
        $contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $contentWidgetTypeRegistry->expects(self::any())
            ->method('getWidgetType')
            ->willReturnMap([
                [ContentWidgetTypeStub::getName(), new ContentWidgetTypeStub()]
            ]);

        return new ContentWidgetRenderer(
            $this->contentWidgetProvider,
            $contentWidgetTypeRegistry,
            $this->layoutManager,
            $this->logger,
            $debug
        );
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
            [true]
        ];
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenNoContentWidget(bool $debug): void
    {
        $exception = new \RuntimeException('The context widget does not exist.');

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        $result = $this->getRenderer($debug)->render(self::SAMPLE_WIDGET);
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenExceptionDuringRendering(bool $debug): void
    {
        $exception = new \Exception('some error');
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $layoutBuilder->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);
        $layout->expects(self::once())
            ->method('render')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        $result = $this->getRenderer($debug)->render(self::SAMPLE_WIDGET);
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testRenderWhenExceptionDuringGettingContextWidgetData(bool $debug): void
    {
        $exception = new \Error('some error');
        $contentWidget = $this->getContentWidget($exception);

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $layoutBuilder->expects(self::never())
            ->method('getLayout');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Error occurred while rendering content widget "sample-widget".');

        $expectedResult = $debug ? sprintf(self::ERROR_TEMPLATE, $exception->getMessage()) : '';
        $result = $this->getRenderer($debug)->render(self::SAMPLE_WIDGET);
        self::assertSame($expectedResult, $result);
    }

    public function testRender(): void
    {
        $contentWidget = $this->getContentWidget(self::SAMPLE_SETTINGS);

        $this->contentWidgetProvider->expects(self::once())
            ->method('getContentWidget')
            ->with(self::SAMPLE_WIDGET)
            ->willReturn($contentWidget);

        $layout = $this->createMock(Layout::class);
        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('content_widget_root', null, 'content_widget_root');
        $layoutBuilder->expects(self::once())
            ->method('getLayout')
            ->with(
                new LayoutContext(
                    ['data' => ['settings' => self::SAMPLE_SETTINGS], 'content_widget' => $contentWidget],
                    ['content_widget']
                )
            )
            ->willReturn($layout);
        $layout->expects(self::once())
            ->method('render')
            ->willReturn(self::SAMPLE_RESULT);

        $this->logger->expects(self::never())
            ->method('error');

        self::assertEquals(self::SAMPLE_RESULT, $this->getRenderer(false)->render(self::SAMPLE_WIDGET));
    }
}
