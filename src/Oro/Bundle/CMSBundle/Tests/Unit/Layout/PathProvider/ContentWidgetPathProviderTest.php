<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\PathProvider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Layout\PathProvider\ContentWidgetPathProvider;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;

class ContentWidgetPathProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var ContentWidgetPathProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->provider = new ContentWidgetPathProvider($this->themeManager);
    }

    /**
     * @dataProvider pathsDataProvider
     */
    public function testGetPaths(?string $theme, ?string $contentWidgetType, array $expectedResults): void
    {
        $contentWidget = new ContentWidget();
        if ($contentWidgetType) {
            $contentWidget->setWidgetType($contentWidgetType);
        }

        $context = new LayoutContext();
        $context->set('theme', $theme);
        $context->set('content_widget', $contentWidget);

        $this->setUpThemeManager(
            [
                'black' => $this->getThemeMock('black', 'base', new PageTemplate('test', 'page', 'test_route')),
                'base'  => $this->getThemeMock('base')
            ]
        );
        $this->provider->setContext($context);
        $this->assertSame($expectedResults, $this->provider->getPaths([]));
    }

    public function pathsDataProvider(): array
    {
        return [
            [

                'theme' => null,
                'content_widget_type ' => null,
                'expectedResults' => [],
            ],
            [

                'theme' => 'base',
                'content_widget_type' => null,
                'expectedResults' => [],
            ],
            [

                'theme' => 'base',
                'content_widget_type' => 'test_widget_type',
                'expectedResults' => [
                    'base/content_widget',
                    'base/content_widget/test_widget_type',
                ],
            ],
            [

                'theme' => 'black',
                'content_widget_type' => 'test_widget_type',
                'expectedResults' => [
                    'base/content_widget',
                    'base/content_widget/test_widget_type',
                    'black/content_widget',
                    'black/content_widget/test_widget_type',
                ],
            ],
        ];
    }

    private function setUpThemeManager(array $themes): void
    {
        $map = [];
        foreach ($themes as $themeName => $theme) {
            $map[] = [$themeName, $theme];
        }

        $this->themeManager->expects($this->any())
            ->method('getTheme')
            ->willReturnMap($map);
    }

    /**
     * @param null|string $directory
     * @param null|string $parent
     * @param PageTemplate $pageTemplate
     * @return Theme|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getThemeMock(
        ?string $directory = null,
        ?string $parent = null,
        PageTemplate $pageTemplate = null
    ): Theme {
        $theme = $this->createMock(Theme::class);
        $theme->expects($this->any())
            ->method('getParentTheme')
            ->willReturn($parent);
        $theme->expects($this->any())
            ->method('getDirectory')
            ->willReturn($directory);
        $theme->expects($this->any())
            ->method('getPageTemplate')
            ->willReturn($pageTemplate);

        return $theme;
    }
}
