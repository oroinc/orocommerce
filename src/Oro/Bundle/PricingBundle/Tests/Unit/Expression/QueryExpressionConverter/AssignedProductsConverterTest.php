<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\AssignedProductsConverter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;
use Oro\Component\Expression\Node\ValueNode;

class AssignedProductsConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var AssignedProductsConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMock(FieldsProviderInterface::class);
        $this->converter = new AssignedProductsConverter($this->fieldsProvider);
    }

    public function testConvertUnsupported()
    {
        $expr = new Expr();
        $params = [];

        $node = $this->getMock(NodeInterface::class);
        $this->assertNull($this->converter->convert($node, $expr, $params));
    }

    /**
     * @dataProvider invalidLeftOperandsDataProvider
     *
     * @param BinaryNode $node
     */
    public function testConvertInvalidLeftOperand(BinaryNode $node)
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap(
                [
                    [ProductPrice::class, 'priceList', PriceList::class],
                    [ProductPrice::class, 'product', Product::class]
                ]
            );

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Left operand of in operation for assigned products condition must be product identifier field'
        );
        $this->converter->convert($node, $expr, $params, $aliasMapping);
    }

    /**
     * @return array
     */
    public function invalidLeftOperandsDataProvider()
    {
        $right = new NameNode(PriceList::class, 'assignedProducts', 4);
        return [
            'incorrect node type' => [
                new BinaryNode(
                    new ValueNode(1),
                    $right,
                    'in'
                )
            ],
            'incorrect name node class' => [
                new BinaryNode(
                    new NameNode(PriceList::class, 'id'),
                    $right,
                    'in'
                )
            ],
            'incorrect name node field' => [
                new BinaryNode(
                    new NameNode(Product::class, 'value'),
                    $right,
                    'in'
                )
            ],
            'incorrect relation node class' => [
                new BinaryNode(
                    new RelationNode(ProductPrice::class, 'priceList', 'id'),
                    $right,
                    'in'
                )
            ],
            'incorrect relation node field' => [
                new BinaryNode(
                    new RelationNode(ProductPrice::class, 'product', 'value'),
                    $right,
                    'in'
                )
            ],
        ];
    }

    public function testConvertNoAlias()
    {
        $expr = new Expr();
        $params = [];
        $aliasMapping = [];

        $node = new BinaryNode(
            new NameNode(Product::class, 'id'),
            new NameNode(PriceList::class, 'assignedProducts', 4),
            'in'
        );

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'No table alias found for "Oro\Bundle\ProductBundle\Entity\Product"'
        );
        $this->converter->convert($node, $expr, $params, $aliasMapping);
    }

    public function testConvertIn()
    {
        $expected = 'EXISTS(SELECT 1 FROM Oro\Bundle\PricingBundle\Entity\PriceListToProduct _ap ' .
            'WHERE _ap.product = pr AND _ap.priceList = pl4)';

        $expr = new Expr();
        $params = [];
        $aliasMapping = [
            Product::class => 'pr',
            PriceList::class . '|4' => 'pl4'
        ];

        $node = new BinaryNode(
            new NameNode(Product::class, 'id'),
            new NameNode(PriceList::class, 'assignedProducts', 4),
            'in'
        );

        $this->assertEquals($expected, (string)$this->converter->convert($node, $expr, $params, $aliasMapping));
    }

    public function testConvertNotIn()
    {
        $expected = 'NOT(EXISTS(SELECT 1 FROM Oro\Bundle\PricingBundle\Entity\PriceListToProduct _ap ' .
            'WHERE _ap.product = pr AND _ap.priceList = pl4))';

        $expr = new Expr();
        $params = [];
        $aliasMapping = [
            Product::class => 'pr',
            PriceList::class . '|4' => 'pl4'
        ];

        $node = new BinaryNode(
            new NameNode(Product::class, 'id'),
            new NameNode(PriceList::class, 'assignedProducts', 4),
            'not in'
        );

        $this->assertEquals($expected, (string)$this->converter->convert($node, $expr, $params, $aliasMapping));
    }
}
