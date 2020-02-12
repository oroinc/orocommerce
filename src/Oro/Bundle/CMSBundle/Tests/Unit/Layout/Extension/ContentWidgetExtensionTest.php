<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Layout\Extension\ContentWidgetExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\ChainPathProvider;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ResourceProviderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\Driver\DriverInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoader;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubContextAwarePathProvider;

class ContentWidgetExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private const THEME = 'oro-default';

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $phpDriver;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $yamlDriver;

    /** @var DependencyInitializer|\PHPUnit\Framework\MockObject\MockObject */
    private $dependencyInitializer;

    /** @var ChainPathProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $pathProvider;

    /** @var ResourceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceProvider;

    /** @var ContentWidgetExtension */
    private $extension;

    protected function setUp(): void
    {
        $driverMockBuilder = $this->getMockBuilder(DriverInterface::class)
            ->setMethods(['load', 'getUpdateFilenamePattern']);

        $this->yamlDriver = $driverMockBuilder->getMock();
        $this->phpDriver = $driverMockBuilder->getMock();

        $loader = new LayoutUpdateLoader();
        $loader->addDriver('yml', $this->yamlDriver);
        $loader->addDriver('php', $this->phpDriver);

        $this->dependencyInitializer = $this->createMock(DependencyInitializer::class);
        $this->pathProvider = $this->createMock(StubContextAwarePathProvider::class);
        $this->resourceProvider = $this->createMock(ResourceProviderInterface::class);

        $this->extension = new ContentWidgetExtension(
            $loader,
            $this->dependencyInitializer,
            $this->pathProvider,
            $this->resourceProvider
        );
    }

    public function testHasLayoutUpdates(): void
    {
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(
                [
                    'oro-default/resource1.yml',
                    'oro-default/page/resource2.yml',
                    'oro-default/page/resource3.php',
                ]
            );

        $this->assertFalse(
            $this->extension->hasLayoutUpdates($this->getLayoutItem('content_widget_root', self::THEME))
        );
    }

    public function testHasLayoutUpdatesWithValidContext(): void
    {
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(['oro-default/resource.yml']);

        $updateMock = $this->createMock(LayoutUpdateInterface::class);

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->with('oro-default/resource.yml')
            ->willReturn($updateMock);

        $this->assertTrue($this->extension->hasLayoutUpdates($this->getLayoutItem('content_widget_root', self::THEME)));
    }

    public function testGetLayoutUpdates(): void
    {
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(
                [
                    'oro-default/resource1.yml',
                    'oro-default/page/resource2.yml',
                    'oro-default/page/resource3.php',
                ]
            );

        $this->assertEquals(
            [],
            $this->extension->getLayoutUpdates($this->getLayoutItem('content_widget_root', self::THEME))
        );
    }

    public function testThemeUpdatesFoundWithOneSkipped(): void
    {
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(
                [
                    'oro-default/resource1.yml',
                    'oro-default/page/resource3.php'
                ]
            );

        $updateMock = $this->createMock(LayoutUpdateInterface::class);
        $update2Mock = $this->createMock(LayoutUpdateInterface::class);

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->with('oro-default/resource1.yml')
            ->willReturn($updateMock);
        $this->phpDriver->expects($this->once())
            ->method('load')
            ->with('oro-default/page/resource3.php')
            ->willReturn($update2Mock);

        $result = $this->extension->getLayoutUpdates($this->getLayoutItem('content_widget_root', self::THEME));

        $this->assertContains($updateMock, $result);
        $this->assertContains($update2Mock, $result);
    }

    public function testShouldPassDependenciesToUpdateInstance(): void
    {
        $updateMock = $this->createMock(LayoutUpdateInterface::class);

        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(
                [
                    'oro-default/resource1.yml'
                ]
            );

        $this->yamlDriver->expects($this->once())
            ->method('load')
            ->willReturn($updateMock);

        $this->dependencyInitializer->expects($this->once())
            ->method('initialize')
            ->with($updateMock);

        $this->extension->getLayoutUpdates($this->getLayoutItem('content_widget_root', self::THEME));
    }

    public function testShouldPassContextInContextAwareProvider(): void
    {
        $this->pathProvider->expects($this->once())
            ->method('getPaths')
            ->willReturn([self::THEME]);

        $this->resourceProvider->expects($this->any())
            ->method('findApplicableResources')
            ->with([self::THEME])
            ->willReturn(
                [
                    'oro-default/resource1.yml',
                    'oro-default/page/resource2.yml',
                    'oro-default/page/resource3.php'
                ]
            );

        $this->pathProvider->expects($this->once())
            ->method('setContext');

        $this->extension->getLayoutUpdates($this->getLayoutItem('root', self::THEME));
    }

    /**
     * @param string $id
     * @param null|string $theme
     *
     * @return LayoutItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLayoutItem(string $id, ?string $theme = null): LayoutItemInterface
    {
        $contentWidget = null;
        if ($theme) {
            $contentWidget = new ContentWidget();
            $contentWidget->setWidgetType($theme);
        }

        $context = new LayoutContext();
        $context->set('content_widget', $contentWidget);

        $layoutItem = new LayoutItem(new RawLayoutBuilder(), $context);
        $layoutItem->initialize($id);

        return $layoutItem;
    }
}
