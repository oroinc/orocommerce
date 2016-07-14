<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Expression\ExpressionParser
     */
    protected $expressionParser;

    protected function setUp()
    {
        $this->expressionParser = new Expression\ExpressionParser(new Expression\ExpressionLanguageConverter());
        $this->expressionParser->addNameMapping('PriceList', 'pl');
        $this->expressionParser->addNameMapping('Product', 'p');
        $this->expressionParser->addNameMapping('Category', 'c');
        $this->expressionParser->addExpressionMapping('Product.category', 'Category');
    }

    public function testParse()
    {
        $expression = "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
            " || (Product.category == -Product.MSRP and not (Product.MSRP.currency matches 'U'))";

        $expected = new Expression\BinaryNode(
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
                        new Expression\ValueNode(130),
                        new Expression\NameNode('c', 'minMargin'),
                        '*'
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
        
        $this->assertEquals($expected, $this->expressionParser->parse($expression));
    }

    /**
     * @dataProvider lexemeExpressionsDataProvider
     * @param string $expression
     * @param array $expected
     */
    public function testGetUsedLexemes($expression, array $expected)
    {
        $this->assertEquals($expected, $this->expressionParser->getUsedLexemes($expression));

    }

    /**
     * @return array
     */
    public function lexemeExpressionsDataProvider()
    {
        return [
            [
                "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.someValue and not (Product.MSRP.currency matches 'U'))",
                [
                    'pl' => ['currency'],
                    'p' => ['margin', 'someValue'],
                    'p::MSRP' => ['currency'],
                    'c' => ['minMargin', null]
                ]
            ],
            [
                '1+1',
                []
            ]
        ];
    }
}
