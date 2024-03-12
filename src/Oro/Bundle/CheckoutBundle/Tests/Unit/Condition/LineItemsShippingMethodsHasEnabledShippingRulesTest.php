<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Condition\LineItemsShippingMethodsHasEnabledShippingRules;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyPath;

class LineItemsShippingMethodsHasEnabledShippingRulesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodsConfigsRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsProvider;

    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var LineItemsShippingMethodsHasEnabledShippingRules */
    private $condition;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShippingMethodsConfigsRuleRepository::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ShippingMethodsConfigsRule::class)
            ->willReturn($this->repository);

        $this->condition = new LineItemsShippingMethodsHasEnabledShippingRules(
            $doctrine,
            $this->checkoutLineItemsProvider
        );
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    private function getCheckoutEntity(
        ?string $shippingMethod1 = 'method1',
        ?string $shippingMethod2 = 'method2'
    ): Checkout {
        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);
        $lineItem1->setShippingMethod($shippingMethod1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);
        $lineItem2->setShippingMethod($shippingMethod2);

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);

        return $checkout;
    }

    public function testExecuteReturnsTrue(): void
    {
        $checkout = $this->getCheckoutEntity();

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection($checkout->getLineItems()->toArray()));

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

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection($checkout->getLineItems()->toArray()));

        $this->repository->expects(self::exactly(2))
            ->method('getEnabledRulesByMethod')
            ->willReturnOnConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                []
            );

        $this->condition->initialize(['entity' => $checkout]);
        self::assertFalse($this->condition->evaluate([]));
    }

    public function testExecuteWithSameLineItemsShippingMethods(): void
    {
        $checkout = $this->getCheckoutEntity('method2');

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection($checkout->getLineItems()->toArray()));

        $this->repository->expects(self::once())
            ->method('getEnabledRulesByMethod')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        self::assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteWithLineItemThatDoesNotHaveShippingMethod(): void
    {
        $checkout = $this->getCheckoutEntity('method1', null);

        $this->contextAccessor->expects(self::once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection($checkout->getLineItems()->toArray()));

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
            'line_items_shipping_methods_has_enabled_shipping_rules',
            $this->condition->getName()
        );
    }

    public function testToArray(): void
    {
        $entity = new \stdClass();
        $this->condition->initialize([$entity]);
        $result = $this->condition->toArray();

        $key = '@line_items_shipping_methods_has_enabled_shipping_rules';

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
                'line_items_shipping_methods_has_enabled_shipping_rules',
                $stdClass
            ),
            $result
        );
    }
}
