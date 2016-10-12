<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\ExpressionLanguageConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var FieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMock(FieldsProviderInterface::class);
        $this->expressionParser = new ExpressionParser(
            new ExpressionLanguageConverter($this->fieldsProvider)
        );
        $this->expressionParser->addNameMapping('PriceList', 'pl');
        $this->expressionParser->addNameMapping('Product', 'p');
    }

    public function testExpressionsMapping()
    {
        $expressionParser = new ExpressionParser(new ExpressionLanguageConverter($this->fieldsProvider));
        $expressionParser->addNameMapping('Product', 'p');
        $expressionParser->addExpressionMapping('category', 'Product.category');

        $expected = new Node\RelationNode('p', 'category', 'id');
        $this->assertEquals($expected, $expressionParser->parse('category.id'));
    }

    public function testParseEmpty()
    {
        $this->assertNull($this->expressionParser->parse(''));
    }

    public function testParseCache()
    {
        $expected = new Node\NameNode('p', 'id');

        /** @var ExpressionLanguageConverter|\PHPUnit_Framework_MockObject_MockObject $converter */
        $converter = $this->getMockBuilder(ExpressionLanguageConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converter->expects($this->once())
            ->method('convert')
            ->willReturn($expected);
        $expressionParser = new ExpressionParser($converter);
        $expressionParser->addNameMapping('Product', 'p');
        $expression = 'Product.id';
        $this->assertEquals($expected, $expressionParser->parse($expression));
        $this->assertEquals($expected, $expressionParser->parse($expression));
    }

    public function testGetUsedLexemesEmpty()
    {
        $this->assertEquals([], $this->expressionParser->getUsedLexemes(''));
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

    /**
     * @dataProvider invalidExpressionDataProvider
     * @param string $expression
     * @param string $exceptionMessage
     */
    public function testParseException($expression, $exceptionMessage)
    {
        $this->setExpectedException(SyntaxError::class, $exceptionMessage);
        $this->expressionParser->parse($expression);
    }

    /**
     * @return array
     */
    public function invalidExpressionDataProvider()
    {
        return [
            [
                'PriceList.value[1]', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.relation.value[1]', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.relation[1].value', 'Attribute is supported only for root variable in expression'
            ],
            [
                'PriceList.category.relation.id', 'Relations of related entities are not allowed to be used'
            ],
            [
                'PriceList.value(1)', 'Function calls are not supported'
            ],
            [
                'Product.id in [1, 3, Product.category.id]', 'Only constant are supported for arrays'
            ],
            [
                'Product.id in PriceList[4].assignedProducts/2',
                'Right operand of in must be an array of scalars or field expression'
            ],
            [
                'Product.id not in PriceList[4].assignedProducts/2',
                'Right operand of not in must be an array of scalars or field expression'
            ],
            [
                '10 in Product.category',
                'Left operand of in must be field expression'
            ],
            [
                '10 not in Product.category',
                'Left operand of not in must be field expression'
            ]
        ];
    }

    /**
     * @dataProvider expressionsDataProvider
     * @param string $expression
     * @param Node\NodeInterface $expected
     */
    public function testParse($expression, Node\NodeInterface $expected)
    {
        $this->prepareCategoryRelation();
        $this->assertEquals($expected, $this->expressionParser->parse($expression));
    }

    /**
     * @return array
     */
    public function expressionsDataProvider()
    {
        return [
            [
                '100',
                new Node\ValueNode(100)
            ],
            [
                '[100]',
                new Node\ValueNode([100])
            ],
            [
                'PriceList[42].prices.currency',
                new Node\RelationNode('pl', 'prices', 'currency', 42)
            ],
            [
                'Product',
                new Node\NameNode('p', 'productId')
            ],
            [
                'PriceList[42].assignedProducts',
                new Node\NameNode('pl', 'assignedProducts', 42)
            ],
            [
                'Product.category.id in [1, 5]',
                new Node\BinaryNode(
                    new Node\RelationNode('p', 'category', 'id'),
                    new Node\ValueNode([1, 5]),
                    'in'
                )
            ],
            [
                "(PriceList.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.MSRP and not (Product.MSRP.currency matches 'U'))",
                new Node\BinaryNode(
                    new Node\BinaryNode(
                        new Node\BinaryNode(
                            new Node\NameNode('pl', 'currency'),
                            new Node\ValueNode('USD'),
                            '=='
                        ),
                        new Node\BinaryNode(
                            new Node\BinaryNode(
                                new Node\NameNode('p', 'margin'),
                                new Node\ValueNode(10),
                                '*'
                            ),
                            new Node\BinaryNode(
                                new Node\ValueNode(130),
                                new Node\RelationNode('p', 'category', 'minMargin'),
                                '*'
                            ),
                            '>'
                        ),
                        'and'
                    ),
                    new Node\BinaryNode(
                        new Node\BinaryNode(
                            new Node\RelationNode('p', 'category', 'categoryId'),
                            new Node\UnaryNode(
                                new Node\NameNode('p', 'MSRP'),
                                '-'
                            ),
                            '=='
                        ),
                        new Node\UnaryNode(
                            new Node\BinaryNode(
                                new Node\RelationNode('p', 'MSRP', 'currency'),
                                new Node\ValueNode('U'),
                                'matches'
                            ),
                            'not'
                        ),
                        'and'
                    ),
                    '||'
                )
            ]
        ];
    }

    /**
     * @dataProvider lexemeExpressionsDataProvider
     * @param string $expression
     * @param array $expected
     * @param bool $withResolvedContainer
     */
    public function testGetUsedLexemes($expression, array $expected, $withResolvedContainer = false)
    {
        $this->prepareCategoryRelation();
        $this->assertEquals($expected, $this->expressionParser->getUsedLexemes($expression, $withResolvedContainer));
    }

    /**
     * @return array
     */
    public function lexemeExpressionsDataProvider()
    {
        return [
            [
                "(PriceList[1].prices.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.someValue and not (Product.MSRP.currency matches 'U'))" .
                ' and Product.id in PriceList[1].assignedProducts',
                [
                    'pl' => ['assignedProducts'],
                    'pl::prices' => ['currency'],
                    'p' => ['margin', 'someValue', 'id'],
                    'p::MSRP' => ['currency'],
                    'p::category' => ['minMargin', 'categoryId']
                ],
                false
            ],
            [
                '1+1',
                [],
                false
            ],
            [
                "(PriceList[1].prices.currency == 'USD' and Product.margin * 10 > 130*Product.category.minMargin)" .
                " || (Product.category == -Product.someValue and not (Product.MSRP.currency matches 'U'))" .
                ' and Product.id in PriceList[1].assignedProducts',
                [
                    'pl|1' => ['assignedProducts'],
                    'pl::prices|1' => ['currency'],
                    'p' => ['margin', 'someValue', 'id'],
                    'p::MSRP' => ['currency'],
                    'p::category' => ['minMargin', 'categoryId']
                ],
                true
            ],
        ];
    }

    protected function prepareCategoryRelation()
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
            ->willReturnMap(
                [
                    ['Category', 'categoryId'],
                    ['p', 'productId']
                ]
            );
    }
}
