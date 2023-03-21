<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductUnitPrecisions::class, LoadProductShippingOptions::class]);
    }

    private function getProductId(string $reference): int
    {
        return $this->getReference($reference)->getId();
    }

    private function getShippingOptionId(string $reference): int
    {
        return $this->getReference($reference)->getId();
    }

    /**
     * @param int $productId
     *
     * @return ProductShippingOptions[]
     */
    private function findShippingOptionsByProduct(int $productId): array
    {
        return $this->getEntityManager()
            ->getRepository(ProductShippingOptions::class)
            ->findBy(['product' => $productId]);
    }

    private function findShippingOption(int $id): ?ProductShippingOptions
    {
        return $this->getEntityManager()
            ->getRepository(ProductShippingOptions::class)
            ->find(ProductShippingOptions::class, $id);
    }

    public function testGetShouldReturnShippingOptions(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product-1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product-1->id)>',
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                [
                                    'type' => 'productshippingoptions',
                                    'id'   => '<toString(@product_shipping_options.1->id)>'
                                ],
                                [
                                    'type' => 'productshippingoptions',
                                    'id'   => '<toString(@product_shipping_options.2->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListShouldReturnShippingOptions(): void
    {
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['sku' => '@product-1->sku']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'products',
                        'id'            => '<toString(@product-1->id)>',
                        'relationships' => [
                            'productShippingOptions' => [
                                'data' => [
                                    [
                                        'type' => 'productshippingoptions',
                                        'id'   => '<toString(@product_shipping_options.1->id)>'
                                    ],
                                    [
                                        'type' => 'productshippingoptions',
                                        'id'   => '<toString(@product_shipping_options.2->id)>'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateShouldChangeProductShippingOptions(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_2);
        $shippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$productId],
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $updatedShippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $updatedShippingOptions);
        self::assertEquals($shippingOptionId, $updatedShippingOptions[0]->getId());
    }

    public function testUpdateShouldChangeProductShippingOptionsAndDeleteUnused(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);
        $shippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);
        $unusedShippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$productId],
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $updatedShippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $updatedShippingOptions);
        self::assertEquals($shippingOptionId, $updatedShippingOptions[0]->getId());

        self::assertNull($this->findShippingOption($unusedShippingOptionId));
    }

    public function testUpdateShouldSetProductShippingOptionsToEmptyAndDeleteUnused(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);
        $shippingOption1Id = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);
        $shippingOption2Id = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$productId],
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => []
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );

        self::assertCount(0, $this->findShippingOptionsByProduct($productId));

        self::assertNull($this->findShippingOption($shippingOption1Id));
        self::assertNull($this->findShippingOption($shippingOption2Id));
    }

    public function testCreateShouldSetShippingOptionsForNewProduct(): void
    {
        $response = $this->post(
            ['entity' => 'products'],
            'product/product_create.yml'
        );

        $productId = (int)$this->getResourceId($response);
        $shippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $shippingOptions);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$productId,
                    'relationships' => [
                        'productShippingOptions' => [
                            'data' => [
                                ['type' => 'productshippingoptions', 'id' => (string)$shippingOptions[0]->getId()]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDeleteShouldDeleteProductShippingOptions(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);
        $shippingOption1Id = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);
        $shippingOption2Id = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        // guard - the deleting product should have shipping options
        $this->assertCount(2, $this->findShippingOptionsByProduct($productId));

        $this->delete(['entity' => 'products', 'id' => $productId]);

        $this->assertCount(0, $this->findShippingOptionsByProduct($productId));

        self::assertNull($this->findShippingOption($shippingOption1Id));
        self::assertNull($this->findShippingOption($shippingOption2Id));
    }

    public function testGetSubresourceForShippingOptionsShouldReturnProductShippingOptions(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);

        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => $productId, 'association' => 'productShippingOptions']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'productshippingoptions',
                        'id'            => '<toString(@product_shipping_options.1->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => (string)$productId]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'productshippingoptions',
                        'id'            => '<toString(@product_shipping_options.2->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => 'products', 'id' => (string)$productId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForShippingOptionsShouldReturnProductShippingOptions(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => '<toString(@product-1->id)>', 'association' => 'productShippingOptions']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productshippingoptions', 'id' => '<toString(@product_shipping_options.1->id)>'],
                    ['type' => 'productshippingoptions', 'id' => '<toString(@product_shipping_options.2->id)>']
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForShippingOptionsShouldChangeProductShippingOptions(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_2);
        $shippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->patchRelationship(
            ['entity' => 'products', 'id' => (string)$productId, 'association' => 'productShippingOptions'],
            [
                'data' => [
                    ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                ]
            ]
        );

        $updatedShippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $updatedShippingOptions);
        self::assertEquals($shippingOptionId, $updatedShippingOptions[0]->getId());
    }

    public function testUpdateRelationshipForShippingOptionsShouldChangeProductShippingOptionsAndDeleteUnused(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);
        $shippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);
        $unusedShippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2);

        $this->patchRelationship(
            ['entity' => 'products', 'id' => (string)$productId, 'association' => 'productShippingOptions'],
            [
                'data' => [
                    ['type' => 'productshippingoptions', 'id' => (string)$shippingOptionId]
                ]
            ]
        );

        $updatedShippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $updatedShippingOptions);
        self::assertEquals($shippingOptionId, $updatedShippingOptions[0]->getId());

        self::assertNull($this->findShippingOption($unusedShippingOptionId));
    }

    public function testDeleteRelationshipForShippingOptionsShouldChangeProductShippingOptionsAndDeleteUnused(): void
    {
        $productId = $this->getProductId(LoadProductData::PRODUCT_1);
        $shippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2);
        $unusedShippingOptionId = $this->getShippingOptionId(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->deleteRelationship(
            ['entity' => 'products', 'id' => (string)$productId, 'association' => 'productShippingOptions'],
            [
                'data' => [
                    ['type' => 'productshippingoptions', 'id' => (string)$unusedShippingOptionId]
                ]
            ]
        );

        $updatedShippingOptions = $this->findShippingOptionsByProduct($productId);
        self::assertCount(1, $updatedShippingOptions);
        self::assertEquals($shippingOptionId, $updatedShippingOptions[0]->getId());

        self::assertNull($this->findShippingOption($unusedShippingOptionId));
    }
}
