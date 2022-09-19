<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Routing;

use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
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
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class SluggableUrlGeneratorTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadSlugsData::class]);
    }

    /**
     * @dataProvider urlServicesDataProvider
     */
    public function testGenerateUrlFirstDefaultLoadedThenLocalized(
        string $urlProviderType,
        string $urlCacheService
    ): void {
        /** @var Slug $defaultSlug */
        $defaultSlug = $this->getReference(LoadSlugsData::SLUG_URL_LOCALIZATION_1);
        /** @var Slug $localizedSlug */
        $localizedSlug = $this->getReference(LoadSlugsData::SLUG_URL_LOCALIZATION_2);

        $localization = $this->createLocalization($localizedSlug->getLocalization()->getId());
        /** @var LocalizationProviderInterface|MockObject $localizationProvider */
        $localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $localizationProvider->expects(self::exactly(2))
            ->method('getCurrentLocalization')
            ->willReturnOnConsecutiveCalls(
                null,
                $localization
            );

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationProvider);

        self::assertEquals(
            $defaultSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
        self::assertEquals(
            $localizedSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
    }

    /**
     * @dataProvider urlServicesDataProvider
     */
    public function testGenerateUrlWithFallbackToDefaultSlug(string $urlProviderType, string $urlCacheService): void
    {
        /** @var Slug $defaultSlug */
        $defaultSlug = $this->getReference(LoadSlugsData::SLUG_URL_PAGE_2);

        $localization = $this->createLocalization(1);
        /** @var LocalizationProviderInterface|MockObject $localizationProvider */
        $localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationProvider);

        self::assertEquals(
            $defaultSlug->getUrl(),
            $urlGenerator->generate($defaultSlug->getRouteName(), $defaultSlug->getRouteParameters())
        );
    }

    /**
     * @dataProvider urlServicesDataProvider
     */
    public function testGenerateUrlLocalizedVersionWithoutFallbacks(
        string $urlProviderType,
        string $urlCacheService
    ): void {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::PAGE_3_LOCALIZED_EN_CA);

        $localization = $this->createLocalization($slug->getLocalization()->getId());
        /** @var LocalizationProviderInterface|MockObject $localizationProvider */
        $localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $localizationProvider->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $urlGenerator = $this->getUrlGenerator($urlProviderType, $urlCacheService, $localizationProvider);

        self::assertEquals(
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

    private function createLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    private function getUrlGenerator(
        string $urlProviderType,
        string $urlCacheType,
        LocalizationProviderInterface $localizationProvider
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
        $contextUrlProviders->expects(self::any())
            ->method('has')
            ->willReturn(false);

        $urlGenerator = new SluggableUrlGenerator(
            $urlProvider,
            new ContextUrlProviderRegistry($contextUrlProviders),
            $localizationProvider,
            self::getConfigManager(null)
        );
        $urlGenerator->setBaseGenerator($this->getContainer()->get('router.default'));

        return $urlGenerator;
    }

    private function createCache(string $type): UrlCacheInterface
    {
        switch ($type) {
            case 'storage':
                $persistentCache = new PhpFileCache(
                    'oro_slug_url_test',
                    0,
                    $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'oro_data'
                );

                return new UrlStorageCache(
                    $persistentCache,
                    new ArrayAdapter(0, false),
                    $this->getContainer()->get('filesystem'),
                    2
                );

            case 'key_value':
                $persistentCache = new FilesystemCache(
                    'oro_slug_kv_test',
                    0,
                    $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'oro_data'
                );

                return new UrlKeyValueCache(
                    $persistentCache,
                    new ArrayAdapter(0, false)
                );

            case 'local':
            default:
                return new UrlLocalCache(new ArrayAdapter(0, false));
        }
    }
}
