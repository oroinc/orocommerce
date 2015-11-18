<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

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
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
            ]
        );
    }

    /**
     * @dataProvider productNamesBySkusDataProvider
     * @param array $skus
     * @param array $expectedData
     */
    public function testProductNamesBySkus(array $skus, array $expectedData)
    {
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

    public function productNamesBySkusDataProvider()
    {
        return [
            'restricted' => [
                'skus' => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => LoadProductData::PRODUCT_1],
                    LoadProductData::PRODUCT_2 => ['name' => LoadProductData::PRODUCT_2],
                    LoadProductData::PRODUCT_3 => ['name' => LoadProductData::PRODUCT_3],
                ],
            ],
            'allowed' => [
                'skus' => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => LoadProductData::PRODUCT_1],
                    LoadProductData::PRODUCT_2 => ['name' => LoadProductData::PRODUCT_2],
                ],
            ],
        ];
    }
}
