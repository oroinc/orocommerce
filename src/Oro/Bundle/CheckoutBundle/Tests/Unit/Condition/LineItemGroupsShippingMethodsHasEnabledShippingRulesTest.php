<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Condition\LineItemGroupsShippingMethodsHasEnabledShippingRules;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class LineItemGroupsShippingMethodsHasEnabledShippingRulesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsProvider;

    /** @var GroupedLineItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupingService;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var LineItemGroupsShippingMethodsHasEnabledShippingRules */
    private $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->groupingService = $this->createMock(GroupedLineItemsProviderInterface::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);

        $this->condition = new LineItemGroupsShippingMethodsHasEnabledShippingRules(
            $doctrine,
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

    public function testExecuteReturnsTrue(): void
    {
        $checkout = $this->getCheckoutEntity();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);
        $checkoutToGetData = new Checkout();
        $filteredLineItems = new ArrayCollection($checkout->getLineItems()->toArray());

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->expectsGetGroupedLineItems($checkout, $checkoutToGetData, $filteredLineItems);

        $this->repository->expects(self::exactly(2))
            ->method('getEnabledRulesByMethod')
            ->willReturnOnConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                [new ShippingMethodsConfigsRule()]
            );

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        self::assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteReturnsFalse(): void
    {
        $checkout = $this->getCheckoutEntity();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method2', 'type' => 'type2']
        ]);
        $checkoutToGetData = new Checkout();
        $filteredLineItems = new ArrayCollection($checkout->getLineItems()->toArray());

        $this->expectsGetGroupedLineItems($checkout, $checkoutToGetData, $filteredLineItems);

        $this->repository->expects(self::exactly(2))
            ->method('getEnabledRulesByMethod')
            ->willReturnOnConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                []
            );

        $this->condition->initialize(['entity' => $checkout]);
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testExecuteWithSameLineItemGroupsShippingMethods(): void
    {
        $checkout = $this->getCheckoutEntity();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1'],
            'product.category:2' => ['method' => 'method1', 'type' => 'type2']
        ]);
        $checkoutToGetData = new Checkout();
        $filteredLineItems = new ArrayCollection($checkout->getLineItems()->toArray());

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->expectsGetGroupedLineItems($checkout, $checkoutToGetData, $filteredLineItems);

        $this->repository->expects(self::once())
            ->method('getEnabledRulesByMethod')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        self::assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteWithLineItemGroupThatDoesNotHaveShippingMethod(): void
    {
        $checkout = $this->getCheckoutEntity();
        $checkout->setLineItemGroupShippingData([
            'product.category:1' => ['method' => 'method1', 'type' => 'type1']
        ]);
        $checkoutToGetData = new Checkout();
        $filteredLineItems = new ArrayCollection($checkout->getLineItems()->toArray());

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->expectsGetGroupedLineItems($checkout, $checkoutToGetData, $filteredLineItems);

        $this->repository->expects(self::once())
            ->method('getEnabledRulesByMethod')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        self::assertFalse($this->condition->evaluate([]));
    }

    /**
     * @dataProvider getSuccessfulInitializeData
     */
    public function testInitializeSuccess(array $options): void
    {
        self::assertSame(
            $this->condition,
            $this->condition->initialize($options)
        );
    }

    public function getSuccessfulInitializeData(): array
    {
        return [
            'Options with key "entity"' => [
                ['entity' => new Checkout()],
            ],
            'Options with the single item' => [
                [new Checkout()]
            ]
        ];
    }

    public function testInitializeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "entity" option');

        $this->condition->initialize([]);
    }

    public function testGetName(): void
    {
        self::assertEquals(
            'line_item_groups_shipping_methods_has_enabled_shipping_rules',
            $this->condition->getName()
        );
    }

    public function testToArray(): void
    {
        $entity = new \stdClass();
        $this->condition->initialize([$entity]);
        $result = $this->condition->toArray();

        $key = '@line_item_groups_shipping_methods_has_enabled_shipping_rules';

        self::assertIsArray($result);
        self::assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        self::assertIsArray($resultSection);
        self::assertArrayHasKey('parameters', $resultSection);
        self::assertContains($entity, $resultSection['parameters']);
    }

    public function testCompile(): void
    {
        $stdClass = new ToStringStub();
        $options = ['entity' => $stdClass];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        self::assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                'line_item_groups_shipping_methods_has_enabled_shipping_rules',
                $stdClass
            ),
            $result
        );
    }
}
