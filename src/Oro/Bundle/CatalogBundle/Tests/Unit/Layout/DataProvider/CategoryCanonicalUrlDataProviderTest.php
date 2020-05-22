<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryCanonicalUrlDataProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryCanonicalUrlDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteSystemUrlResolver;

    /** @var CategoryCanonicalUrlDataProvider */
    private $canonicalUrlDataProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteSystemUrlResolver = $this->createMock(WebsiteUrlResolver::class);

        $this->canonicalUrlDataProvider = new CategoryCanonicalUrlDataProvider(
            $this->configManager,
            $this->websiteSystemUrlResolver
        );
    }

    public function testGetUrlSecureUrlType()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 13]);

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

    public function testGetUrl()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 13]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type')
            ->willReturn(Configuration::INSECURE);

        $this->websiteSystemUrlResolver->expects($this->once())
            ->method('getWebsitePath')
            ->with('oro_product_frontend_product_index', [
                'categoryId' => 13,
                'includeSubcategories' => false
            ])
            ->willReturn('websitePath');

        $this->websiteSystemUrlResolver->expects($this->never())
            ->method('getWebsiteSecurePath');

        $this->assertSame('websitePath', $this->canonicalUrlDataProvider->getUrl($category));
    }
}
