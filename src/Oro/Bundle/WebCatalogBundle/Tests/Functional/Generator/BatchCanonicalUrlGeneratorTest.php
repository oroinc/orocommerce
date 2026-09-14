<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Generator\BatchCanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogPageVariantsData;
use Oro\Component\Testing\QueryCounter;

class BatchCanonicalUrlGeneratorTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const string WEB_CATALOG_CANONICAL_URL = 'oro_web_catalog.enable_web_catalog_canonical_url';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWebCatalogPageVariantsData::class]);

        $this->setConfigValue(
            WebCatalogUsageProvider::SETTINGS_KEY,
            $this->getReference(LoadWebCatalogData::CATALOG_1)->getId()
        );
        $this->setConfigValue('oro_redirect.canonical_url_type', Configuration::DIRECT_URL);
        $this->getCanonicalUrlGenerator()->clearCache();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
        $this->getCanonicalUrlGenerator()->clearCache();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Page::class);
    }

    private function getCanonicalUrlGenerator(): CanonicalUrlGenerator
    {
        return self::getContainer()->get('oro_redirect.generator.canonical_url');
    }

    private function getBatchCanonicalUrlGenerator(): BatchCanonicalUrlGenerator
    {
        return self::getContainer()->get('oro_web_catalog.tests.alias.generator.batch_canonical_url');
    }

    /**
     * @return int[]
     */
    private function getPageIds(): array
    {
        $ids = [];
        foreach ([LoadPageData::PAGE_1, LoadPageData::PAGE_2] as $reference) {
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

    public function testGetUrlsWhenFeatureIsEnabled(): void
    {
        $this->setConfigValue(self::WEB_CATALOG_CANONICAL_URL, true);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        // the URL comes from the content variant of the web catalog node, not from the slug of the page,
        // and out of the two variants of the first page the one on the topmost node wins
        self::assertStringEndsWith('/' . LoadContentNodesData::CATALOG_1_ROOT, $expectedUrls[$ids[0]]);
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    public function testGetUrlsWhenFeatureIsDisabled(): void
    {
        $this->setConfigValue(self::WEB_CATALOG_CANONICAL_URL, false);

        $ids = $this->getPageIds();
        $expectedUrls = $this->getExpectedUrls($ids);

        self::assertStringNotContainsString(LoadContentNodesData::CATALOG_1_ROOT, $expectedUrls[$ids[0]]);
        self::assertSame($expectedUrls, $this->getBatchCanonicalUrlGenerator()->getUrls(Page::class, $ids));
    }

    /**
     * Both pages have a content variant, so the whole batch is answered by one variant lookup
     * and one slug query, and the parent generator is not involved at all.
     */
    public function testGetUrlsQueryCountDoesNotGrowWithBatchSize(): void
    {
        $this->setConfigValue(self::WEB_CATALOG_CANONICAL_URL, true);

        $generator = $this->getBatchCanonicalUrlGenerator();
        $em = $this->getEntityManager();
        $queryCounter = new QueryCounter($em);
        $ids = $this->getPageIds();

        // warm up the configuration, the web catalog and the website URL caches
        $generator->getUrls(Page::class, $ids);

        $em->clear();
        $queriesForOnePage = $queryCounter->countQueries(fn () => $generator->getUrls(Page::class, [$ids[0]]));

        $em->clear();
        $queriesForAllPages = $queryCounter->countQueries(fn () => $generator->getUrls(Page::class, $ids));

        self::assertSame(
            $queriesForOnePage,
            $queriesForAllPages,
            'Resolving canonical URLs for a batch must not cost more queries than for a single entity'
        );
    }
}
