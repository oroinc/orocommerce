<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Routing;

use Doctrine\Common\Cache\ArrayCache;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlKeyValueCache;
use Oro\Bundle\RedirectBundle\Cache\UrlLocalCache;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlCacheAwareProvider;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class SluggableUrlGeneratorTest extends WebTestCase
{
    use EntityTrait;
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadSlugsData::class
            ]
        );
    }

    public function testGenerateForNullRouteName()
    {
        /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject $localizationManager */
        $localizationManager = $this->createMock(UserLocalizationManager::class);
        $localizationManager->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn(null);
        $urlGenerator = $this->getUrlGenerator('database', 'local', $localizationManager);

        $this->expectException(RouteNotFoundException::class);
        $urlGenerator->generate(null);
    }

    /**
     * @dataProvider urlServicesDataProvider
     * @param string $urlProviderType
     * @param string $urlCacheService
     */
    public function testGenerateUrlFirstDefaultLoadedThenLocalized($urlProviderType, $urlCacheService)
    {
        /** @var Slug $defaultSlug */
        $defaultSlug = $this->getReference(LoadSlugsData::SLUG_URL_LOCALIZATION_1);
        /** @var Slug $localizedSlug */
        $localizedSlug = $this->getReference(LoadSlugsData::SLUG_URL_LOCALIZATION_2);

        $localization = $this->getEntity(Localization::class, ['id' => $localizedSlug->getLocalization()->getId()]);
        /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject $localizationManager */
        $localizationManager = $this->createMock(UserLocalizationManager::class);
        $localizationManager->expects($this->exactly(2))
            ->method('getCurrentLocalization')
            ->willReturnOnConsecutiveCalls(
                null,
                $localization
            );

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationManager);

        $this->assertEquals(
            $defaultSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
        $this->assertEquals(
            $localizedSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
    }

    /**
     * @dataProvider urlServicesDataProvider
     * @param string $urlProviderType
     * @param string $urlCacheService
     */
    public function testGenerateUrlWithFallbackToDefaultSlug($urlProviderType, $urlCacheService)
    {
        /** @var Slug $defaultSlug */
        $defaultSlug = $this->getReference(LoadSlugsData::SLUG_URL_PAGE_2);

        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject $localizationManager */
        $localizationManager = $this->createMock(UserLocalizationManager::class);
        $localizationManager->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationManager);

        $this->assertEquals(
            $defaultSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
    }

    /**
     * @dataProvider urlServicesDataProvider
     * @param string $urlProviderType
     * @param string $urlCacheService
     */
    public function testGenerateUrlLocalizedVersionWithoutFallbacks($urlProviderType, $urlCacheService)
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::PAGE_3_LOCALIZED_EN_CA);

        $localization = $this->getEntity(Localization::class, ['id' => $slug->getLocalization()->getId()]);
        /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject $localizationManager */
        $localizationManager = $this->createMock(UserLocalizationManager::class);
        $localizationManager->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationManager);

        $this->assertEquals(
            $slug->getUrl(),
            $urlGenerator->generate($slug->getRouteName(), $slug->getRouteParameters())
        );
    }

    public function urlServicesDataProvider(): array
    {
        return [
            'provider=database,cache=local' => ['database', 'local'],
            'provider=database,cache=storage' => ['database', 'storage'],
            'provider=database,cache=key_value' => ['database', 'key_value'],
            'provider=cache,cache=storage' => ['cache', 'storage'],
            'provider=cache,cache=key_value' => ['cache', 'key_value'],
            // provider=cache,cache=local is ambiguous because it's equivalent to disabled slugs
        ];
    }

    /**
     * @param string $urlProviderType
     * @param string $urlCacheType
     * @param UserLocalizationManager $localizationManager
     * @return SluggableUrlGenerator
     */
    private function getUrlGenerator(
        $urlProviderType,
        $urlCacheType,
        UserLocalizationManager $localizationManager
    ): SluggableUrlGenerator {
        $urlCache = $this->createCache($urlCacheType);
        $cacheUrlProvider = new SluggableUrlCacheAwareProvider($urlCache);

        if ($urlProviderType === 'cache') {
            $urlProvider = $cacheUrlProvider;
        } else {
            $urlProvider = new SluggableUrlDatabaseAwareProvider(
                $cacheUrlProvider,
                $urlCache,
                $this->getContainer()->get('doctrine')
            );
        }

        $contextUrlProviders = $this->createMock(ContainerInterface::class);
        $contextUrlProviders->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $urlGenerator = new SluggableUrlGenerator(
            $urlProvider,
            new ContextUrlProviderRegistry($contextUrlProviders),
            $localizationManager,
            self::getConfigManager(null)
        );
        $urlGenerator->setBaseGenerator($this->getContainer()->get('router.default'));

        return $urlGenerator;
    }

    /**
     * @param string $type
     * @return UrlCacheInterface
     */
    private function createCache($type)
    {
        switch ($type) {
            case 'storage':
                $persistentCache = new PhpFileCache(
                    $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'oro_data'
                );
                $persistentCache->setNamespace('oro_slug_url_test');

                return new UrlStorageCache(
                    $persistentCache,
                    new ArrayCache(),
                    $this->getContainer()->get('filesystem'),
                    2
                );

            case 'key_value':
                $persistentCache = new FilesystemCache(
                    $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'oro_data'
                );
                $persistentCache->setNamespace('oro_slug_kv_test');

                return new UrlKeyValueCache(
                    $persistentCache,
                    new ArrayCache(),
                    $this->getContainer()->get('filesystem')
                );

            case 'local':
            default:
                return new UrlLocalCache(new ArrayCache());
        }
    }
}
