<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\ExpressionLanguage;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
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

        $this->loadFixtures(['@OroInventoryBundle/Tests/Functional/DataFixtures/inventory_level.yml']);
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
        $factory = $this->getContainer()->get('oro_payment.expression_language.decorated_product_line_item_factory');

        $context = new PaymentContext([
            PaymentContext::FIELD_LINE_ITEMS => new DoctrinePaymentLineItemCollection(
                $this->prepareLineItems($lineItems)
            ),
            PaymentContext::FIELD_CURRENCY => 'USD'
        ]);
        $lineItems = $context->getLineItems()->toArray();

        $values = [
            'lineItems' => array_map(function (PaymentLineItemInterface $lineItem) use ($factory, $lineItems) {
                return $factory->createLineItemWithDecoratedProductByLineItem($lineItems, $lineItem);
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
    lineItem.product.inventoryLevels.any(
        inventoryLevel.productUnitPrecision.unit.code = lineItem.productUnit.code
        and
        inventoryLevel.quantity > lineItem.quantity
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
            return new PaymentLineItem([
                PaymentLineItem::FIELD_PRODUCT => $this->getReference($item['product']),
                PaymentLineItem::FIELD_QUANTITY => $item['quantity'],
                PaymentLineItem::FIELD_PRODUCT_UNIT => $this->getReference($item['unit']),
                PaymentLineItem::FIELD_PRODUCT_UNIT_CODE => $this->getReference($item['unit'])->getCode(),
            ]);
        }, $data);
    }
}
