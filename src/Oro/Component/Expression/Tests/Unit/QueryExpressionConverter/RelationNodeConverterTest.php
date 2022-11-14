<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;
use Oro\Component\Expression\QueryExpressionConverter\RelationNodeConverter;

class RelationNodeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new RelationNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(
        string $container,
        string $field,
        string $relationField,
        ?int $containerId,
        array $aliasMapping,
        string $expected
    ) {
        $expr = new Expr();
        $params = [];

        $node = new RelationNode($container, $field, $relationField, $containerId);

        $converter = new RelationNodeConverter();
        $this->assertEquals($expected, $converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function convertDataProvider(): array
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No table alias found for relation "Product::relation"');

        $node = new RelationNode('Product', 'relation', 'value');
        $converter = new RelationNodeConverter();
        $converter->convert($node, $expr, $params, $aliasMapping);
    }
}
