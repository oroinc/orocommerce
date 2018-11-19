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
    protected function setUp()
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
                    'type' => 'products',
                    'id' => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id' => '<toString(@category_1->id)>'
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
                        'type' => 'products',
                        'id' => '<toString(@product-1->id)>',
                        'relationships' => [
                            'category' => [
                                'data' => [
                                    'type' => 'categories',
                                    'id' => '<toString(@category_1->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testShouldChangeProductCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        /**
         * It's a workaround for doctrine2 bug
         * @see https://github.com/doctrine/doctrine2/issues/6186
         * remove this in https://magecore.atlassian.net/browse/BB-11411
         */
        $this->getEntityManager()->clear();

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string)$product->getId(),
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id' => (string)$category->getId(),
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id' => '<toString(@category_1_2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $this->getEntityManager()->clear();
        $updatedCategory = $this->getEntityManager()
            ->getRepository(Category::class)
            ->findOneByProductSku($product->getSku());
        self::assertEquals($category->getId(), $updatedCategory->getId());
    }

    public function testShouldSetProductCategoryToNull()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->patch(
            ['entity' => 'products', 'id' => (string)$product->getId()],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string)$product->getId(),
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ],
                    ],
                ],
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product-1->id)>',
                    'relationships' => [
                        'category' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );

        $this->getEntityManager()->clear();
        self::assertNull(
            $this->getEntityManager()->getRepository(Category::class)->findOneByProductSku($product->getSku())
        );
    }

    public function testShouldSetCategoryForNewProduct()
    {
        $response = $this->post(
            ['entity' => 'products'],
            'product_create.yml'
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'relationships' => [
                        'category' => [
                            'data' => [
                                'type' => 'categories',
                                'id' => '<toString(@category_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $this->getEntityManager()->clear();
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

        $this->getEntityManager()->clear();
        $this->assertNull($categoryRepo->findOneByProductSku($product->getSku()));
    }
}
