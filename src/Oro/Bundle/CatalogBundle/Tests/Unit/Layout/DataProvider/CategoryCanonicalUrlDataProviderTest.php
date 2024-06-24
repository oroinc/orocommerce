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
use Oro\Component\Testing\ReflectionUtil;

class CategoryCanonicalUrlDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteSystemUrlResolver;

    /** @var ContentNodeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentNodeProvider;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var CategoryRoutingInformationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $routingInformationProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CategoryCanonicalUrlDataProvider */
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

    private function getCategory(int $id): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    public function testGetUrlSystemUrlSecureUrlType()
    {
        $category = $this->getCategory(13);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::SECURE);

        $this->websiteSystemUrlResolver->expects(self::once())
            ->method('getWebsiteSecurePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('secureWebsitePath');

        $this->websiteSystemUrlResolver->expects(self::never())
            ->method('getWebsitePath');

        self::assertSame('secureWebsitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlSystemUrl()
    {
        $category = $this->getCategory(13);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn(null);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::INSECURE);

        $this->websiteSystemUrlResolver->expects(self::once())
            ->method('getWebsitePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('websitePath');

        $this->websiteSystemUrlResolver->expects(self::never())
            ->method('getWebsiteSecurePath');

        self::assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlSystemUrlCalledWhenThereIsNoVariantUrls()
    {
        $category = $this->getCategory(13);
        $variant = new ContentVariant();

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn($variant);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getDirectUrl')
            ->willReturn(null);

        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::INSECURE);

        $this->websiteSystemUrlResolver->expects(self::once())
            ->method('getWebsitePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => true
            ])
            ->willReturn('websitePath');

        $this->websiteSystemUrlResolver->expects(self::never())
            ->method('getWebsiteSecurePath');

        self::assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlFromWebCatalog()
    {
        $category = $this->getCategory(5);
        $variant = new ContentVariant();

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->contentNodeProvider->expects(self::once())
            ->method('getFirstMatchingVariantForEntity')
            ->with($category)
            ->willReturn($variant);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getDirectUrl')
            ->willReturn('websitePath');

        self::assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, true));
    }

    public function testGetUrlCategoryEntityUrl()
    {
        $category = $this->getCategory(5);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $routeData = new RouteData(
            'oro_product_frontend_product_index',
            ['categoryId' => 13, 'includeSubcategories' => false]
        );
        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($category)
            ->willReturn($routeData);
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('getUrl')
            ->with($category)
            ->willReturn('websitePath');

        self::assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category, false));
    }
}
