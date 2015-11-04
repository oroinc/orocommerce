<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AjaxProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts'
            ]
        );
    }

    public function testProductNamesBySkus()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $skus = [
            'not a sku',
            $product1->getSku(),
            $product2->getSku(),
        ];

        $expectedData = [
            $product1->getSku() => ['name'=> $product1->getDefaultName()->getString()],
            $product2->getSku() => ['name'=> $product2->getDefaultName()->getString()],
        ];

        $this->client->request(
            'POST',
            $this->getUrl('orob2b_product_frontend_ajax_names_by_skus'),
            ['skus'=> $skus]
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($expectedData, $data);
    }
}
