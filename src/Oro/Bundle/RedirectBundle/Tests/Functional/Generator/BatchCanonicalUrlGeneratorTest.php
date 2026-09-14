<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\QueryCounter;

class BatchCanonicalUrlGeneratorTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const string CANONICAL_URL_TYPE = 'oro_redirect.canonical_url_type';
    private const string USE_LOCALIZED_CANONICAL = 'oro_redirect.use_localized_canonical';
    private const string WEB_CATALOG_CANONICAL_URL = 'oro_web_catalog.enable_web_catalog_canonical_url';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSlugsData::class]);

        // this generator does not resolve web catalog based canonical URLs
        $this->setConfigValue(self::WEB_CATALOG_CANONICAL_URL, false);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
        $this->getCanonicalUrlGenerator()->clearCache();
        $this->getLocalizationProvider()->setCurrentLocalization(null);
    }

    private function getLocalizationProvider(): LocalizationProviderInterface
    {
        return self::getContainer()->get('oro_locale.provider.current_localization');
    }

    private function setCurrentLocalization(string $localizationCode): void
    {
        $this->getLocalizationProvider()->setCurrentLocalization($this->getReference($localizationCode));
    }

    private function getCanonicalUrlGenerator(): CanonicalUrlGenerator
    {
        return self::getContainer()->get('oro_redirect.generator.canonical_url');
    }

    private function getBatchCanonicalUrlGenerator(): BatchCanonicalUrlGenerator
    {
        // the service identifier yields the web catalog aware decorator, this test covers the base generator
        return new BatchCanonicalUrlGenerator(
            $this->getCanonicalUrlGenerator(),
            self::getContainer()->get('oro_entity.doctrine_helper')
        );
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Page::class);
    }

    private function setCanonicalUrlType(string $type): void
    {
        $this->setConfigValue(self::CANONICAL_URL_TYPE, $type);
        // the configuration values are cached in memory
        $this->getCanonicalUrlGenerator()->clearCache();
    }

    /**
     * @return int[]
     */
    private function getPageIds(): array
    {
        $ids = [];
        foreach ([LoadPageData::PAGE_1, LoadPageData::PAGE_2, LoadPageData::PAGE_3] as $reference) {
            /** @var Page $page */
            $page = $this->getReference($reference);
            $ids[] = $page->getId();
        }

        return $ids;
    }

    /**
     * @return array [page id => canonical url, ...] as it is advertised by the storefront
     */
    private function getExpectedUrls(array $ids): array
    {
        $generator = $this->getCanonicalUrlGenerator();
        $em = $this->getEntityManager();

        $urls = [];
        foreach ($ids as $id) {
            $urls[$id] = $generator->getUrl($em->find(Page::class, $id));
        }

        return $urls;
    }

    public function testGetUrlsForDirectUrlType(): void
    {
        $this->setCanonicalUrlType(Configuration::DIRECT_URL);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        self::assertStringEndsWith(
            LoadSlugsData::SLUG_URL_ANONYMOUS,
            $expectedUrls[$ids[0]],
            'The direct URL of the first page is expected to be resolved from its slug'
        );
        // the second page has no slug, so it falls back to the system URL built from its route data
        self::assertStringEndsWith('/' . $ids[1], $expectedUrls[$ids[1]]);
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    public function testGetUrlsForSystemUrlType(): void
    {
        $this->setCanonicalUrlType(Configuration::SYSTEM_URL);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        // the system URL ends with the entity id, see PageRoutingInformationProvider
        self::assertStringEndsWith('/' . $ids[0], $expectedUrls[$ids[0]]);
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    /**
     * The third page has a base slug and an "en_CA" slug. The current localization must be "en_CA":
     * a slug is matched by an exact localization, so the default "en_US" gives the base slug anyway
     * and both cases below would pass without checking anything.
     */
    public function testGetUrlsWhenLocalizedCanonicalUrlsAreEnabled(): void
    {
        $this->setCanonicalUrlType(Configuration::DIRECT_URL);
        $this->setConfigValue(self::USE_LOCALIZED_CANONICAL, true);
        $this->getCanonicalUrlGenerator()->clearCache();
        $this->setCurrentLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        self::assertStringEndsWith(
            LoadSlugsData::PAGE_3_LOCALIZED_EN_CA,
            $expectedUrls[$ids[2]],
            'The slug of the current localization is expected to be used'
        );
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    public function testGetUrlsWhenLocalizedCanonicalUrlsAreDisabled(): void
    {
        $this->setCanonicalUrlType(Configuration::DIRECT_URL);
        $this->setConfigValue(self::USE_LOCALIZED_CANONICAL, false);
        $this->getCanonicalUrlGenerator()->clearCache();
        $this->setCurrentLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        self::assertStringEndsWith(
            LoadSlugsData::PAGE_3_DEFAULT,
            $expectedUrls[$ids[2]],
            'The base slug is expected to be used when the localized canonical URLs are disabled'
        );
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    public function testGetDirectUrlsOmitsEntitiesWithoutSlug(): void
    {
        $ids = $this->getPageIds();

        $urls = $this->getBatchCanonicalUrlGenerator()->getDirectUrls(Page::class, $ids);

        // LoadSlugsData leaves the second page without a slug
        self::assertArrayNotHasKey($ids[1], $urls);
        self::assertStringEndsWith(LoadSlugsData::SLUG_URL_ANONYMOUS, $urls[$ids[0]]);
        self::assertNotEmpty($urls[$ids[2]]);
        self::assertCount(2, $urls);
    }

    /**
     * The batch mixes both paths: the first and the third page are resolved from a slug in one query,
     * the second one has no slug and goes through an entity reference that must cost nothing.
     */
    public function testGetUrlsQueryCountDoesNotGrowWithBatchSize(): void
    {
        $this->setCanonicalUrlType(Configuration::DIRECT_URL);

        $generator = $this->getBatchCanonicalUrlGenerator();
        $em = $this->getEntityManager();
        $queryCounter = new QueryCounter($em);
        $ids = $this->getPageIds();

        // warm up the configuration and the website URL caches
        $generator->getUrls(Page::class, $ids);

        // a managed entity would let the system URL fallback skip the reference
        $em->clear();
        $queriesForOneEntity = $queryCounter->countQueries(fn () => $generator->getUrls(Page::class, [$ids[0]]));

        $em->clear();
        $queriesForAllEntities = $queryCounter->countQueries(fn () => $generator->getUrls(Page::class, $ids));

        self::assertSame(
            $queriesForOneEntity,
            $queriesForAllEntities,
            'Resolving canonical URLs for a batch must not cost more queries than for a single entity'
        );
    }
}
