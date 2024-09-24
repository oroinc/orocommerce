<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Condition\IsLineItemGroupsShippingMethodsUpdateRequired;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class IsLineItemGroupsShippingMethodsUpdateRequiredTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsProvider;

    /** @var GroupedLineItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupingService;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var IsLineItemGroupsShippingMethodsUpdateRequired */
    private $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->groupingService = $this->createMock(GroupedLineItemsProviderInterface::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->condition = new IsLineItemGroupsShippingMethodsUpdateRequired(
            $this->checkoutLineItemsProvider,
            $this->groupingService,
            $this->checkoutFactory
        );
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    private function getCheckoutEntity(): Checkout
    {
        $lineItem1 = new CheckoutLineItem();
        $lineItem2 = new CheckoutLineItem();

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);

        return $checkout;
    }

    private function expectsGetGroupedLineItems(
        Checkout $checkout,
        Checkout $checkoutToGetData,
        ArrayCollection $filteredLineItems
    ): void {
        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with(self::identicalTo($checkout))
            ->willReturn($filteredLineItems);
        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), self::identicalTo($filteredLineItems))
            ->willReturn($checkoutToGetData);
        $this->groupingService->expects(self::once())
            ->method('getGroupedLineItems')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn([
                'product.category:1' => [$filteredLineItems->first()],
                'product.category:2' => [$filteredLineItems->last()]
            ]);
    }

    public function testConditionReturnTrue(): void
    {
        $checkout = $this->getCheckoutEntity();
        $checkoutToGetData = new Checkout();
        $filteredLineItems = new ArrayCollection($checkout->getLineItems()->toArray());

        $lineItemGroupsShippingData = [
            'product.category:1' => ['method' => 'method1', 'type' => 'type1']
        ];

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($lineItemGroupsShippingData, $checkout);

        $this->expectsGetGroupedLineItems($checkout, $checkoutToGetData, $filteredLineItems);

        $this->condition->initialize([
            'entity' => new PropertyPath('entity'),
            'line_item_groups_shipping_data' => new PropertyPath('line_item_groups_shipping_data')
        ]);

        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testConditionReturnFalseWhenLineItemGroupsShippingDataEmpty(): void
    {
        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn([]);

        $this->condition->initialize([
            'entity' => new PropertyPath('entity'),
            'line_item_groups_shipping_data' => new PropertyPath('line_item_groups_shipping_data')
        ]);

        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testConditionReturnFalseWhenLineItemGroupsHasShippingMethods(): void
    {
        $checkout = $this->getCheckoutEntity();

        $lineItemGroupsShippingData = [
            'product.category:1' => ['method' => 'method1', 'type' => 'type1']
        ];

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls($lineItemGroupsShippingData, $checkout);

        $this->condition->initialize([
            'entity' => new PropertyPath('entity'),
            'line_item_groups_shipping_data' => new PropertyPath('line_item_groups_shipping_data')
        ]);

        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testInitializeSuccess(): void
    {
        $this->assertSame(
            $this->condition,
            $this->condition->initialize([
                new PropertyPath('entity'),
                new PropertyPath('line_item_groups_shipping_data')
            ])
        );
    }

    /**
     * @dataProvider initializeThrowsExceptionDataProvider
     */
    public function testInitializeThrowsException(array $options, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->condition->initialize($options);
    }

    public function initializeThrowsExceptionDataProvider(): array
    {
        return [
            [
                'options' => [],
                'expectedMessage' => 'Missing "entity" option'
            ],
            [
                'options' => [new PropertyPath('entity')],
                'expectedMessage' => 'Missing "line_item_groups_shipping_data" option'
            ],
            [
                'options' => ['line_item_groups_shipping_data' => new PropertyPath('line_item_groups_shipping_data')],
                'expectedMessage' => 'Missing "entity" option'
            ],
            [
                'options' => ['entity' => new PropertyPath('entity')],
                'expectedMessage' => 'Missing "line_item_groups_shipping_data" option'
            ]
        ];
    }

    public function testGetName(): void
    {
        self::assertEquals('is_line_item_groups_shipping_methods_update_required', $this->condition->getName());
    }

    public function testToArray(): void
    {
        $entity = new \stdClass();
        $lineItemGroupsShippingData = [];

        $this->condition->initialize([$entity, $lineItemGroupsShippingData]);
        $result = $this->condition->toArray();

        $key = '@is_line_item_groups_shipping_methods_update_required';

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($entity, $resultSection['parameters']);
        $this->assertContains($lineItemGroupsShippingData, $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $entity = new ToStringStub();
        $lineItemGroupsShippingData = new PropertyPath('line_item_groups_shipping_data');

        $options = [
            'entity' => $entity,
            'line_item_groups_shipping_data' => $lineItemGroupsShippingData
        ];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s, %s])',
                'is_line_item_groups_shipping_methods_update_required',
                $entity,
                "new \Oro\Component\ConfigExpression\CompiledPropertyPath("
                . "'line_item_groups_shipping_data', ['line_item_groups_shipping_data'], [false])"
            ),
            $result
        );
    }
}
