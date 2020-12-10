<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryCanonicalUrlDataProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryRoutingInformationProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryCanonicalUrlDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteSystemUrlResolver;

    /**
     * @var ContentNodeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentNodeProvider;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var CategoryRoutingInformationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $routingInformationProvider;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /**
     * @var CategoryCanonicalUrlDataProvider
     */
    private $canonicalUrlDataProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteSystemUrlResolver = $this->createMock(WebsiteUrlResolver::class);
        $this->contentNodeProvider = $this->createMock(ContentNodeProvider::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->routingInformationProvider = $this->createMock(CategoryRoutingInformationProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->canonicalUrlDataProvider = new CategoryCanonicalUrlDataProvider(
            $this->configManager,
            $this->websiteSystemUrlResolver,
            $this->contentNodeProvider,
            $this->canonicalUrlGenerator,
            $this->routingInformationProvider
        );
        $this->canonicalUrlDataProvider->setFeatureChecker($this->featureChecker);
        $this->canonicalUrlDataProvider->addFeature('web_catalog_based_canonical_urls');
    }

    public function testGetUrlSystemUrlSecureUrlType()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 13]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::SECURE);

        $this->websiteSystemUrlResolver->expects($this->once())
            ->method('getWebsiteSecurePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('secureWebsitePath');

        $this->websiteSystemUrlResolver->expects($this->never())
            ->method('getWebsitePath');

        $this->assertSame('secureWebsitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlSystemUrl()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 13]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects($this->once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn(null);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::INSECURE);

        $this->websiteSystemUrlResolver->expects($this->once())
            ->method('getWebsitePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('websitePath');

        $this->websiteSystemUrlResolver->expects($this->never())
            ->method('getWebsiteSecurePath');

        $this->assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlSystemUrlCalledWhenThereIsNoVariantUrls()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 13]);
        $variant = new ContentVariant();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects($this->once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn($variant);
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getDirectUrl')
            ->willReturn(null);

        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::INSECURE);

        $this->websiteSystemUrlResolver->expects($this->once())
            ->method('getWebsitePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('websitePath');

        $this->websiteSystemUrlResolver->expects($this->never())
            ->method('getWebsiteSecurePath');

        $this->assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlFromWebCatalog()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 5]);
        $variant = new ContentVariant();

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects($this->once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn($variant);
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getDirectUrl')
            ->willReturn('websitePath');

        $this->assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlCategoryEntityUrl()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 5]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getUrl')
            ->with($category)
            ->willReturn('websitePath');

        $this->assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, false));
    }
}
