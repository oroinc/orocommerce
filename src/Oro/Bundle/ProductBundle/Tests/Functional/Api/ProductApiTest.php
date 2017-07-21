<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

class ProductApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadProductUnits::class,
            LoadProductUnitPrecisions::class,
            LoadBusinessUnitData::class,
            LoadOrganizations::class,
            LoadProductTaxCodes::class,
            LoadAttributeFamilyData::class,
            LoadCategoryData::class,
        ]);
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
            ['entity' => 'products', 'id' => (string)$product->getId()],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string)$product->getId(),
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

    public function testProductPageTemplateValue()
    {
        $response = $this->post(
            ['entity' => $this->getEntityType(Product::class)],
            __DIR__ . '/requests/create_product.yml'
        );

        /** @var Product $product */
        $product = $this->getEntityManager()->getRepository(Product::class)->findOneBy(['sku' => 'sku-test-api-1']);

        var_dump($product->getPageTemplate()->getOwnValue()); die;
        $this->assertTrue(true);
//        $this->assertResponseContains(__DIR__ . '/responses/create_customer.yml', $response, $customer);
    }
}
