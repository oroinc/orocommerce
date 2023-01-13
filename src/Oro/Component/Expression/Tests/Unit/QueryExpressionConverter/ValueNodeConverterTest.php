<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Component\Expression\QueryExpressionConverter\ValueNodeConverter;

class ValueNodeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new ValueNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testConvert(mixed $value, string|int $expected, array $expectedParameters, array $params)
    {
        $expr = new Expr();

        $node = new ValueNode($value);
        $converter = new ValueNodeConverter();
        $this->assertEquals($expected, $converter->convert($node, $expr, $params));
        $this->assertEquals($expectedParameters, $params);
    }

    public function valuesDataProvider(): array
    {
        return [
            'scalar value' => [
                'test',
                ':_vn0',
                ['_vn0' => 'test'],
                []
            ],
            'numeric value' => [
                42,
                42,
                [],
                []
            ],
            'scalar value force parametrization' => [
                42,
                ':_vn0',
                [
                    QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true,
                    '_vn0' => 42
                ],
                [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true]
            ],
            'array value' => [
                [1, 3, 5],
                ':_vn0',
                ['_vn0' => [1, 3, 5]],
                []
            ]
        ];
    }
}
