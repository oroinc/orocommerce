<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\ExpressionLanguage\Lexer;
use Oro\Component\ExpressionLanguage\Parser;

/**
 * @dbIsolation
 */
class ExpressionLanguageTest extends WebTestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider parseDataProvider
     *
     * @param string $expression
     * @param string $orderIdentifier
     * @param bool $expectedResult
     */
    public function testParse($expression, $orderIdentifier, $expectedResult)
    {
        $lineItems = $this->findOrder($orderIdentifier)->getLineItems();

        $factory = $this->getContainer()->get('oro_shipping.expression_language.factory');

        $values = [
            'lineItems' => array_map(function (OrderLineItem $lineItem) use ($factory, $lineItems) {
                return $factory->createOrderLineItemDecorator($lineItems, $lineItem);
            }, $lineItems->toArray()),
        ];

        $functions = [
            'count' => [
                'compiler' => function ($field) {
                    return sprintf('count(%s)', $field);
                },
                'evaluator' => function ($arguments, $field) {
                    return count($field);
                }
            ]
        ];
        $this->parser = new Parser($functions);
        $lexer = new Lexer();
        $tokens = $lexer->tokenize($expression);
        $nodes = $this->parser->parse($tokens, array_keys($values));
        $this->assertEquals($expectedResult, $nodes->evaluate($functions, $values));
    }

    /**
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            [
                'expression' => <<<EXPR
lineItems.all(
    lineItem.product.status in ['enabled'] 
    and
    lineItem.product.warehouseInventoryLevels.any(
        warehouseInventoryLevel.warehouse.name = "Main Warehouse" 
        and
        warehouseInventoryLevel.productUnitPrecision.unit.code = lineItem.productUnit.code
        and
        warehouseInventoryLevel.quantity > lineItem.quantity
    )
)
and 
count(lineItems) > 1
EXPR
                ,
                'order' => 'FR1012401',
                'expectedResult' => true,
            ]
        ];
    }

    /**
     * @param $identifier
     * @return null|Order
     */
    protected function findOrder($identifier)
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(Order::class)
            ->findOneBy(['identifier' => $identifier]);
    }
}
