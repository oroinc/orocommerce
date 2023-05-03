<?php

namespace Oro\Component\Expression\Tests\Unit\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;
use Oro\Component\Expression\QueryExpressionConverter\BinaryNodeConverter;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

class BinaryNodeConverterTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->createMock(NodeInterface::class);
        $converter = new BinaryNodeConverter();
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

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);

        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(NodeInterface::class);
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter->expects($this->any())
            ->method('convert')
            ->willReturnMap([
                [$left, $expr, $params, $aliasMapping, 'a.b'],
                [$right, $expr, $params, $aliasMapping, 'c.d']
            ]);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
        $this->assertArrayNotHasKey(QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION, $params);
    }

    public function operationDataProvider(): array
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

    /**
     * @dataProvider operationWithValueDataProvider
     */
    public function testConvertWithValue(
        string $operation,
        string $expected,
        array $params,
        string $convertedValue
    ) {
        $expr = new Expr();
        $aliasMapping = [];

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);

        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(ValueNode::class);
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter->expects($this->any())
            ->method('convert')
            ->willReturnMap([
                [$left, $expr, $params, $aliasMapping, 'a.b'],
                [$right, $expr, $params, $aliasMapping, $convertedValue]
            ]);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertEquals($expected, (string)$converter->convert($node, $expr, $params, $aliasMapping));
        $this->assertArrayNotHasKey(QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION, $params);
    }

    public function operationWithValueDataProvider(): array
    {
        return [
            ['and', 'a.b AND 10', [], '10'],
            ['or', 'a.b OR 10', [], '10'],
            ['+', 'a.b + 10', [], '10'],
            ['-', 'a.b - 10', [], '10'],
            ['*', 'a.b * 10', [], '10'],
            ['/', 'a.b / 10', [], '10'],
            ['like', 'a.b LIKE :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['>', 'a.b > :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['>=', 'a.b >= :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['<', 'a.b < :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['<=', 'a.b <= :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['==', 'a.b = :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['!=', 'a.b <> :_vn0', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            ['in', 'a.b IN(:_vn0)', [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true], ':_vn0'],
            [
                'not in',
                'a.b NOT IN(:_vn0)',
                [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true],
                ':_vn0'
            ],
            ['%', 'MOD(a.b, 10)', [], '10']
        ];
    }

    public function testConvertInArray()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);

        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(ValueNode::class);
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $mainConverter->expects($this->any())
            ->method('convert')
            ->willReturnMap([
                [$left, $expr, $params, $aliasMapping, 'a.b'],
                [
                    $right,
                    $expr,
                    array_merge($params, [QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION => true]),
                    $aliasMapping,
                    ':_vn0'
                ]
            ]);

        $node = new BinaryNode($left, $right, 'in');
        $this->assertEquals('a.b IN(:_vn0)', (string)$converter->convert($node, $expr, $params, $aliasMapping));
        $this->assertArrayNotHasKey(QueryExpressionConverterInterface::REQUIRE_PARAMETRIZATION, $params);
    }

    public function testConvertException()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported operation "unknown"');

        $mainConverter = $this->createMock(QueryExpressionConverterInterface::class);

        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(ValueNode::class);
        $converter = new BinaryNodeConverter();
        $converter->setConverter($mainConverter);

        $node = new BinaryNode($left, $right, 'unknown');
        $converter->convert($node, $expr, $params, $aliasMapping);
    }
}
