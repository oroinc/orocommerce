<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    private QueryBuilder $queryBuilder;
    private ProductVisibilityQueryBuilderModifier $modifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductData::class]);

        $this->queryBuilder = self::getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id');

        $this->modifier = new ProductVisibilityQueryBuilderModifier();
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
                    'prod_inventory_status.in_stock',
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
                    'prod_inventory_status.out_of_stock',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            'products discontinued' => [
                'statuses' => [
                    'prod_inventory_status.discontinued',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_4,
                ],
            ],
            'all products' => [
                'statuses' => [
                    'prod_inventory_status.in_stock',
                    'prod_inventory_status.out_of_stock',
                    'prod_inventory_status.discontinued',
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
                'prod_inventory_status.out_of_stock',
            ]
        );

        $this->modifier->modifyByStatus($this->queryBuilder, [Product::STATUS_ENABLED, Product::STATUS_DISABLED]);
        $this->modifier->modifyByInventoryStatus(
            $this->queryBuilder,
            [
                'prod_inventory_status.in_stock',
                'prod_inventory_status.out_of_stock',
                'prod_inventory_status.discontinued',
            ]
        );

        $this->assertCount(4, $this->queryBuilder->getParameters());
        $this->assertEquals(LoadProductData::PRODUCT_3, $this->queryBuilder->getQuery()->getSingleScalarResult());
    }
}
