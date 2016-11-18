<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WebsiteSearchControllerTest extends WebTestCase
{
    const SEARCH_STRING = 'string-to-search';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testSearchResultsAction()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        // assert search widget exists on page
        $searchFieldBlock = $crawler->filter('div.search-widget');
        $this->assertGreaterThan(0, $searchFieldBlock->count());

        // search form processing
        $searchForm           = $searchFieldBlock->selectButton('oro_website_search_search_button')->form();
        $searchForm['search'] = static::SEARCH_STRING;

        // submit the form
        $this->client->followRedirects(true);
        $this->client->submit($searchForm);

        // assert product page has been rendered
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertProductPageWithFilters(static::SEARCH_STRING);
    }

    /**
     * @param string $searchString
     */
    private function assertProductPageWithFilters($searchString)
    {
        $urlParams = ['frontend-product-search-grid' => [
            '_filter' => ['all_text' => ['value' => $searchString, 'type' => 1]]
        ]];
        $expectedUrl = $this->getContainer()->get('router')->generate('oro_product_frontend_product_index', $urlParams);
        $this->assertEquals($expectedUrl, $this->client->getRequest()->getRequestUri());
    }
}
