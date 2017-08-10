<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class ProductApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadProductUnitPrecisions::class]);
    }

    /**
     * @param array $parameters
     * @param string $expectedDataFileName
     *
     * @dataProvider getListDataProvider
     */
    public function testGetList(array $parameters, $expectedDataFileName)
    {
        $response = $this->cget(['entity' => 'products'], $parameters);

        $this->assertResponseContains($expectedDataFileName, $response);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'filter' => [
                        'sku' => '@product-1->sku',
                    ],
                ],
                'expectedDataFileName' => 'cget_filter_by_product.yml',
            ],
            'filter by Products with different inventory status' => [
                'parameters' => [
                    'filter' => [
                        'sku' => ['@product-2->sku', '@product-3->sku'],
                    ],
                ],
                'expectedDataFileName' => 'cget_filter_by_products_by_inventory_status.yml',
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string) $product->getId()],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string) $product->getId(),
                    'relationships' => [
                        'inventory_status' => [
                            'data' => [
                                'type' => 'prodinventorystatuses',
                                'id' => 'out_of_stock',
                            ],
                        ],
                    ],
                ]
            ]
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->find(Product::class, $product->getId());
        $this->getReferenceRepository()->setReference(LoadProductData::PRODUCT_1, $product);

        $this->assertResponseContains('patch_update_entity.yml', $response);
    }

    public function testDeleteAction()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->delete(
            ['entity' => 'products', 'id' => (string) $product->getId()]);

        $this->assertNull(
            $this->getEntityManager()->find(Product::class,$product->getId())
        );
    }
}
