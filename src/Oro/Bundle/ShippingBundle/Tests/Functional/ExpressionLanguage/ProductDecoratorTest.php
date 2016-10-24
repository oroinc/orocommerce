<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\ExpressionLanguage;

use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @dbIsolation
 */
class ProductDecoratorTest extends WebTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadInventoryLevels::class]);
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $lineItems
     * @param string $expression
     * @param bool $expectedResult
     */
    public function testEvaluate(array $lineItems, $expression, $expectedResult)
    {
        $factory = $this->getContainer()->get('oro_shipping.expression_language.line_item_decorator_factory');

        $context = new ShippingContext();
        $context->setLineItems($this->prepareLineItems($lineItems));
        $lineItems = $context->getLineItems();

        $values = [
            'lineItems' => array_map(function (ShippingLineItemInterface $lineItem) use ($factory, $lineItems) {
                return $factory->createOrderLineItemDecorator($lineItems, $lineItem);
            }, $lineItems),
        ];

        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->register('count', function ($field) {
            return sprintf('count(%s)', $field);
        }, function ($arguments, $field) {
            return count($field);
        });
        $this->assertEquals($expectedResult, $expressionLanguage->evaluate($expression, $values));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        $expression = <<<'EXPR'
lineItems.all(
    lineItem.product.status in ['enabled']
    and
    lineItem.product.warehouseInventoryLevels.any(
        warehouseInventoryLevel.warehouse.name = 'First Warehouse' 
        and
        warehouseInventoryLevel.productUnitPrecision.unit.code = lineItem.productUnit.code
        and
        warehouseInventoryLevel.quantity > lineItem.quantity
    )
)
and 
count(lineItems) > 1
EXPR;

        return [
            'success execution' => [
                'lineItems' => [
                    [
                        'product' => LoadProductData::PRODUCT_1,
                        'quantity' => 9,
                        'unit' => LoadProductUnits::LITER,
                    ],
                    [
                        'product' => LoadProductData::PRODUCT_2,
                        'quantity' => 40,
                        'unit' => LoadProductUnits::BOTTLE,
                    ],
                ],
                'expression' => $expression,
                'expectedResult' => true,
            ],
            'requested quantity over than existing in warehouse' => [
                'lineItems' => [
                    [
                        'product' => LoadProductData::PRODUCT_1,
                        'quantity' => 9,
                        'unit' => LoadProductUnits::LITER,
                    ],
                    [
                        'product' => LoadProductData::PRODUCT_2,
                        'quantity' => 100,
                        'unit' => LoadProductUnits::BOTTLE,
                    ],
                ],
                'expression' => $expression,
                'expectedResult' => false,
            ]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareLineItems(array $data)
    {
        return array_map(function ($item) {
            return (new ShippingLineItem())
                ->setProduct($this->getReference($item['product']))
                ->setQuantity($item['quantity'])
                ->setProductUnit($this->getReference($item['unit']));
        }, $data);
    }
}
