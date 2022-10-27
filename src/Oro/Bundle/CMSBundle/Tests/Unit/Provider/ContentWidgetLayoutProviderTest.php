<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ContentWidgetLayoutProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ContentWidgetLayoutProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->provider = new ContentWidgetLayoutProvider($this->themeManager, $this->cache);
    }

    public function testGetWidgetLayouts(): void
    {
        $theme1 = new Theme('blank');
        $theme1->setConfig(
            [
                'widgets' => [
                    'layouts' => [
                        'some_widget_type' => ['template1' => 'Template 1'],
                        ContentWidgetTypeStub::getName() => ['template2' => 'Template 2'],
                    ]
                ]
            ]
        );

        $theme2 = new Theme('default');
        $theme2->setConfig(
            [
                'widgets' => [
                    'layouts' => [
                        ContentWidgetTypeStub::getName() => ['template3' => 'Template 3'],
                    ]
                ]
            ]
        );

        $theme3 = new Theme('custom');
        $theme3->setConfig([]);

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->willReturn([$theme1, $theme2, $theme3]);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            ['template2' => 'Template 2', 'template3' => 'Template 3'],
            $this->provider->getWidgetLayouts(ContentWidgetTypeStub::getName())
        );
    }

    public function testGetWidgetLayoutsFromCache(): void
    {
        $this->themeManager->expects($this->never())
            ->method('getAllThemes');

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    'layouts' => [
                        'some_widget_type' => ['template1' => 'Template 1'],
                        ContentWidgetTypeStub::getName() => ['template2' => 'Template 2'],
                    ]
                ]
            );

        $this->assertEquals(
            ['template2' => 'Template 2'],
            $this->provider->getWidgetLayouts(ContentWidgetTypeStub::getName())
        );
    }

    public function testGetWidgetLayoutLabel(): void
    {
        $theme = new Theme('blank');
        $theme->setConfig(
            [
                'widgets' => [
                    'layouts' => [
                        'some_widget_type' => ['template1' => 'Template 1'],
                        ContentWidgetTypeStub::getName() => ['template2' => 'Template 2'],
                    ]
                ]
            ]
        );

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->willReturn([$theme]);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            'Template 2',
            $this->provider->getWidgetLayoutLabel(ContentWidgetTypeStub::getName(), 'template2')
        );
    }

    public function testGetWidgetLayoutLabelFromCache(): void
    {
        $this->themeManager->expects($this->never())
            ->method('getAllThemes');

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    'layouts' => [
                        'some_widget_type' => ['template1' => 'Template 1'],
                        ContentWidgetTypeStub::getName() => ['template2' => 'Template 2'],
                    ]
                ]
            );

        $this->assertEquals(
            'Template 2',
            $this->provider->getWidgetLayoutLabel(ContentWidgetTypeStub::getName(), 'template2')
        );
    }
}
