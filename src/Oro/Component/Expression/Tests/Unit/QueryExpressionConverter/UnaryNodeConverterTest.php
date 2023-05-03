<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\UnaryNode;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Component\Expression\QueryExpressionConverter\UnaryNodeConverter;

class UnaryNodeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new UnaryNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider operationDataProvider
     */
    public function testConvert(string $operation, string $expected)
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];
        $subNode = $this->createMock(NodeInterface::class);

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);
        $converter = new UnaryNodeConverter();
        $converter->setConverter($mainConverter);

        $node = new UnaryNode($subNode, $operation);

        $mainConverter->expects($this->once())
            ->method('convert')
            ->with($subNode, $expr, $params, $aliasMapping)
            ->willReturn('a.b');

        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function operationDataProvider(): array
    {
        return [
            ['not', 'NOT(a.b)'],
            ['-', '(-a.b)'],
            ['+', 'a.b']
        ];
    }
}
