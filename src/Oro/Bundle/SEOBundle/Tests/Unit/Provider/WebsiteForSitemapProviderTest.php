<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Provider;

use Oro\Bundle\SEOBundle\Provider\WebsiteForSitemapProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

class WebsiteForSitemapProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAvailableWebsites()
    {
        $expectedWebsites = [new Website(), new Website()];
        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $websiteProvider->expects(self::once())
            ->method('getWebsites')
            ->willReturn($expectedWebsites);

        $provider = new WebsiteForSitemapProvider($websiteProvider);

        self::assertEquals($expectedWebsites, $provider->getAvailableWebsites());
    }
}
