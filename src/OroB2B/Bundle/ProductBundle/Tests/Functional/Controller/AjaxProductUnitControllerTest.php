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
        $this->assertEquals(['bottle', 'box', 'item', 'kg', 'liter'], array_keys($data['units']));
    }

    /**
     * @param string $productReference
     * @param array $expectedData
     *
     * @dataProvider productUnitsDataProvider
     */
    public function testGetProductUnitsAction($productReference, array $expectedData)
    {
        $product = $this->getProduct($productReference);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_unit_product_units', ['id' => $product->getId()])
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
            [
                'product.1',
                ['bottle' => 'orob2b.product_unit.bottle.label.full', 'liter' => 'orob2b.product_unit.liter.label.full']
            ],
            [
                'product.2',
                [
                    'bottle' => 'orob2b.product_unit.bottle.label.full',
                    'box' => 'orob2b.product_unit.box.label.full',
                    'liter' => 'orob2b.product_unit.liter.label.full'
                ]
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
