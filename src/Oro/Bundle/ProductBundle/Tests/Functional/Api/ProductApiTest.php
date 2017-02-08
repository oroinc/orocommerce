<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class ProductApiTest extends RestJsonApiTestCase
{
    const ARRAY_DELIMITER = ',';

    /**
     * @var array
     */
    protected $expectations;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(['@OroInventoryBundle/Tests/Functional/DataFixtures/inventory_level.yml']);
    }

    /**
     * @param array $parameters
     * @param string $expectedContentFile filename with expected json response data
     *
     * @dataProvider cgetParamsAndExpectation
     */
    public function testCgetEntity(array $parameters, $expectedContentFile)
    {
        $entityType = $this->getEntityType(Product::class);
        $response = $this->get('oro_rest_api_cget', ['entity' => $entityType], $parameters);
        $this->assertResponseContains($expectedContentFile, $response);
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'filter' => [
                        'sku' => '@product-1->sku',
                    ],
                ],
                'expectedContent' => '@OroProductBundle/Tests/Functional/Api/responses/cget_filter_by_product.yml',
            ],
            'filter by Products with different inventory status' => [
                'parameters' => [
                    'filter' => [
                        'sku' => ['@product-2->sku', '@product-3->sku'],
                    ],
                ],
                'expectedContent'
                => '@OroProductBundle/Tests/Functional/Api/responses/cget_filter_by_products_by_inventory_status.yml',
            ],
        ];
    }

    public function testUpdateEntity()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertEquals('in_stock', $product->getInventoryStatus()->getId());

        $entityType = $this->getEntityType(Product::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => LoadProductData::PRODUCT_1,
                'relationships' => [
                    'inventory_status' => [
                        'data' => [
                            'type' => 'prodinventorystatuses',
                            'id' => 'out_of_stock',
                        ],
                    ],
                ],
            ]
        ];
        $response = $this->patch(
            'oro_rest_api_patch',
            ['entity' => $entityType, 'id' => LoadProductData::PRODUCT_1],
            $data
        );

        $doctrineHelper = $this->getContainer()->get('oro_api.doctrine_helper');
        /** @var Product $product */
        $product = $doctrineHelper->getEntity(Product::class, $product->getId());
        $this->getReferenceRepository()->setReference(LoadProductData::PRODUCT_1, $product);

        $this->assertResponseContains(
            '@OroProductBundle/Tests/Functional/Api/responses/patch_update_entity.yml',
            $response
        );
    }
}
