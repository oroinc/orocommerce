<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Model;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);

        $this->modifier = new ProductVisibilityQueryBuilderModifier();

        $this->queryBuilder = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BProductBundle:Product')->createQueryBuilder('p');
    }

    /**
     * @dataProvider modifyByStatusDataProvider
     * @param array $statuses
     * @param array $expected
     */
    public function testModifyByStatus($statuses, array $expected)
    {
        $this->modifier->modifyByStatus($this->queryBuilder, $statuses);
        $result = array_map(
            function ($product) {
                return $product['sku'];
            },
            $this->queryBuilder->getQuery()->getArrayResult()
        );

        $this->assertEquals($expected, $result);
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
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ],
            ],
            'disabled products' => [
                'statuses' => [
                    'disabled',
                ],
                'expected' => [
                    LoadProductData::PRODUCT_2,
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
                ],
            ],
        ];
    }

    /**
     * @dataProvider modifyByStatusDataProvider
     * @param array $statuses
     * @param array $expected
     */
    public function testModifyByInventoryStatus($statuses, array $expected)
    {
        $this->modifier->modifyByStatus($this->queryBuilder, $statuses);
        $result = array_map(
            function ($product) {
                return $product['sku'];
            },
            $this->queryBuilder->getQuery()->getArrayResult()
        );

        $this->assertEquals($expected, $result);
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
                ],
            ],
        ];
    }

    public function testModifyByStatusesParameterNamesConflict()
    {
        $this->queryBuilder->select('p.sku');
        $this->modifier->modifyByStatus($this->queryBuilder, [Product::STATUS_DISABLED]);
        $this->modifier->modifyByInventoryStatus(
            $this->queryBuilder,
            [
                Product::INVENTORY_STATUS_IN_STOCK,
            ]
        );

        $this->queryBuilder
            ->andWhere('p.status in (:status)')
            ->setParameter('status', [Product::STATUS_ENABLED, Product::STATUS_DISABLED])
            ->andWhere('p.inventory_status in (:inventory_status)')
            ->setParameter('inventory_status', [
                Product::INVENTORY_STATUS_IN_STOCK,
                Product::INVENTORY_STATUS_OUT_OF_STOCK,
                Product::INVENTORY_STATUS_DISCONTINUED,
            ]);

        $this->assertCount(4, $this->queryBuilder->getParameters());
        $this->assertEquals(LoadProductData::PRODUCT_2, $this->queryBuilder->getQuery()->getSingleScalarResult());
    }
}
