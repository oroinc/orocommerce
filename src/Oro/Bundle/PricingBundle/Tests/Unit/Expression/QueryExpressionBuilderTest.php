<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\PricingBundle\Expression;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionBuilder;

class QueryExpressionBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $node = new Expression\BinaryNode(
            new Expression\BinaryNode(
                new Expression\BinaryNode(
                    new Expression\NameNode('pl', 'active', 42),
                    new Expression\ValueNode(true),
                    '=='
                ),
                new Expression\BinaryNode(
                    new Expression\BinaryNode(
                        new Expression\NameNode('p', 'margin'),
                        new Expression\ValueNode(10),
                        '*'
                    ),
                    new Expression\BinaryNode(
                        new Expression\BinaryNode(
                            new Expression\ValueNode(130),
                            new Expression\NameNode('c', 'minMargin'),
                            '*'
                        ),
                        new Expression\BinaryNode(
                            new Expression\ValueNode(1),
                            new Expression\BinaryNode(
                                new Expression\NameNode('pl', 'someAttr', 3),
                                new Expression\RelationNode('pl', 'prices', 'value', 42),
                                '*'
                            ),
                            '-'
                        ),
                        '+'
                    ),
                    '>'
                ),
                'and'
            ),
            new Expression\BinaryNode(
                new Expression\BinaryNode(
                    new Expression\NameNode('c'),
                    new Expression\UnaryNode(
                        new Expression\NameNode('p', 'MSRP'),
                        '-'
                    ),
                    '=='
                ),
                new Expression\UnaryNode(
                    new Expression\BinaryNode(
                        new Expression\RelationNode('p', 'MSRP', 'currency'),
                        new Expression\ValueNode('U'),
                        'matches'
                    ),
                    'not'
                ),
                'and'
            ),
            '||'
        );

        $expr = new Expr();

        $aliasMap = [
            'pl|42' => 'mapPL42',
            'pl::prices|42' => 'mapPrice42',
            'pl|3' => 'mapPL3',
            'c' => 'mapC',
            'p' => 'mapP',
            'p::MSRP' => 'mapMSRP'
        ];

        $converter = new QueryExpressionBuilder();
        $params = [];
        $actual = $converter->convert($node, $expr, $params, $aliasMap);

        $this->assertEquals(
            '(mapPL42.active = :_vn0 AND mapP.margin * 10 ' .
            '> (130 * mapC.minMargin) + (1 - (mapPL3.someAttr * mapPrice42.value))) ' .
            'OR (mapC = (-mapP.MSRP) AND NOT(mapMSRP.currency LIKE :_vn1))',
            (string)$actual
        );
        $this->assertEquals(['_vn0' => true, '_vn1' => 'U'], $params);
    }

    public function testConvertIn()
    {
        $node = new Expression\BinaryNode(
            new Expression\NameNode('p', 'id'),
            new Expression\ValueNode([1, 3]),
            'in'
        );
        $converter = new QueryExpressionBuilder();
        $params = [];
        $expr = new Expr();
        $actual = $converter->convert($node, $expr, $params);
        $this->assertEquals(
            'p.id IN(:_vn0)',
            (string)$actual
        );
        $this->assertEquals(['_vn0' => [1, 3]], $params);
    }

    public function testConvertInMemberOf()
    {
        $node = new Expression\BinaryNode(
            new Expression\NameNode('p', 'id'),
            new Expression\NameNode('pl', 'assignedProduct'),
            'in'
        );
        $converter = new QueryExpressionBuilder();
        $params = [];
        $expr = new Expr();
        $actual = $converter->convert($node, $expr, $params);
        $this->assertEquals(
            'p.id MEMBER OF pl.assignedProduct',
            (string)$actual
        );
        $this->assertEquals([], $params);
    }

    public function testConvertNotIn()
    {
        $node = new Expression\BinaryNode(
            new Expression\NameNode('p', 'id'),
            new Expression\ValueNode([1, 3]),
            'not in'
        );
        $converter = new QueryExpressionBuilder();
        $params = [];
        $expr = new Expr();
        $actual = $converter->convert($node, $expr, $params);
        $this->assertEquals(
            'p.id NOT IN(:_vn0)',
            (string)$actual
        );
        $this->assertEquals(['_vn0' => [1, 3]], $params);
    }

    public function testConvertNotInMemberOf()
    {
        $node = new Expression\BinaryNode(
            new Expression\NameNode('p', 'id'),
            new Expression\NameNode('pl', 'assignedProduct'),
            'not in'
        );
        $converter = new QueryExpressionBuilder();
        $params = [];
        $expr = new Expr();
        $actual = $converter->convert($node, $expr, $params);
        $this->assertEquals(
            'NOT(p.id MEMBER OF pl.assignedProduct)',
            (string)$actual
        );
        $this->assertEquals([], $params);
    }
}
