<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\UnaryNodeConverter;
use Oro\Bundle\PricingBundle\Expression\UnaryNode;

class UnaryNodeConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->getMock(NodeInterface::class);
        $converter = new UnaryNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider operationDataProvider
     * @param string $operation
     */
    public function testConvert($operation, $expected)
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];
        $subNode = $this->getMock(NodeInterface::class);

        $mainConverter = $this->getMock(QueryExpressionConverterInterface::class);
        $converter = new UnaryNodeConverter();
        $converter->setConverter($mainConverter);

        $node = new UnaryNode($subNode, $operation);

        $mainConverter->expects($this->once())
            ->method('convert')
            ->with($subNode, $expr, $params, $aliasMapping)
            ->willReturn('a.b');

        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
    }

    /**
     * @return array
     */
    public function operationDataProvider()
    {
        return [
            ['not', 'NOT(a.b)'],
            ['-', '(-a.b)'],
            ['+', 'a.b']
        ];
    }
}
