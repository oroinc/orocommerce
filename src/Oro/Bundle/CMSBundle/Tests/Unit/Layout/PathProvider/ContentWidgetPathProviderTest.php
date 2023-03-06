<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\PathProvider;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Layout\PathProvider\ContentWidgetPathProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;

class ContentWidgetPathProviderTest extends \PHPUnit\Framework\TestCase
{
    private ThemeManager|\PHPUnit\Framework\MockObject\MockObject $themeManager;

    private ContentWidgetPathProvider $provider;

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

        $this->themeManager
            ->expects(self::any())
            ->method('getThemesHierarchy')
            ->willReturnMap([
                ['base', [new Theme('base')]],
                ['black', [new Theme('base'), new Theme('black', 'base')]],
            ]);

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
}
