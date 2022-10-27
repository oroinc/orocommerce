<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Website;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersService;
use Oro\Component\Website\WebsiteInterface;

class WebsiteUrlProvidersServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var UrlItemsProviderRegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlItemsProviderRegistry;

    /**
     * @var UrlItemsProviderRegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accessDeniedProviderRegistry;

    /**
     * @var WebsiteUrlProvidersService
     */
    private $websiteUrlProvidersService;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->urlItemsProviderRegistry = $this->createMock(UrlItemsProviderRegistryInterface::class);
        $this->accessDeniedProviderRegistry = $this->createMock(UrlItemsProviderRegistryInterface::class);
        $this->websiteUrlProvidersService = new WebsiteUrlProvidersService(
            $this->configManager,
            $this->urlItemsProviderRegistry,
            $this->accessDeniedProviderRegistry
        );
    }

    public function testGetWebsiteProvidersByNames()
    {
        $website = $this->createMock(WebsiteInterface::class);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_frontend.guest_access_enabled')
            ->willReturn(true);

        $this->accessDeniedProviderRegistry->expects(static::never())
            ->method('getProvidersIndexedByNames');

        $this->urlItemsProviderRegistry->expects(static::once())
            ->method('getProvidersIndexedByNames')
            ->willReturn(['providerName' => 'provider']);

        $this->websiteUrlProvidersService->getWebsiteProvidersIndexedByNames($website);
    }

    public function testGetWebsiteProvidersByNamesWithoutAccessEnabled()
    {
        $website = $this->createMock(WebsiteInterface::class);

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_frontend.guest_access_enabled')
            ->willReturn(false);

        $this->urlItemsProviderRegistry->expects(static::never())
            ->method('getProvidersIndexedByNames');

        $this->accessDeniedProviderRegistry->expects(static::once())
            ->method('getProvidersIndexedByNames')
            ->willReturn(['providerName' => 'provider']);

        $this->websiteUrlProvidersService->getWebsiteProvidersIndexedByNames($website);
    }
}
