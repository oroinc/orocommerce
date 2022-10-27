<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Website;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceIndex;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class WebsiteUrlProvidersServiceIndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UrlItemsProviderRegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlItemsProviderRegistry;

    /**
     * @var WebsiteUrlProvidersServiceIndex
     */
    private $websiteUrlProvidersServiceIndex;

    protected function setUp(): void
    {
        $this->urlItemsProviderRegistry = $this->createMock(UrlItemsProviderRegistryInterface::class);
        $this->websiteUrlProvidersServiceIndex = new WebsiteUrlProvidersServiceIndex($this->urlItemsProviderRegistry);
    }

    public function testGetWebsiteProvidersByNames()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $providerValue = $this->createMock(UrlItemsProviderInterface::class);
        $expectedProviders = [
            'indexProviderName' => $providerValue
        ];
        $this->urlItemsProviderRegistry->expects($this->once())
            ->method('getProvidersIndexedByNames')
            ->willReturn($expectedProviders);

        $providers = $this->websiteUrlProvidersServiceIndex->getWebsiteProvidersIndexedByNames($website);
        static::assertEquals($expectedProviders, $providers);
    }
}
