<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WebsiteSearchControllerTest extends WebTestCase
{
    const SEARCH_STRING = 'string-to-search';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
    }

    public function testSearchResultsAction()
    {
        $this->client->followRedirects(true);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_website_search_results',
                ['search' => static::SEARCH_STRING]
            )
        );

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
        $urlParams['grid'] = [
            'frontend-product-search-grid' => "f%5Ball_text%5D%5Bvalue%5D={$searchString}&f%5Ball_text%5D%5Btype%5D=1"
        ];
        $expectedUrl = $this->getContainer()->get('router')->generate('oro_product_frontend_product_index', $urlParams);
        $this->assertEquals($expectedUrl, $this->client->getRequest()->getRequestUri());
    }
}
