<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\QueryExpressionConverter\ValueNodeConverter;
use Oro\Component\Expression\Node\ValueNode;

class ValueNodeConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->getMock(NodeInterface::class);
        $converter = new ValueNodeConverter();
        $this->assertNull($converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider valuesDataProvider
     * @param mixed $value
     * @param string|int $expected
     * @param array $expectedParameters
     */
    public function testConvert($value, $expected, array $expectedParameters)
    {
        $expr = new Expr();
        $params = [];

        $node = new ValueNode($value);
        $converter = new ValueNodeConverter();
        $this->assertEquals($expected, $converter->convert($node, $expr, $params));
        $this->assertEquals($expectedParameters, $params);
    }

    /**
     * @return array
     */
    public function valuesDataProvider()
    {
        return [
            [
                'test',
                ':_vn0',
                ['_vn0' => 'test']
            ],
            [
                42,
                42,
                []
            ],
            [
                [1, 3, 5],
                ':_vn0',
                ['_vn0' => [1, 3, 5]]
            ]
        ];
    }
}
