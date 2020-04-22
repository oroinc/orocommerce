<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductGridPagerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/DataFixtures/frontend_product_grid_pager_fixture.yml',
            LoadFrontendProductData::class,
        ]);
    }

    public function testPagerButtons()
    {
        $this->client->request('GET', $this->getUrl('oro_product_frontend_product_search'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $nextPageLink = $this->client->getCrawler()->filter('.oro-pagination__next');
        $prevPageLink = $this->client->getCrawler()->filter('.oro-pagination__prev');
        $currentPage = $this->client->getCrawler()->filter('.oro-pagination__input');

        $this->assertEquals(1, $currentPage->attr('value'));
        $this->assertContains('disable', $prevPageLink->attr('class'));
        $this->assertEquals('#', $prevPageLink->attr('href'));
        $this->assertNotContains('disable', $nextPageLink->attr('class'));
        $this->assertNotEquals('#', $nextPageLink->attr('href'));

        $this->client->click($nextPageLink->link());

        $nextPageLink = $this->client->getCrawler()->filter('.oro-pagination__next');
        $prevPageLink = $this->client->getCrawler()->filter('.oro-pagination__prev');
        $currentPage = $this->client->getCrawler()->filter('.oro-pagination__input');

        $this->assertEquals(2, $currentPage->attr('value'));
        $this->assertNotContains('disable', $prevPageLink->attr('class'));
        $this->assertNotEquals('#', $prevPageLink->attr('href'));
        $this->assertContains('disable', $nextPageLink->attr('class'));
        $this->assertEquals('#', $nextPageLink->attr('href'));
    }
}
