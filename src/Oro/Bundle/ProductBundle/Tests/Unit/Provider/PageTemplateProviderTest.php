<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\ProductBundle\Provider\PageTemplateProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class PageTemplateProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PageTemplateProvider */
    private $pageTemplateProvider;

    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $fallbackResolver;

    /** @var Product */
    private $product;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->fallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $this->pageTemplateProvider = new PageTemplateProvider($this->themeManager, $this->fallbackResolver);

        $this->product = new Product();
    }

    public function testGetPageTemplateWithNoFallback()
    {
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn([]);

        $this->assertNull($this->pageTemplateProvider->getPageTemplate($this->product, 'not_resolved_route'));
    }

    public function testGetPageTemplateWithNoFoundInTheme()
    {
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['resolved_route' => 'some_template_key']);

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->willReturn([]);

        $this->assertNull($this->pageTemplateProvider->getPageTemplate($this->product, 'resolved_route'));
    }

    public function testGetPageTemplate()
    {
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->with($this->product, 'pageTemplate')
            ->willReturn(['resolved_route' => 'some_template_key']);

        $theme = new Theme('some theme name');
        $pageTemplate = new PageTemplate('some label', 'some_template_key', 'resolved_route');
        $theme->addPageTemplate($pageTemplate);

        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->willReturn([$theme]);

        $this->assertSame(
            $pageTemplate,
            $this->pageTemplateProvider->getPageTemplate($this->product, 'resolved_route')
        );
    }
}
