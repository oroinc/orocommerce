<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\RouterSitemapUrlsProvider;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouterSitemapUrlsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ROUTES = [
        'oro_customer_customer_user_security_login',
        'oro_customer_frontend_customer_user_reset_request',
        'oro_customer_frontend_customer_user_register'
    ];

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var RouterSitemapUrlsProvider */
    private $sitemapLoginUrlsProvider;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);

        $this->sitemapLoginUrlsProvider = new RouterSitemapUrlsProvider(
            $this->urlGenerator,
            $this->canonicalUrlGenerator,
            self::ROUTES
        );
    }

    public function testGetUrlItems()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';
        $url = '/sitemaps/1/actual/test.xml';
        $absoluteUrl = 'http://test.com/sitemaps/1/actual/test.xml';
        $this->urlGenerator->expects(static::any())
            ->method('generate')
            ->willReturn($url);
        $this->canonicalUrlGenerator->expects(static::any())
            ->method('getAbsoluteUrl')
            ->with($url, $website)
            ->willReturn($absoluteUrl);

        $actual = iterator_to_array($this->sitemapLoginUrlsProvider->getUrlItems($website, $version));
        $this->assertCount(3, $actual);
        /** @var UrlItem $urlItem */
        $urlItem = reset($actual);
        $this->assertInstanceOf(UrlItem::class, $urlItem);
        $this->assertEquals($absoluteUrl, $urlItem->getLocation());
        $this->assertEmpty($urlItem->getPriority());
        $this->assertEmpty($urlItem->getChangeFrequency());
    }
}
