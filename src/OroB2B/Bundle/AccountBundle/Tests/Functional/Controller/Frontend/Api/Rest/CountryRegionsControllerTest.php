<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryRegionsControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testGetAction()
    {
        $this->client->request('GET', $this->getUrl('orob2b_api_frontend_country_get_regions', ['country' => 'US']));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
    }
}
