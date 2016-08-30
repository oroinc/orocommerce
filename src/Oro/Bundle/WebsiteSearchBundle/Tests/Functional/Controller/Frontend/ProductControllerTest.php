<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const SIDEBAR_ROUTE = 'orob2b_catalog_frontend_category_product_sidebar';

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testSidebarAction()
    {
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                static::SIDEBAR_ROUTE,
                [RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId()]
            ),
            ['_widgetContainer' => 'widget']
        );
        $json = $crawler->filterXPath('//*[@data-page-component-options]')->attr('data-page-component-options');
        $this->assertJson($json);
        $arr = json_decode($json, true);
        $this->assertEquals($arr['defaultCategoryId'], $secondLevelCategory->getId());
        $this->assertCount(8, $arr['data']);
    }
}
