<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    private ProductVisibilityQueryBuilderModifier $modifier;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductData::class]);

        $this->modifier = new ProductVisibilityQueryBuilderModifier();

        $this->queryBuilder = $this->getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id');
    }

    /**
     * @dataProvider modifyByStatusDataProvider
     */
    public function testModifyByStatus(array $statuses, array $expected)
    {
        $this->modifier->modifyByStatus($this->queryBuilder, $statuses);

        $this->assertEquals($expected, $this->getProductSkus($this->queryBuilder->getQuery()->getArrayResult()));
    }

    public function modifyByStatusDataProvider(): array
    {
        return [
            'enabled products' => [
                'statuses' => [
                    'enabled',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_9,
                ],
            ],
            'disabled products' => [
                'statuses' => [
                    'disabled',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_5,
                ],
            ],
            'all products' => [
                'statuses' => [
                    'enabled',
                    'disabled',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_9,
                ],
            ],
        ];
    }

    /**
     * @dataProvider modifyByInventoryStatusDataProvider
     */
    public function testModifyByInventoryStatus(array $statuses, array $expected)
    {
        $this->modifier->modifyByInventoryStatus($this->queryBuilder, $statuses);

        $this->assertEquals($expected, $this->getProductSkus($this->queryBuilder->getQuery()->getArrayResult()));
    }

    public function modifyByInventoryStatusDataProvider(): array
    {
        return [
            'products in_stock' => [
                'statuses' => [
                    'in_stock',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_9,
                ],
            ],
            'products out_of_stock' => [
                'statuses' => [
                    'out_of_stock',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            'products discontinued' => [
                'statuses' => [
                    'discontinued',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_4,
                ],
            ],
            'all products' => [
                'statuses' => [
                    'in_stock',
                    'out_of_stock',
                    'discontinued',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_5,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_9,
                ],
            ],
        ];
    }

    private function getProductSkus(array $products): array
    {
        return array_map(function ($product) {
            return $product['sku'];
        }, $products);
    }

    public function testModifyByStatusesParameterNamesConflict()
    {
        $this->queryBuilder->select('p.sku');
        $this->modifier->modifyByStatus($this->queryBuilder, [Product::STATUS_ENABLED]);
        $this->modifier->modifyByInventoryStatus(
            $this->queryBuilder,
            [
                Product::INVENTORY_STATUS_OUT_OF_STOCK,
            ]
        );

        $this->modifier->modifyByStatus($this->queryBuilder, [Product::STATUS_ENABLED, Product::STATUS_DISABLED]);
        $this->modifier->modifyByInventoryStatus(
            $this->queryBuilder,
            [
                Product::INVENTORY_STATUS_IN_STOCK,
                Product::INVENTORY_STATUS_OUT_OF_STOCK,
                Product::INVENTORY_STATUS_DISCONTINUED,
            ]
        );

        $this->assertCount(4, $this->queryBuilder->getParameters());
        $this->assertEquals(LoadProductData::PRODUCT_3, $this->queryBuilder->getQuery()->getSingleScalarResult());
    }
}
