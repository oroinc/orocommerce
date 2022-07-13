<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

class CategoryBreadcrumbProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );

        $this->loadFixtures(
            [
                LoadMasterCatalogLocalizedTitles::class,
                LoadCategoryData::class,
                LoadCategoryVisibilityData::class,
            ]
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testHaveCategoriesInBreadcrumbs(string $category, array $urlParts): void
    {
        $url = $this->getUrl(
            'oro_product_frontend_product_index',
            [
                RequestProductHandler::CATEGORY_ID_KEY => $this->getReference($category)->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => 1,
            ]
        );

        $crawler = $this->client->request(
            'GET',
            $url
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $breadCrumbsNodes = $crawler->filter('.breadcrumbs__item a');
        self::assertEquals(count($urlParts), $breadCrumbsNodes->count());

        foreach ($breadCrumbsNodes as $key => $node) {
            self::assertNotNull($node->getAttribute('href'));
            self::assertNotNull($node->textContent);
            self::assertEquals($urlParts[$key], trim($node->textContent));
        }
    }

    public function dataProvider(): array
    {
        return [
            [
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'urlParts' => [
                    'master',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ],
            ],
            [
                'category' => LoadCategoryData::THIRD_LEVEL1,
                'urlParts' => [
                    'master',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                    LoadCategoryData::THIRD_LEVEL1,
                ],
            ],
            [
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'urlParts' => [
                    'master',
                    LoadCategoryData::FIRST_LEVEL,
                    LoadCategoryData::SECOND_LEVEL1,
                ],
            ],
        ];
    }
}
