<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\RelationNodeConverter;
use Oro\Bundle\PricingBundle\Expression\RelationNode;

class RelationNodeConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->getMock(NodeInterface::class);
        $converter = new RelationNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider convertDataProvider
     *
     * @param string $container
     * @param string $field
     * @param string $relationField
     * @param int|null $containerId
     * @param array $aliasMapping
     * @param string $expected
     */
    public function testConvert($container, $field, $relationField, $containerId, array $aliasMapping, $expected)
    {
        $expr = new Expr();
        $params = [];

        $node = new RelationNode($container, $field, $relationField, $containerId);

        $converter = new RelationNodeConverter();
        $this->assertEquals($expected, $converter->convert($node, $expr, $params, $aliasMapping));
    }

    /**
     * @return array
     */
    public function convertDataProvider()
    {
        return [
            [
                'PriceList',
                'products',
                'value',
                42,
                ['PriceList::products|42' => 'pp42'],
                'pp42.value'
            ],
            [
                'Product',
                'category',
                'id',
                null,
                ['Product::category' => 'cat'],
                'cat.id'
            ]
        ];
    }

    public function testConvertException()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'No table alias found for relation "Product::relation"'
        );

        $node = new RelationNode('Product', 'relation', 'value');
        $converter = new RelationNodeConverter();
        $converter->convert($node, $expr, $params, $aliasMapping);
    }
}
