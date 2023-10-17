<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\QueryExpressionConverter\NameNodeConverter;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

class NameNodeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new NameNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(
        string $container,
        string $field,
        ?int $containerId,
        array $aliasMapping,
        string $expected
    ) {
        $expr = new Expr();
        $params = [];

        $node = new NameNode($container, $field, $containerId);

        $converter = new NameNodeConverter();
        $this->assertEquals($expected, $converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function convertDataProvider(): array
    {
        return [
            'field with container id' => [
                'PriceList',
                'value',
                42,
                [QueryExpressionConverterInterface::MAPPING_TABLES => ['PriceList|42' => 'pl42']],
                'pl42.value'
            ],
            'simple field' => [
                'Product',
                'margin',
                null,
                [QueryExpressionConverterInterface::MAPPING_TABLES => ['Product' => 'p']],
                'p.margin'
            ],
            'virtual field' => [
                'Product',
                'virtual_field',
                null,
                [
                    QueryExpressionConverterInterface::MAPPING_TABLES => ['Product' => 'p'],
                    QueryExpressionConverterInterface::MAPPING_COLUMNS => [
                        'Product' => ['virtual_field' => 'LOWER(p.field)']
                    ]
                ],
                'LOWER(p.field)'
            ]
        ];
    }

    public function testConvertException()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No table alias found for "Product"');

        $node = new NameNode('Product', 'value');
        $converter = new NameNodeConverter();
        $converter->convert($node, $expr, $params, $aliasMapping);
    }
}
