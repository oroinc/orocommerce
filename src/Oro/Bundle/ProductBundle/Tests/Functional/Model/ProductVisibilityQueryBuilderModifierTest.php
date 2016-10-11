<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Model;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductVisibilityQueryBuilderModifierTest extends WebTestCase
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);

        $this->modifier = new ProductVisibilityQueryBuilderModifier();

        $this->queryBuilder = $this->getContainer()->get('doctrine')
            ->getRepository('OroProductBundle:Product')->createQueryBuilder('p')->orderBy('p.id');
    }

    /**
     * @dataProvider modifyByStatusDataProvider
     * @param array $statuses
     * @param array $expected
     */
    public function testModifyByStatus(array $statuses, array $expected)
    {
        $this->modifier->modifyByStatus($this->queryBuilder, $statuses);

        $this->assertEquals($expected, $this->getProductSkus($this->queryBuilder->getQuery()->getArrayResult()));
    }

    /**
     * @return array
     */
    public function modifyByStatusDataProvider()
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
                ],
            ],
        ];
    }

    /**
     * @dataProvider modifyByInventoryStatusDataProvider
     * @param array $statuses
     * @param array $expected
     */
    public function testModifyByInventoryStatus(array $statuses, array $expected)
    {
        $this->modifier->modifyByInventoryStatus($this->queryBuilder, $statuses);

        $this->assertEquals($expected, $this->getProductSkus($this->queryBuilder->getQuery()->getArrayResult()));
    }

    /**
     * @return array
     */
    public function modifyByInventoryStatusDataProvider()
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
                ],
            ],
        ];
    }

    /**
     * @param array $products
     * @return array
     */
    protected function getProductSkus(array $products)
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
