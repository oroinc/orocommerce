<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

/**
 * @dbIsolationPerTest
 */
class ProductTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductUnitPrecisions::class, LoadCategoryProductData::class]);
    }

    public function testGetShouldReturnCategoryField()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->get(
            ['entity' => 'products', 'id' => $product->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id'   => '<toString(@category_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListShouldReturnCategoryField()
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
                            'category' => [
                                'data' => [
                                    'type' => 'categories',
                                    'id'   => '<toString(@category_1->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateShouldChangeProductCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$product->getId(),
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id'   => (string)$category->getId()
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
                    'id'            => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id'   => '<toString(@category_1_2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $updatedCategory = $this->getEntityManager()
            ->getRepository(Category::class)
            ->findOneByProductSku($product->getSku());
        self::assertEquals($category->getId(), $updatedCategory->getId());
    }

    public function testUpdateShouldSetProductCategoryToNull()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => (string)$product->getId(),
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );

        self::assertNull(
            $this->getEntityManager()->getRepository(Category::class)->findOneByProductSku($product->getSku())
        );
    }

    public function testCreateShouldSetCategoryForNewProduct()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'product_create.yml'
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id'   => '<toString(@category_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $product = $this->getEntityManager()->getReference(Product::class, $this->getResourceId($response));
        $category = $this->getEntityManager()->getRepository(Category::class)->findOneByProduct($product);
        self::assertEquals(
            $this->getReferenceRepository()->getReference('category_1')->getId(),
            $category->getId()
        );
    }

    public function testShouldDeleteProductFromCategoryWhenProductIsDeleted()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $categoryRepo = $this->getEntityManager()->getRepository(Category::class);

        // guard - the deleting product should be assigned to a category
        $this->assertInstanceOf(Category::class, $categoryRepo->findOneByProductSku($product->getSku()));

        $this->delete(['entity' => 'products', 'id' => $product->getId()]);

        $this->assertNull($categoryRepo->findOneByProductSku($product->getSku()));
    }

    public function testGetSubresourceForCategoryShouldReturnProductCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->getSubresource(
            ['entity' => 'products', 'id' => $product->getId(), 'association' => 'category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'categories',
                    'id'            => '<toString(@category_1->id)>',
                    'relationships' => [
                        'products' => [
                            'data' => [
                                ['type' => 'products', 'id' => (string)$product->getId()]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCategoryShouldReturnProductCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->getRelationship(
            ['entity' => 'products', 'id' => $product->getId(), 'association' => 'category']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'categories',
                    'id'   => '<toString(@category_1->id)>'
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForCategoryShouldChangeProductCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->patchRelationship(
            ['entity' => 'products', 'id' => (string)$product->getId(), 'association' => 'category'],
            [
                'data' => [
                    'type' => 'categories',
                    'id'   => '<toString(@category_1_2->id)>'
                ]
            ]
        );

        $updatedCategory = $this->getEntityManager()
            ->getRepository(Category::class)
            ->findOneByProductSku($product->getSku());
        self::assertEquals($category->getId(), $updatedCategory->getId());
    }

    public function testUpdateRelationshipForCategoryShouldSetProductCategoryToNull()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->patchRelationship(
            ['entity' => 'products', 'id' => (string)$product->getId(), 'association' => 'category'],
            [
                'data' => null
            ]
        );

        self::assertNull(
            $this->getEntityManager()->getRepository(Category::class)->findOneByProductSku($product->getSku())
        );
    }
}
