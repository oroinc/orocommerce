<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;
use Oro\Component\Expression\QueryExpressionConverter\NullSafeConverter;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

class NullSafeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new NullSafeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    public function testConvertException()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Converter is not set.');

        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(NodeInterface::class);


        $node = new BinaryNode($left, $right, '==');
        $converter = new NullSafeConverter();
        $converter->convert($node, $expr, $params, $aliasMapping);
    }

    /**
     * @dataProvider operationDataProvider
     */
    public function testConvert(?NodeInterface $left, ?NodeInterface $right, string $operation, string $expected)
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);

        $converter = new NullSafeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter
            ->expects($this->any())
            ->method('convert')
            ->willReturn("status");

        $node = new BinaryNode($left, $right, $operation);
        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function operationDataProvider(): array
    {
        return [
            [
                new NameNode('status'),
                new ValueNode(null),
                '==',
                "status IS NULL"
            ],
            [
                new NameNode('status'),
                new ValueNode(null),
                '!=',
                "status IS NOT NULL"
            ],
            [
                new ValueNode(null),
                new NameNode('status'),
                '==',
                "status IS NULL"
            ],
            [
                new ValueNode(null),
                new NameNode('status'),
                '!=',
                "status IS NOT NULL"
            ],
        ];
    }
}
