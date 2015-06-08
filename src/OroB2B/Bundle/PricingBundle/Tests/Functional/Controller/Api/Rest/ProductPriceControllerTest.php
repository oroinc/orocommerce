<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * @dbIsolation
 */
class ProductPriceControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices']);
    }

    public function testDelete()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.1');

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_pricing_delete_product_price', ['id' => $productPrice->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
