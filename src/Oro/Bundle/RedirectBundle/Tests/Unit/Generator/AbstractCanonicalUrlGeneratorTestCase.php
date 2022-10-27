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
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

abstract class AbstractCanonicalUrlGeneratorTestCase extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var RoutingInformationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $routingInformationProvider;

    /**
     * @var WebsiteUrlResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteUrlResolver;

    /**
     * @var LocalizationProviderInterface
     */
    protected $localizationProvider;

    /**
     * @var CanonicalUrlGenerator
     */
    protected $canonicalUrlGenerator;

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

    protected function assertUrlTypeCalls(string $urlSecurityType, WebsiteInterface $website = null): void
    {
        $urlTypeKey = 'oro_redirect.canonical_url_type';
        $urlSecurityTypeKey = 'oro_redirect.canonical_url_security_type';
        if ($website) {
            $urlTypeKey .= '.' . $website->getId();
            $urlSecurityTypeKey .= '.' . $website->getId();
        }
        $this->cache->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($cacheKey) use ($urlTypeKey, $urlSecurityTypeKey, $urlSecurityType) {
                    switch ($cacheKey) {
                        case $urlTypeKey:
                            return Configuration::DIRECT_URL;
                        case $urlSecurityTypeKey:
                            return $urlSecurityType;
                    }
                }
            );

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [$urlTypeKey, false, false, $website, Configuration::DIRECT_URL],
                [$urlSecurityTypeKey, false, false, $website, $urlSecurityType]
            ]);
    }

    protected function assertRequestCalls(
        SluggableInterface $data,
        ?string $expectedBaseUrl = null
    ): void {
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($expectedBaseUrl);
        $this->requestStack->expects($this->atMost(1))
            ->method('getMainRequest')
            ->willReturn($request);

        $this->routingInformationProvider->expects($this->never())
            ->method('getRouteData')
            ->with($data);
    }

    /**
     * @param Slug $slug
     * @return SluggableInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSluggableEntity(Slug $slug)
    {
        $entity = new SluggableEntityStub();
        $entity->addSlug($slug);

        return $entity;
    }
}
