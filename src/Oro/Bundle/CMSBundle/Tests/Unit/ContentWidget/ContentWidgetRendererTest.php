<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetRepository;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Psr\Log\LoggerInterface;

class ContentWidgetRendererTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const SAMPLE_WIDGET = 'sample-widget';
    private const SAMPLE_WIDGET_TYPE = 'sample-widget-type';
    private const SAMPLE_RESULT = 'sample-result';
    private const SAMPLE_TEMPLATE = 'sample-template';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LayoutManager|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutManager;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ContentWidgetRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer = new ContentWidgetRenderer($this->doctrine, $this->layoutManager);

        $this->setUpLoggerMock($this->renderer);
    }

    public function testRenderWhenNoOrganization(): void
    {
        $this->assertLoggerErrorMethodCalled();

        $this->assertEquals('', $this->renderer->render(self::SAMPLE_WIDGET));
    }

    public function testRenderWhenNoContentWidget(): void
    {
        $this->assertLoggerErrorMethodCalled();

        $this->mockFindOneByName(null);

        $this->assertEquals('', $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization()));
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
            $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization())
        );
    }

    public function testRenderWhenOrganizationInToken(): void
    {
        $this->tokenAccessor
            ->method('getOrganization')
            ->willReturn($this->getOrganization());

        $this->renderer->setTokenAccessor($this->tokenAccessor);

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
                            'content_widget' => $contentWidget,
                        ],
                        'content_widget_type' => self::SAMPLE_WIDGET_TYPE,
                        'content_widget_layout' => self::SAMPLE_TEMPLATE,
                    ],
                    ['content_widget_type', 'content_widget_layout']
                )
            )
            ->willReturn($layoutContext);

        $this->layoutManager->expects($this->once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);

        $this->assertEquals(self::SAMPLE_RESULT, $this->renderer->render(self::SAMPLE_WIDGET));
    }

    public function testRenderWhenOrganizationAsArgument(): void
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
                            'content_widget' => $contentWidget,
                        ],
                        'content_widget_type' => self::SAMPLE_WIDGET_TYPE,
                        'content_widget_layout' => self::SAMPLE_TEMPLATE,
                    ],
                    ['content_widget_type', 'content_widget_layout']
                )
            )
            ->willReturn($layoutContext);

        $this->layoutManager->expects($this->once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);

        $this->assertEquals(
            self::SAMPLE_RESULT,
            $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization())
        );
    }

    /**
     * @param ContentWidget|null $contentWidget
     */
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
            ->method('findOneByName')
            ->with(self::SAMPLE_WIDGET, $this->getOrganization())
            ->willReturn($contentWidget);
    }

    /**
     * @return Organization|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getOrganization(): Organization
    {
        return $this->createMock(Organization::class);
    }

    /**
     * @return ContentWidget|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockContentWidget(): ContentWidget
    {
        $contentWidget = $this->createMock(ContentWidget::class);
        $contentWidget
            ->method('getWidgetType')
            ->willReturn(self::SAMPLE_WIDGET_TYPE);

        $contentWidget
            ->method('getLayout')
            ->willReturn(self::SAMPLE_TEMPLATE);

        return $contentWidget;
    }
}
