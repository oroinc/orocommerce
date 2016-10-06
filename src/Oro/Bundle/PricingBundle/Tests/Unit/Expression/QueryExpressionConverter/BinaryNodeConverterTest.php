<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\BinaryNodeConverter;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Bundle\PricingBundle\Expression\ValueNode;

class BinaryNodeConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->getMock(NodeInterface::class);
        $converter = new BinaryNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider operationDataProvider
     *
     * @param string $operation
     * @param string $expected
     */
    public function testConvert($operation, $expected)
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $mainConverter = $this->getMock(QueryExpressionConverterInterface::class);

        $left = $this->getMock(NodeInterface::class);
        $right = $this->getMock(NodeInterface::class);
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter->expects($this->any())
            ->method('convert')
            ->willReturnMap(
                [
                    [$left, $expr, $params, $aliasMapping, 'a.b'],
                    [$right, $expr, $params, $aliasMapping, 'c.d']
                ]
            );

        $node = new BinaryNode($left, $right, $operation);
        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
    }

    /**
     * @return array
     */
    public function operationDataProvider()
    {
        return [
            ['and', 'a.b AND c.d'],
            ['or', 'a.b OR c.d'],
            ['like', 'a.b LIKE c.d'],
            ['+', 'a.b + c.d'],
            ['-', 'a.b - c.d'],
            ['*', 'a.b * c.d'],
            ['/', 'a.b / c.d'],
            ['>', 'a.b > c.d'],
            ['>=', 'a.b >= c.d'],
            ['<', 'a.b < c.d'],
            ['<=', 'a.b <= c.d'],
            ['==', 'a.b = c.d'],
            ['!=', 'a.b <> c.d'],
            ['in', 'a.b MEMBER OF c.d'],
            ['not in', 'NOT(a.b MEMBER OF c.d)'],
            ['%', 'MOD(a.b, c.d)']
        ];
    }

    public function testConvertInArray()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $mainConverter = $this->getMock(QueryExpressionConverterInterface::class);

        $left = $this->getMock(NodeInterface::class);
        $right = $this->getMockBuilder(ValueNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter->expects($this->any())
            ->method('convert')
            ->willReturnMap(
                [
                    [$left, $expr, $params, $aliasMapping, 'a.b'],
                    [$right, $expr, $params, $aliasMapping, ':_vn0']
                ]
            );

        $node = new BinaryNode($left, $right, 'in');
        $this->assertEquals('a.b IN(:_vn0)', (string)$converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function testConvertException()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->setExpectedException(\InvalidArgumentException::class, 'Unsupported operation "unknown"');

        $mainConverter = $this->getMock(QueryExpressionConverterInterface::class);

        $left = $this->getMock(NodeInterface::class);
        $right = $this->getMockBuilder(ValueNode::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $node = new BinaryNode($left, $right, 'unknown');
        $converter->convert($node, $expr, $params, $aliasMapping);
    }
}
