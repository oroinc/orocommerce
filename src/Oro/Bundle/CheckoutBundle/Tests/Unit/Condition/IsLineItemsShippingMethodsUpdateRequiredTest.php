<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Condition\IsLineItemsShippingMethodsUpdateRequired;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsLineItemsShippingMethodsUpdateRequiredTest extends TestCase
{
    use EntityTrait;

    private ContextAccessorInterface|MockObject $contextAccessor;
    private IsLineItemsShippingMethodsUpdateRequired $condition;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $this->condition = new IsLineItemsShippingMethodsUpdateRequired();
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    public function testConditionReturnTrue()
    {
        $lineItemsShippingData = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ]
        ];

        $lineItems = new ArrayCollection([new CheckoutLineItem()]);

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($lineItemsShippingData, $lineItems);

        $this->condition->initialize([
            'line_items' => new PropertyPath('line_items'),
            'line_items_shipping_data' => new PropertyPath('line_items_shipping_data')
        ]);

        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testConditionReturnFalseIfLineItemsDataEmpty()
    {
        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn([]);

        $this->condition->initialize([
            'line_items' => new PropertyPath('line_items'),
            'line_items_shipping_data' => new PropertyPath('line_items_shipping_data')
        ]);

        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testConditionReturnFalseWhenLineItemsHasShippingMethods()
    {
        $lineItem = $this->getEntity(CheckoutLineItem::class, [
            'shippingMethod' => 'SHIPPING_METHOD',
            'shippingMethodType' => 'SHIPPING_METHOD_TYPE'
        ]);

        $lineItems = new ArrayCollection([$lineItem]);

        $lineItemsShippingData = [
            'sku-1:item' => [
                'method' => 'SHIPPING_METHOD',
                'type' => 'SHIPPING_METHOD_TYPE'
            ]
        ];

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($lineItemsShippingData, $lineItems);

        $this->condition->initialize([
            'line_items' => new PropertyPath('line_items'),
            'line_items_shipping_data' => new PropertyPath('line_items_shipping_data')
        ]);

        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testInitializeSuccess()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([new PropertyPath('line_items'), new PropertyPath('line_items_shipping_data')])
        );
    }

    /**
     * @param $options
     * @param $expectedMessage
     * @dataProvider getTestInitializeThrowsExceptionData
     */
    public function testInitializeThrowsException($options, $expectedMessage)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize($options)
        );
    }

    public function getTestInitializeThrowsExceptionData()
    {
        return [
            [
                'options' => [],
                'expectedMessage' => 'Missing "line_items" option'
            ],
            [
                'options' => [new PropertyPath('line_items')],
                'expectedMessage' => 'Missing "line_items_shipping_data" option'
            ],
            [
                'options' => ['line_items_shipping_data' => new PropertyPath('line_items_shipping_data')],
                'expectedMessage' => 'Missing "line_items" option'
            ],
            [
                'options' => ['line_items' => new PropertyPath('line_items')],
                'expectedMessage' => 'Missing "line_items_shipping_data" option'
            ]
        ];
    }

    public function testToArray()
    {
        $lineItems = new \stdClass();
        $lineItemsShippingData = [];

        $this->condition->initialize([$lineItems, $lineItemsShippingData]);
        $result = $this->condition->toArray();

        $key = '@is_line_items_shipping_methods_update_required';

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($lineItems, $resultSection['parameters']);
        $this->assertContains($lineItemsShippingData, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $stdClass = new ToStringStub();
        $lineItemsShippingData = new PropertyPath('line_items_shipping_data');

        $options = [
            'line_items' => $stdClass,
            'line_items_shipping_data' => $lineItemsShippingData
        ];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s, %s])',
                'is_line_items_shipping_methods_update_required',
                $stdClass,
                "new \Oro\Component\ConfigExpression\CompiledPropertyPath("
                    . "'line_items_shipping_data', ['line_items_shipping_data'], [false])"
            ),
            $result
        );
    }
}
