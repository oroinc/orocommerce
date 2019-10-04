<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Repository\ContentWidgetRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

class ContentWidgetRendererTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const SAMPLE_WIDGET = 'sample-widget';
    private const SAMPLE_WIDGET_TYPE = 'sample-widget-type';
    private const SAMPLE_RESULT = 'sample-result';
    private const WIDGET_DATA = ['sample-data'];
    private const SAMPLE_TEMPLATE = 'sample-template';

    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    /** @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ContentWidgetRenderer */
    private $renderer;

    protected function setUp()
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->doctrine = $this->createMock(RegistryInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->renderer = new ContentWidgetRenderer(
            $this->contentWidgetTypeRegistry,
            $this->doctrine,
            $this->twig
        );

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

    public function testRenderWhenInvalidWidgetType(): void
    {
        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $this->mockWidgetData($contentWidget);

        $this->twig
            ->method('render')
            ->with(self::SAMPLE_TEMPLATE, self::WIDGET_DATA)
            ->willThrowException(new LoaderError(''));

        $this->contentWidgetTypeRegistry
            ->method('getWidgetType')
            ->with(self::SAMPLE_WIDGET_TYPE)
            ->willReturn(null);

        $this->assertLoggerErrorMethodCalled();

        $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization());
    }

    /**
     * @return ContentWidget|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockContentWidget()
    {
        $contentWidget = $this->createMock(ContentWidget::class);
        $contentWidget
            ->method('getWidgetType')
            ->willReturn(self::SAMPLE_WIDGET_TYPE);

        $contentWidget
            ->method('getTemplate')
            ->willReturn(self::SAMPLE_TEMPLATE);

        return $contentWidget;
    }

    public function testRenderWhenException(): void
    {
        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $this->mockWidgetData($contentWidget);

        $this->twig
            ->method('render')
            ->with(self::SAMPLE_TEMPLATE, self::WIDGET_DATA)
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $this->assertEquals(
            '',
            $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization())
        );
    }

    private function mockWidgetData(ContentWidget $contentWidget): void
    {
        $this->contentWidgetTypeRegistry
            ->method('getWidgetType')
            ->with(self::SAMPLE_WIDGET_TYPE)
            ->willReturn($widgetType = $this->createMock(ContentWidgetTypeInterface::class));

        $widgetType
            ->method('getWidgetData')
            ->with($contentWidget)
            ->willReturn(self::WIDGET_DATA);
    }

    public function testRenderWhenOrganizationInToken(): void
    {
        $this->tokenAccessor
            ->method('getOrganization')
            ->willReturn($this->getOrganization());

        $this->renderer->setTokenAccessor($this->tokenAccessor);

        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $this->mockWidgetData($contentWidget);

        $this->twig
            ->method('render')
            ->with(self::SAMPLE_TEMPLATE, self::WIDGET_DATA)
            ->willReturn(self::SAMPLE_RESULT);

        $this->assertEquals(self::SAMPLE_RESULT, $this->renderer->render(self::SAMPLE_WIDGET));
    }

    public function testRenderWhenOrganizationAsArgument(): void
    {
        $contentWidget = $this->mockContentWidget();
        $this->mockFindOneByName($contentWidget);

        $this->mockWidgetData($contentWidget);

        $this->twig
            ->method('render')
            ->with(self::SAMPLE_TEMPLATE, self::WIDGET_DATA)
            ->willReturn(self::SAMPLE_RESULT);

        $this->assertEquals(
            self::SAMPLE_RESULT,
            $this->renderer->render(self::SAMPLE_WIDGET, $this->getOrganization())
        );
    }
}
