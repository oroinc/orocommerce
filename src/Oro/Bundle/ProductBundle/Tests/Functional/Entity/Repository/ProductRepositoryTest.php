<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductData::class, LoadOrganization::class]);
    }

    private function getRepository(): ProductRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Product::class);
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }

    private function referencesToEntities(array $references): array
    {
        return array_map(function ($reference) {
            return $this->getReference($reference);
        }, $references);
    }

    public function testFindOneBySku()
    {
        $this->assertNull($this->getRepository()->findOneBySku(uniqid('_fake_sku_', true)));

        $product = $this->getProduct(LoadProductData::PRODUCT_9);
        $expectedProduct = $this->getRepository()->findOneBySku(ucfirst(LoadProductData::PRODUCT_9));

        $this->assertEquals($product->getSku(), $expectedProduct->getSku());
    }

    /**
     * @dataProvider getSearchQueryBuilderDataProvider
     */
    public function testGetSearchQueryBuilder(string $search, int $firstResult, int $maxResult, array $expected)
    {
        $queryBuilder = $this->getRepository()->getSearchQueryBuilder($search, $firstResult, $maxResult);
        $result = array_map(
            function ($product) {
                return $product['sku'];
            },
            $queryBuilder->getQuery()->getArrayResult()
        );

        $this->assertEquals($expected, $result);
    }

    public function getSearchQueryBuilderDataProvider(): array
    {
        return [
            'product, 0, 10' => [
                'search' => 'duct',
                'firstResult' => 0,
                'maxResult' => 10,
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                ],
            ],
            'product, 1, 1' => [
                'search' => 'oduct',
                'firstResult' => 1,
                'maxResult' => 1,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'product, 0, 2' => [
                'search' => 'product',
                'firstResult' => 0,
                'maxResult' => 2,
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider patternsAndSkuListProvider
     */
    public function testFindAllSkuByPattern(string $pattern, array $expectedSkuList)
    {
        $result = $this->getRepository()->getAllSkuByPatternQueryBuilder($pattern)->getQuery()->getResult();
        $actualSkuList = [];
        foreach ($result as $item) {
            $actualSkuList[] = $item['sku'];
        }

        $this->assertCount(count($expectedSkuList), $actualSkuList);
        foreach ($expectedSkuList as $expectedSku) {
            $this->assertContains($expectedSku, $actualSkuList);
        }
    }

    public function patternsAndSkuListProvider(): array
    {
        $products = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_8,
        ];

        return [
            'exact search 1' => [LoadProductData::PRODUCT_1, [LoadProductData::PRODUCT_1]],
            'exact search 2' => [LoadProductData::PRODUCT_3, [LoadProductData::PRODUCT_3]],
            'not found' => [uniqid('_fake_', true), []],
            'mask all products 1' => ['product-%', $products],
            'mask all products 2' => ['pro%', $products],
            'product suffixed with 1' => ['%-1', [LoadProductData::PRODUCT_1]],
            'product suffixed with 3' => ['%3', [LoadProductData::PRODUCT_3]],
        ];
    }

    public function testGetProductsQueryBuilder()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => LoadProductData::PRODUCT_1]);
        $builder = $this->getRepository()->getProductsQueryBuilder([$product->getId()]);
        $result = $builder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertEquals($product, $result[0]);
    }

    public function testGetProductsIdsBySku()
    {
        $product7 = $this->getProduct(LoadProductData::PRODUCT_7);
        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);

        $result = $this->getRepository()->getProductsIdsBySkuQueryBuilder(
            [
                $product3->getSku(),
                mb_strtoupper($product7->getSku()),
                mb_strtolower($product2->getSku()),
            ]
        )->getQuery()->getArrayResult();

        $this->assertEquals(
            [
                ['id' => $product2->getId(), 'sku' => $product2->getSku()],
                ['id' => $product3->getId(), 'sku' => $product3->getSku()],
                ['id' => $product7->getId(), 'sku' => $product7->getSku()],
            ],
            $result
        );
    }

    public function testGetProductIdBySkuQueryBuilder(): void
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_2);

        $result = $this->getRepository()->getProductIdBySkuQueryBuilder($product->getSku())
            ->getQuery()
            ->getArrayResult();

        $this->assertEquals(
            [
                ['id' => $product->getId()],
            ],
            $result
        );
    }

    public function getProductsNamesBySkuDataProvider(): array
    {
        return [
            [
                'skus' => [
                    LoadProductData::PRODUCT_1,
                    mb_strtoupper(LoadProductData::PRODUCT_7),
                    mb_strtolower(LoadProductData::PRODUCT_3),
                    'not a sku',
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                'skus' => [
                    'not a sku',
                ],
                'expectedData' => [],
            ]
        ];
    }

    /**
     * @dataProvider getListingImagesFilesByProductIdsDataProvider
     */
    public function testGetListingImagesFilesByProductIds(array $products, array $expectedImages)
    {
        $result = $this->getRepository()->getListingImagesFilesByProductIds($this->referencesToEntities($products));

        $this->assertCount(count($expectedImages), $result);

        foreach ($this->referencesToEntities($expectedImages) as $image) {
            $this->assertContains($image, $result);
        }
    }

    public function getListingImagesFilesByProductIdsDataProvider(): array
    {
        return [
            [
                'products' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
                'expectedImages' => [
                    'img.product-1',
                    'img.product-8',
                ],
            ],
            [
                'products' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
                'expectedImages' => [
                    'img.product-1',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getListingAndMainImagesFilesByProductIdsDataProvider
     */
    public function testGetListingAndMainImagesFilesByProductIds(array $products, array $expectedImages): void
    {
        $result = $this->getRepository()->getListingAndMainImagesFilesByProductIds(
            $this->referencesToEntities($products)
        );

        $this->assertCount(count($expectedImages), $result);

        foreach ($expectedImages as $images) {
            $this->assertContains($this->referencesToEntities($images), $result);
        }
    }

    public function getListingAndMainImagesFilesByProductIdsDataProvider(): array
    {
        return [
            [
                'products' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
                'expectedImages' => [
                    [
                        ProductImageType::TYPE_LISTING => 'img.product-1',
                    ],
                    [
                        ProductImageType::TYPE_MAIN => 'img.product-2',
                    ],
                    [
                        ProductImageType::TYPE_MAIN => 'img.product-3',
                    ],
                    [
                        ProductImageType::TYPE_LISTING => 'img.product-8',
                    ],
                ],
            ],
            [
                'products' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
                'expectedImages' => [
                    [
                        ProductImageType::TYPE_LISTING => 'img.product-1',
                    ],
                    [
                        ProductImageType::TYPE_MAIN => 'img.product-2',
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider getImagesFilesByProductIdDataProvider
     */
    public function testImagesFilesByProductId(string $productReference, array $expectedImages)
    {
        $result = $this->getRepository()->getImagesFilesByProductId($this->getProduct($productReference)->getId());

        $this->assertEquals($this->referencesToEntities($expectedImages), array_values($result));
    }

    public function getImagesFilesByProductIdDataProvider(): array
    {
        return [
            [
                'productId' => LoadProductData::PRODUCT_1,
                'expectedImages' => [
                    'img.product-1',
                ],
            ],
            [
                'productId' => LoadProductData::PRODUCT_2,
                'expectedImages' => [
                    'img.product-2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findByCaseInsensitiveDataProvider
     */
    public function testFindByCaseInsensitive(array $criteria, array $expectedSkus)
    {
        $actualProducts = $this->getRepository()->findByCaseInsensitive($criteria);

        $actualSkus = [];
        foreach ($actualProducts as $product) {
            $actualSkus[] = $product->getSku();
        }

        $this->assertCount(count($expectedSkus), $actualSkus);
        foreach ($expectedSkus as $expectedSku) {
            $this->assertContains($expectedSku, $actualSkus);
        }
    }

    public function testFindByCaseInsensitiveWithObject()
    {
        $criteria = ['organization' => $this->getReference(LoadOrganization::ORGANIZATION)];
        $expectedSkus = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_7,
            LoadProductData::PRODUCT_8,
            LoadProductData::PRODUCT_9,
        ];

        $actualProducts = $this->getRepository()->findByCaseInsensitive($criteria);

        $actualSkus = [];
        foreach ($actualProducts as $product) {
            $actualSkus[] = $product->getSku();
        }

        $this->assertCount(count($expectedSkus), $actualSkus);
        foreach ($expectedSkus as $expectedSku) {
            $this->assertContains($expectedSku, $actualSkus);
        }
    }

    public function findByCaseInsensitiveDataProvider(): array
    {
        return [
            'regular sku' => [
                'criteria' => ['sku' => LoadProductData::PRODUCT_1],
                'expectedSkus' => [LoadProductData::PRODUCT_1]
            ],
            'upper sku' => [
                'criteria' => ['sku' => mb_strtoupper(LoadProductData::PRODUCT_7)],
                'expectedSkus' => [LoadProductData::PRODUCT_7]
            ],
            'lower sku' => [
                'criteria' => ['sku' => mb_strtolower(LoadProductData::PRODUCT_3)],
                'expectedSkus' => [LoadProductData::PRODUCT_3]
            ],
            'undefined sku' => [
                'criteria' => ['sku' => 'UndefinedSku'],
                'expectedSkus' => []
            ],
            'insensitive type' => [
                'criteria' => ['type' => 'SiMpLe'],
                'expectedSkus' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                ]
            ],
        ];
    }

    public function testGetFeaturedProductsQueryBuilder()
    {
        $queryBuilder = $this->getRepository()->getFeaturedProductsQueryBuilder(2);
        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
        $this->assertInstanceOf(Product::class, $result[1]);
    }

    public function testGetProductIdsByAttributeFamilies()
    {
        $product5 = $this->getProduct(LoadProductData::PRODUCT_5);
        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);

        $this->assertEquals(
            [
                $product5->getId(),
                $product9->getId(),
            ],
            $this->getRepository()->getProductIdsByAttributeFamilies(
                [$this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1)]
            )
        );
    }

    public function testGetSimpleProductIdsByParentProductsQueryBuilder()
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);
        $product8 = $this->getProduct(LoadProductData::PRODUCT_8);
        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);

        $this->prepareConfigurableVariants();

        $qb = $this->getRepository()->getSimpleProductIdsByParentProductsQueryBuilder(
            [$product8->getId(), $product9->getId()]
        );
        $qb->orderBy('p.id');
        $result = $qb->getQuery()->getArrayResult();

        $this->assertEquals(
            [
                ['id' => $product1->getId()],
                ['id' => $product2->getId()],
                ['id' => $product3->getId()]
            ],
            $result
        );
    }

    public function testGetVariantsMapping()
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);
        $product8 = $this->getProduct(LoadProductData::PRODUCT_8);
        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);

        $this->prepareConfigurableVariants();

        $expected = [];
        $expected[$product1->getId()] = [$product8->getId()];
        $expected[$product2->getId()] = [$product8->getId()];
        $expected[$product3->getId()] = [$product9->getId()];
        $this->assertEquals(
            $expected,
            $this->getRepository()->getVariantsMapping([$product8->getId(), $product9->getId()])
        );
    }

    public function testGetParentProductsForSimpleProduct()
    {
        $this->prepareConfigurableVariants();

        $product8 = $this->getProduct(LoadProductData::PRODUCT_8);
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);

        $parentProducts = $this->getRepository()->getParentProductsForSimpleProduct($product1);
        $this->assertNotEmpty($parentProducts);
        $this->assertCount(1, $parentProducts);
        $this->assertEquals($product8->getId(), $parentProducts[0]->getId());
    }

    public function testGetSimpleProductsForConfigurableProduct()
    {
        $this->prepareConfigurableVariants();

        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);
        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);

        $simpleProducts = $this->getRepository()->getSimpleProductsForConfigurableProduct($product9);
        $this->assertNotEmpty($simpleProducts);
        $this->assertCount(1, $simpleProducts);
        $this->assertEquals($product3->getId(), $simpleProducts[0]->getId());
    }

    public function testGetRequiredAttributesForSimpleProduct()
    {
        $this->prepareConfigurableVariants();

        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $variantFields = ['field1', 'field2'];
        $product9->setVariantFields($variantFields);
        $em->flush();

        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);

        $attributes = $this->getRepository()->getRequiredAttributesForSimpleProduct($product3);
        $this->assertEquals(
            [['id' => $product9->getId(), 'sku' => $product9->getSku(), 'variantFields' => $variantFields]],
            $attributes
        );
    }

    private function prepareConfigurableVariants()
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);
        $product8 = $this->getProduct(LoadProductData::PRODUCT_8);
        $product9 = $this->getProduct(LoadProductData::PRODUCT_9);

        /** @var ManagerRegistry $registry */
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManagerForClass(Product::class);

        $variantLink81 = new ProductVariantLink();
        $variantLink81->setParentProduct($product8);
        $variantLink81->setProduct($product1);
        $product8->addVariantLink($variantLink81);

        $variantLink82 = new ProductVariantLink();
        $variantLink82->setParentProduct($product8);
        $variantLink82->setProduct($product2);
        $product8->addVariantLink($variantLink82);

        $variantLink93 = new ProductVariantLink();
        $variantLink93->setParentProduct($product9);
        $variantLink93->setProduct($product3);
        $product9->addVariantLink($variantLink93);

        $em->flush();
    }
}
