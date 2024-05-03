<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use ArrayIterator;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\ProductBundle\Provider\PageTemplateProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PageTemplateProviderTest extends TestCase
{
    private PageTemplateProvider $pageTemplateProvider;

    private ThemeManager|MockObject $themeManager;

    private EntityFallbackResolver|MockObject $fallbackResolver;

    private Product $product;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->fallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $this->pageTemplateProvider = new PageTemplateProvider(
            $this->themeManager,
            $this->fallbackResolver
        );

        $this->product = new Product();
    }

    public function testGetPageTemplateWithNoFallback(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn([]);

        self::assertNull($this->pageTemplateProvider->getPageTemplate($this->product, 'not_resolved_route'));
    }

    public function testGetPageTemplateWithArrayIteratorFallback(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(new ArrayIterator(['some_template_key']));

        $this->themeManager->expects(self::never())
            ->method('getAllThemes');

        self::assertNull(
            $this->pageTemplateProvider->getPageTemplate($this->product, 'oro_product_frontend_product_view')
        );
    }

    public function testGetPageTemplateWithNoFoundInTheme(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['resolved_route' => 'some_template_key']);

        $this->themeManager->expects(self::once())
            ->method('getAllThemes')
            ->willReturn([]);

        self::assertNull($this->pageTemplateProvider->getPageTemplate($this->product, 'resolved_route'));
    }

    public function testGetPageTemplate(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['resolved_route' => 'some_template_key']);

        $theme = new Theme('some theme name');
        $pageTemplate = new PageTemplate('some label', 'some_template_key', 'resolved_route');
        $theme->addPageTemplate($pageTemplate);

        $this->themeManager->expects(self::once())
            ->method('getAllThemes')
            ->willReturn([$theme]);

        self::assertSame(
            $pageTemplate,
            $this->pageTemplateProvider->getPageTemplate($this->product, 'resolved_route')
        );
    }

    public function testGetPageTemplateFromThemeConfigurationWithNotFoundInTheme(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['some_template_key']);

        $this->themeManager->expects(self::once())
            ->method('getAllThemes')
            ->willReturn([]);

        self::assertNull(
            $this->pageTemplateProvider->getPageTemplate($this->product, 'oro_product_frontend_product_view')
        );
    }

    public function testGetPageTemplateFromThemeConfiguration(): void
    {
        $this->fallbackResolver->expects(self::once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['some_template_key']);

        $theme = new Theme('some theme name');
        $pageTemplate = new PageTemplate('some label', 'some_template_key', 'oro_product_frontend_product_view');
        $theme->addPageTemplate($pageTemplate);

        $this->themeManager->expects(self::once())
            ->method('getAllThemes')
            ->willReturn([$theme]);

        self::assertSame(
            $pageTemplate,
            $this->pageTemplateProvider->getPageTemplate($this->product, 'oro_product_frontend_product_view')
        );
    }
}
