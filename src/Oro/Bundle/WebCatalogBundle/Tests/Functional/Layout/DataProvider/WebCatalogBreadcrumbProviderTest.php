<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogCategoryVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class WebCatalogBreadcrumbProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use SearchExtensionTrait;

    private ?int $initialWebCatalogId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadWebCatalogData::class,
            LoadWebCatalogCategoryVariantsData::class
        ]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get(WebCatalogUsageProvider::SETTINGS_KEY);
        $configManager->set(
            WebCatalogUsageProvider::SETTINGS_KEY,
            $this->getReference(LoadWebCatalogData::CATALOG_1)->getId()
        );
        $configManager->flush();

        self::getContainer()->get('oro_website_search.indexer')->reindex();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(WebCatalogUsageProvider::SETTINGS_KEY, $this->initialWebCatalogId);
        $configManager->flush();

        parent::tearDown();
    }

    /**
     * @dataProvider getSlugs
     */
    public function testBreadcrumbs(string $reference, int $expectedCount, array $expectedBreadcrumbs): void
    {
        $crawler = $this->client->request('GET', '/' . $reference);
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(
            $reference,
            $crawler->filter('title')->html()
        );

        self::assertStringContainsString(
            $reference,
            $crawler->filter('h1.category-title')->html()
        );

        $breadcrumbs = [];
        foreach ($crawler->filter('.breadcrumbs__item a') as $key => $item) {
            self::assertEquals($expectedBreadcrumbs[$key], $item->textContent);
            $breadcrumbs[] = trim($item->textContent);
        }

        self::assertCount($expectedCount, $breadcrumbs);
    }

    public function testBreadcrumbsWhenInSubfolder(): void
    {
        // Emulates subfolder request.
        $baseUrl = '/custom/base/url/';
        $crawler = $this->client->request(
            'GET',
            $baseUrl . 'app.php/' . LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
            [],
            [],
            [
                'SCRIPT_NAME' => '/custom/base/url/app.php',
                'SCRIPT_FILENAME' => 'app.php'
            ]
        );

        $breadcrumbUrls = [];

        foreach ($crawler->filter('.breadcrumbs__item a') as $key => $item) {
            $breadcrumbUrls[] = $item->getAttribute('href');
        }

        self::assertCount(2, $breadcrumbUrls);
        foreach ($breadcrumbUrls as $url) {
            self::assertStringContainsString($baseUrl . 'app.php/', $url);
        }
    }

    public function getSlugs(): array
    {
        return [
            [
                LoadContentNodesData::CATALOG_1_ROOT,
                0,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                1,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                2,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                ]
            ],
            [
                LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                2,
                [
                    LoadContentNodesData::CATALOG_1_ROOT,
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                ]
            ],
        ];
    }
}
