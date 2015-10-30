<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductVisibilityQueryBuilderModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $productQueryBuilderModifier;

    /**
     *
     */
    protected function setUp()
    {
        $this->productQueryBuilderModifier = new ProductVisibilityQueryBuilderModifier();
    }

    /**
     * @param string $method
     * @param string $field
     * @param array $params
     *
     * @dataProvider testModifyDataProvider
     */
    public function testModify($method, $field, $params)
    {
        /** @var \Doctrine\ORM\EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());

        $queryBuilder = new QueryBuilder($em);
        $queryBuilder->from('TestEntity', 'e');

        $this->productQueryBuilderModifier->$method($queryBuilder, $params);

        $this->assertEquals(
            sprintf('SELECT FROM TestEntity e WHERE e.%s IN(:values)', $field),
            $queryBuilder->getDQL()
        );
        $this->assertCount(1, $queryBuilder->getParameters());
        $this->assertEquals($params, $queryBuilder->getParameters()->first()->getValue());
    }

    /**
     * @return array
     */
    public function testModifyDataProvider()
    {
        return [
            'modifyByStatus'          => [
                'method' => 'modifyByStatus',
                'field'  => 'status',
                'params' => ['status'],
            ],
            'modifyByInventoryStatus' => [
                'method' => 'modifyByInventoryStatus',
                'field'  => 'inventory_status',
                'params' => ['status'],
            ],
        ];
    }

    /**
     * @param string $method
     * @param array $field
     *
     * @dataProvider testModifyEmptyDataProvider
     */
    public function testModifyEmpty($method, $field)
    {
        /** @var \Doctrine\ORM\EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        $queryBuilder = new QueryBuilder($em);
        $queryBuilder->from('TestEntity', 'e');

        $this->productQueryBuilderModifier->$method($queryBuilder, []);

        $this->assertEquals(
            sprintf('SELECT FROM TestEntity e WHERE 1 = 0', $field),
            $queryBuilder->getDQL()
        );
        $this->assertCount(0, $queryBuilder->getParameters());
    }

    /**
     * @return array
     */
    public function testModifyEmptyDataProvider()
    {
        return [
            'modifyByStatus'          => [
                'method' => 'modifyByInventoryStatus',
                'field'  => 'status',
            ],
            'modifyByInventoryStatus' => [
                'method' => 'modifyByInventoryStatus',
                'field'  => 'inventory_status',
            ],
        ];
    }
}
