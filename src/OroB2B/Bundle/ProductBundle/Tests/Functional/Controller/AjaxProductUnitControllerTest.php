<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class AjaxProductUnitControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);
    }

    public function testGetAllProductUnitsAction()
    {
        $this->client->request('GET', $this->getUrl('orob2b_product_unit_all_product_units'));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('units', $data);
        $this->assertArrayHasKey('bottle', $data['units']);
        $this->assertArrayHasKey('box', $data['units']);
        $this->assertArrayHasKey('liter', $data['units']);
    }

    /**
     * @param string $productReference
     * @param array $expectedData
     * @param bool $isShort
     *
     * @dataProvider productUnitsDataProvider
     */
    public function testGetProductUnitsAction($productReference, array $expectedData, $isShort = false)
    {
        $product = $this->getProduct($productReference);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_unit_product_units', ['id' => $product->getId(), 'short' => $isShort])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('units', $data);
        $this->assertEquals($expectedData, $data['units']);
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
    {
        return [
            'product.1' => [
                'product.1',
                [
                    'bottle' => 'bottle',
                    'liter' => 'liter',
                    'milliliter' => 'milliliter',
                ],
                false
            ],
            'product.2' => [
                'product.2',
                [
                    'bottle' => 'bottle',
                    'box' => 'box',
                    'liter' => 'liter',
                    'milliliter' => 'milliliter',
                ],
                false
            ],
            'product.1 short label' => [
                'product.1',
                [
                    'bottle' => 'bottle',
                    'liter' => 'liter',
                    'milliliter' => 'milliliter',
                ],
                true
            ],
            'product.2 short label' => [
                'product.2',
                [
                    'bottle' => 'bottle',
                    'box' => 'box',
                    'liter' => 'liter',
                    'milliliter' => 'milliliter',
                ],
                true
            ]
        ];
    }

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     * @return ProductUnit
     */
    protected function getProductUnit($reference)
    {
        return $this->getReference($reference);
    }
}
