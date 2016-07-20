<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Expression\ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->expressionParser = new Expression\ExpressionParser(
            new Expression\ExpressionLanguageConverter($this->fieldsProvider)
        );
        $this->expressionParser->addNameMapping('PriceList', 'pl');
        $this->expressionParser->addNameMapping('Product', 'p');
    }

    public function testGetNamesMapping()
    {
        $this->assertEquals(
            [
                'PriceList' => 'pl',
                'Product' => 'p'
            ],
            $this->expressionParser->getNamesMapping()
        );
    }

    public function testGetReverseNameMapping()
    {
        $this->assertEquals(
            [
                'pl' => 'PriceList',
                'p' => 'Product'
            ],
            $this->expressionParser->getReverseNameMapping()
        );
    }

    public function testParse()
    {
        $this->assertCategoryRelation();

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
                        new Expression\RelationNode('p', 'category', 'minMargin'),
                        '*'
                    ),
                    '>'
                ),
                'and'
            ),
            new Expression\BinaryNode(
                new Expression\BinaryNode(
                    new Expression\RelationNode('p', 'category', 'categoryId'),
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
        $this->assertCategoryRelation();
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
                    'p::category' => ['minMargin', 'categoryId']
                ]
            ],
            [
                '1+1',
                []
            ]
        ];
    }

    protected function assertCategoryRelation()
    {
        $this->fieldsProvider->expects($this->any())
            ->method('isRelation')
            ->willReturnMap(
                [
                    ['p', 'category', true]
                ]
            );
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with('p', 'category')
            ->willReturn('Category');
        $this->fieldsProvider->expects($this->any())
            ->method('getIdentityFieldName')
            ->with('Category')
            ->willReturn('categoryId');
    }
}
