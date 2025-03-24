<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractCanonicalUrlGeneratorTestCase extends TestCase
{
    protected ConfigManager&MockObject $configManager;
    protected CacheInterface&MockObject $cache;
    protected RequestStack&MockObject $requestStack;
    protected RoutingInformationProvider&MockObject $routingInformationProvider;
    protected WebsiteUrlResolver&MockObject $websiteUrlResolver;
    protected LocalizationProviderInterface&MockObject $localizationProvider;
    protected CanonicalUrlGenerator $canonicalUrlGenerator;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProvider::class);
        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->canonicalUrlGenerator = $this->createGenerator();
    }

    abstract protected function createGenerator(): CanonicalUrlGenerator;

    protected function assertUrlTypeCalls(string $urlSecurityType, ?WebsiteInterface $website = null): void
    {
        $urlTypeKey = 'oro_redirect.canonical_url_type';
        $urlSecurityTypeKey = 'oro_redirect.canonical_url_security_type';
        if ($website) {
            $urlTypeKey .= '.' . $website->getId();
            $urlSecurityTypeKey .= '.' . $website->getId();
        }
        $this->cache->expects(self::any())
            ->method('get')
            ->willReturnCallback(
                function ($cacheKey) use ($urlTypeKey, $urlSecurityTypeKey, $urlSecurityType) {
                    switch ($cacheKey) {
                        case $urlTypeKey:
                            return Configuration::DIRECT_URL;
                        case $urlSecurityTypeKey:
                            return $urlSecurityType;
                    }

                    return null;
                }
            );

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [$urlTypeKey, false, false, $website, Configuration::DIRECT_URL],
                [$urlSecurityTypeKey, false, false, $website, $urlSecurityType]
            ]);
    }

    protected function assertRequestCalls(
        SluggableInterface $data,
        string $expectedBaseUrl = ''
    ): void {
        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn($expectedBaseUrl);
        $this->requestStack->expects(self::atMost(1))
            ->method('getMainRequest')
            ->willReturn($request);

        $this->routingInformationProvider->expects(self::never())
            ->method('getRouteData')
            ->with($data);
    }

    protected function getSluggableEntity(Slug $slug): SluggableInterface
    {
        $entity = new SluggableEntityStub();
        $entity->addSlug($slug);

        return $entity;
    }
}
