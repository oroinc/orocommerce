<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadRelatedProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RelatedProductRepositoryTest extends WebTestCase
{
    private RelatedProductRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadRelatedProductData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(RelatedProduct::class);
    }

    private function getProductBySku(string $sku): Product
    {
        return self::getContainer()->get('doctrine')->getRepository(Product::class)->findOneBy(['sku' => $sku]);
    }

    public function testExistsReturnTrue()
    {
        $product3 = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $product1 = $this->getProductBySku(LoadProductData::PRODUCT_1);

        $this->assertTrue($this->repository->exists($product3, $product1));
    }

    public function testExistsReturnFalse()
    {
        $product3 = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $product6 = $this->getProductBySku(LoadProductData::PRODUCT_6);

        $this->assertFalse($this->repository->exists($product3, $product6));
    }

    /**
     * @dataProvider countRelationsForProductDataProvider
     */
    public function testCountRelationsForProduct(string $productSku, int $numberOfRelations)
    {
        $product = $this->getProductBySku($productSku);
        $this->assertSame($numberOfRelations, $this->repository->countRelationsForProduct($product->getId()));
    }

    public function testFindAllRelatedUnidirectional()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1),
            $this->getProductBySku(LoadProductData::PRODUCT_2),
        ];
        $relatedProducts = $this->repository->findRelated($product->getId(), false, 10);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedUnidirectionalWithLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)
        ];
        $relatedProducts = $this->repository->findRelated($product->getId(), false, 1);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedUnidirectionalWithoutLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1),
            $this->getProductBySku(LoadProductData::PRODUCT_2),
        ];
        $relatedProducts = $this->repository->findRelated($product->getId(), false);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedBidirectional()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1),
            $this->getProductBySku(LoadProductData::PRODUCT_2),
            $this->getProductBySku(LoadProductData::PRODUCT_4),
        ];
        $relatedProducts = $this->repository->findRelated($product->getId(), true, 10);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedBidirectionalWithLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_4);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_3),
            $this->getProductBySku(LoadProductData::PRODUCT_5),
        ];
        $relatedProducts = $this->repository->findRelated($product->getId(), true, 2);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindAllRelatedIdsUnidirectional()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId(),
            $this->getProductBySku(LoadProductData::PRODUCT_2)->getId(),
        ];
        $relatedProducts = $this->repository->findRelatedIds($product->getId(), false, 10);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedIdsUnidirectionalWithLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId()
        ];
        $relatedProducts = $this->repository->findRelatedIds($product->getId(), false, 1);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedIdsUnidirectionalWithoutLimit()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId(),
            $this->getProductBySku(LoadProductData::PRODUCT_2)->getId(),
        ];
        $relatedProducts = $this->repository->findRelatedIds($product->getId(), false);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function testFindRelatedIdsBidirectional()
    {
        $product = $this->getProductBySku(LoadProductData::PRODUCT_3);
        $expectedRelatedProducts = [
            $this->getProductBySku(LoadProductData::PRODUCT_1)->getId(),
            $this->getProductBySku(LoadProductData::PRODUCT_2)->getId(),
            $this->getProductBySku(LoadProductData::PRODUCT_4)->getId(),
        ];
        $relatedProducts = $this->repository->findRelatedIds($product->getId(), true, 10);

        $this->assertEquals($expectedRelatedProducts, $relatedProducts);
    }

    public function countRelationsForProductDataProvider(): array
    {
        return [
            ['product-1', 0],
            ['product-3', 2],
            ['product-4', 2],
        ];
    }

    /**
     * @dataProvider getUniqueProductDataProvider
     */
    public function testGetUniqueProductDataQueryBuilder(bool $isBidirectional, array $expected): void
    {
        $qb = $this->repository->getUniqueProductDataQueryBuilder($isBidirectional);

        $actual = [];
        foreach ($qb->getQuery()->getResult() as $item) {
            $actual[] = $item;
        }

        foreach ($expected as &$item) {
            $item['id'] = $this->getReference($item['id'])->getId();
        }
        unset($item);

        $this->assertEquals($expected, $actual);
    }

    public function getUniqueProductDataProvider(): array
    {
        return [
            [
                'isBidirectional' => false,
                'expected' => [
                    [
                        'id' => LoadProductData::PRODUCT_3,
                        'sku' => LoadProductData::PRODUCT_3,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_4,
                        'sku' => LoadProductData::PRODUCT_4,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_5,
                        'sku' => LoadProductData::PRODUCT_5,
                    ],
                ],
            ],
            [
                'isBidirectional' => true,
                'expected' => [
                    [
                        'id' => LoadProductData::PRODUCT_1,
                        'sku' => LoadProductData::PRODUCT_1,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_2,
                        'sku' => LoadProductData::PRODUCT_2,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_3,
                        'sku' => LoadProductData::PRODUCT_3,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_4,
                        'sku' => LoadProductData::PRODUCT_4,
                    ],
                    [
                        'id' => LoadProductData::PRODUCT_5,
                        'sku' => LoadProductData::PRODUCT_5,
                    ],
                ],
            ]
        ];
    }
}
