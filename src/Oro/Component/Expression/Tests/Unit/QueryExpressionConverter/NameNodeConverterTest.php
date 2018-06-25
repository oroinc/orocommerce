<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\QueryExpressionConverter\NameNodeConverter;

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
     *
     * @param string $container
     * @param string $field
     * @param int|null $containerId
     * @param array $aliasMapping
     * @param string $expected
     */
    public function testConvert($container, $field, $containerId, array $aliasMapping, $expected)
    {
        $expr = new Expr();
        $params = [];

        $node = new NameNode($container, $field, $containerId);

        $converter = new NameNodeConverter();
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
                'value',
                42,
                ['PriceList|42' => 'pl42'],
                'pl42.value'
            ],
            [
                'Product',
                'margin',
                null,
                ['Product' => 'p'],
                'p.margin'
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
