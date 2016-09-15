<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryRegionsControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testGetAction()
    {
        $this->client->request('GET', $this->getUrl('oro_api_frontend_country_get_regions', ['country' => 'US']));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
    }
}
