<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CanonicalUrlGeneratorTest extends AbstractCanonicalUrlGeneratorTestCase
{
    #[\Override]
    protected function createGenerator(): CanonicalUrlGenerator
    {
        return new CanonicalUrlGenerator(
            $this->configManager,
            $this->cache,
            $this->requestStack,
            $this->routingInformationProvider,
            $this->websiteUrlResolver,
            $this->localizationProvider
        );
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    public function testIsDirectUrlEnabled(): void
    {
        $website = $this->getWebsite(1);

        $cacheKey = 'oro_redirect.canonical_url_type.1';
        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(Configuration::DIRECT_URL);

        $this->assertTrue($this->canonicalUrlGenerator->isDirectUrlEnabled($website));
    }

    public function testIsDirectUrlDisabled(): void
    {
        $website = $this->getWebsite(1);

        $cacheKey = 'oro_redirect.canonical_url_type.1';
        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(Configuration::SYSTEM_URL);

        $this->assertFalse($this->canonicalUrlGenerator->isDirectUrlEnabled($website));
    }

    public function testGetDirectUrlForInsecureCanonical(): void
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'http://example.com/';
        $expectedUrl = 'http://example.com/index_dev.php/canonical';
        $expectedBaseUrl = '/index_dev.php';
        $urlSecurityType = Configuration::INSECURE;
        $website = $this->getWebsite(777);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->with($website)
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);

        $this->assertUrlTypeCalls($urlSecurityType, $website);

        $entity = $this->getSluggableEntity($slug);
        $this->assertRequestCalls($entity, $expectedBaseUrl);
        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($entity, null, $website));
    }

    public function testGetDirectUrlForInsecureCanonicalWithInstallationInSubfolder(): void
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'http://example.com/subfolder';
        $expectedUrl = 'http://example.com/subfolder/index_dev.php/canonical';
        $expectedBaseUrl = '/subfolder/index_dev.php';
        $urlSecurityType = Configuration::INSECURE;
        $website = $this->getWebsite(777);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->with($website)
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);

        $this->assertUrlTypeCalls($urlSecurityType, $website);
        $entity = $this->getSluggableEntity($slug);
        $this->assertRequestCalls($entity, $expectedBaseUrl);
        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($entity, null, $website));
    }

    public function testGetDirectUrlForSecureCanonical(): void
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'https://example.com/';
        $expectedUrl = 'https://example.com/index_dev.php/canonical';
        $expectedBaseUrl = '/index_dev.php';
        $urlSecurityType = Configuration::SECURE;
        $website = $this->getWebsite(777);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteSecureUrl')
            ->with($website)
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);

        $this->assertUrlTypeCalls($urlSecurityType, $website);
        $entity = $this->getSluggableEntity($slug);
        $this->assertRequestCalls($entity, $expectedBaseUrl);
        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($entity, null, $website));
    }

    /**
     * @dataProvider localizedUrlDataProvider
     */
    public function testGetDirectUrl(
        string $expectedUrl,
        ?Localization $localization = null,
        ?Localization $currentLocalization = null
    ): void {
        $expectedWebsiteUrl = 'http://example.com/';

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_redirect.use_localized_canonical', false, false, null, true]
            ]);
        $this->cache->expects(self::any())
            ->method('get')
            ->willReturn('oro_redirect.use_localized_canonical');

        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);

        $this->localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($currentLocalization);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteUrl')
            ->willReturn($expectedWebsiteUrl);

        $baseSlug = new Slug();
        $baseSlug->setUrl('/canonical_base');
        $baseSlug->setRouteName('route_name');
        $baseSlug->setRouteParameters([]);

        $slug = new Slug();
        $slug->setUrl('/canonical');
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);
        $slug->setLocalization($localization1);

        $slug2 = new Slug();
        $slug2->setUrl('/canonical_2');
        $slug2->setRouteName('route_name');
        $slug2->setRouteParameters([]);
        $slug2->setLocalization($localization2);

        $this->assertUrlTypeCalls(Configuration::INSECURE);
        $entity = new SluggableEntityStub();
        $entity->addSlug($baseSlug);
        $entity->addSlug($slug);
        $entity->addSlug($slug2);
        $this->assertRequestCalls($entity);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getDirectUrl($entity, $localization));
    }

    public function localizedUrlDataProvider(): array
    {
        return [
            'current used' => [
                'http://example.com/canonical',
                null,
                $this->getLocalization(1)
            ],
            'base used when not found for current' => [
                'http://example.com/canonical_base',
                null,
                $this->getLocalization(3)
            ],
            'base used when no current' => [
                'http://example.com/canonical_base',
                null,
                null
            ],
            'by locale no current' => [
                'http://example.com/canonical',
                $this->getLocalization(1),
                null
            ],
            'by locale with another current' => [
                'http://example.com/canonical_2',
                $this->getLocalization(2),
                $this->getLocalization(1)
            ]
        ];
    }

    public function testGetSystemUrlForInsecureCanonical(): void
    {
        $expectedUrl = 'http://example.com/canonical';
        $website = $this->getWebsite(1);

        $data = $this->createMock(SluggableInterface::class);

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->assertUrlTypeCalls(Configuration::INSECURE, $website);

        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsitePath')
            ->with($route, $routeParameters, $website)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data, null, $website));
    }

    public function testGetSystemUrlForSecureCanonical(): void
    {
        $expectedUrl = 'https://example.com/canonical';
        $website = $this->getWebsite(777);

        $data = $this->createMock(SluggableInterface::class);

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->cache->expects(self::any())
            ->method('get')
            ->willReturn(Configuration::SECURE);

        $this->assertUrlTypeCalls(Configuration::SECURE, $website);

        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsiteSecurePath')
            ->with($route, $routeParameters, $website)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data, null, $website));
    }

    public function testGetUrlWithoutDirect(): void
    {
        $data = $this->createMock(SluggableInterface::class);
        $data->expects(self::any())
            ->method('getSlugs')
            ->willReturn(new ArrayCollection([]));

        $this->cache->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_redirect.canonical_url_type', Configuration::DIRECT_URL],
                ['oro_redirect.canonical_url_security_type', Configuration::INSECURE],
                ['oro_redirect.use_localized_canonical', true]
            ]);

        $this->configManager->expects(self::never())
            ->method('get');

        $expectedUrl = 'http://example.com/canonical';

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->routingInformationProvider->expects(self::once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects(self::any())
            ->method('getWebsitePath')
            ->with($route, $routeParameters)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data));
    }

    public function testClearCacheWithoutWebsite(): void
    {
        $this->cache->expects(self::exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['oro_redirect.canonical_url_type'],
                ['oro_redirect.canonical_url_security_type'],
                ['oro_redirect.use_localized_canonical']
            );
        $this->canonicalUrlGenerator->clearCache();
    }

    public function testClearCacheWithWebsite(): void
    {
        $website = $this->getWebsite(777);
        $this->cache->expects(self::exactly(3))
            ->method('delete')
            ->withConsecutive(
                [sprintf('oro_redirect.canonical_url_type.%s', 777)],
                [sprintf('oro_redirect.canonical_url_security_type.%s', 777)],
                ['oro_redirect.use_localized_canonical']
            );

        $this->canonicalUrlGenerator->clearCache($website);
    }

    public function testGetCanonicalDomainUrlSecure(): void
    {
        $host = 'https://host.domain';
        $website = $this->getWebsite(777);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type', false, false, $website)
            ->willReturn(Configuration::SECURE);

        $this->cache->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type.777')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->websiteUrlResolver->expects(self::atLeastOnce())
            ->method('getWebsiteSecureUrl')
            ->with($website)
            ->willReturn($host);

        $this->assertEquals($host, $this->canonicalUrlGenerator->getCanonicalDomainUrl($website));
    }

    public function testGetCanonicalDomainUrlNotSecure(): void
    {
        $host = 'http://host.domain';
        $website = $this->getWebsite(777);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type', false, false, $website)
            ->willReturn(Configuration::INSECURE);
        $this->cache->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type.777')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->websiteUrlResolver->expects(self::atLeastOnce())
            ->method('getWebsiteUrl')
            ->with($website)
            ->willReturn($host);

        $this->assertEquals($host, $this->canonicalUrlGenerator->getCanonicalDomainUrl($website));
    }

    public function testGetAbsoluteUrl(): void
    {
        $host = 'https://host.domain/hello/';
        $url = '/test/my/url';
        $website = $this->getWebsite(777);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type', false, false, $website)
            ->willReturn(Configuration::SECURE);

        $this->cache->expects(self::once())
            ->method('get')
            ->with('oro_redirect.canonical_url_security_type.777')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->websiteUrlResolver->expects(self::atLeastOnce())
            ->method('getWebsiteSecureUrl')
            ->with($website)
            ->willReturn($host);

        $this->assertEquals(
            'https://host.domain/hello/test/my/url',
            $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website)
        );
    }

    public function testCreateUrlWithMainRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn('base');
        $this->requestStack->expects(self::atMost(1))
            ->method('getMainRequest')
            ->willReturn($request);

        $host = 'https://host.domain/';
        $url = '/test/my/url';

        $this->assertEquals(
            'https://host.domain/base/test/my/url',
            $this->canonicalUrlGenerator->createUrl($host, $url)
        );
    }

    public function testCreateUrlWithoutMainRequest(): void
    {
        $this->requestStack->expects(self::atMost(1))
            ->method('getMainRequest')
            ->willReturn(null);

        $host = 'https://host.domain/';
        $url = '/test/my/url';

        $this->assertEquals(
            'https://host.domain/test/my/url',
            $this->canonicalUrlGenerator->createUrl($host, $url)
        );
    }
}
