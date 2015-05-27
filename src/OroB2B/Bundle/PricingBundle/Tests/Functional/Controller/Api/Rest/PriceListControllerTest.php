<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dpIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\Fixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_customer_delete_customer', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_view', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 404);
    }
}
