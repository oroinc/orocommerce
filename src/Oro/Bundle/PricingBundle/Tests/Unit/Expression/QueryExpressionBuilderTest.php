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
                    new Expression\NameNode('pl', 'currency'),
                    new Expression\ValueNode('USD'),
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
                                new Expression\NameNode('pl', 'someAttr'),
                                new Expression\NameNode('c', 'maxMargin'),
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
            'pl' => 'mapPL',
            'c' => 'mapC',
            'p' => 'mapP',
            'p::MSRP' => 'mapMSRP'
        ];

        $converter = new QueryExpressionBuilder();
        $params = [];
        $actual = $converter->convert($node, $expr, $params, $aliasMap);

        $this->assertEquals(
            '(mapPL.currency = :_vn0 AND mapP.margin * 10 ' .
            '> (130 * mapC.minMargin) + (1 - (mapPL.someAttr * mapC.maxMargin))) ' .
            'OR (mapC = (-mapP.MSRP) AND NOT(mapMSRP.currency LIKE :_vn1))',
            (string)$actual
        );
        $this->assertEquals(['_vn0' => 'USD', '_vn1' => 'U'], $params);
    }
}
