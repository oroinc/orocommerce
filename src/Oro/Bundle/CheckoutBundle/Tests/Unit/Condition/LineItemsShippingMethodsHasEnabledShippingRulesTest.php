<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Condition\LineItemsShippingMethodsHasEnabledShippingRules;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
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

        $this->condition = new LineItemsShippingMethodsHasEnabledShippingRules(
            $this->repository,
            $this->checkoutLineItemsProvider
        );
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    private function getCheckoutEntity(
        ?string $shippingMethod1 = 'flat_rate_1',
        ?string $shippingMethod2 = 'flat_rate_2'
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

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2]));

        return $checkout;
    }

    public function testExecuteReturnsTrue()
    {
        $checkout = $this->getCheckoutEntity();

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->repository->expects($this->exactly(2))
            ->method('getEnabledRulesByMethod')
            ->willReturnOnConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                [new ShippingMethodsConfigsRule()]
            );

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteReturnsFalse()
    {
        $checkout = $this->getCheckoutEntity();

        $this->repository->expects($this->exactly(2))
            ->method('getEnabledRulesByMethod')
            ->willReturnOnConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                []
            );

        $this->condition->initialize(['entity' => $checkout]);
        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testExecuteWithSameLineItemsShippingMethods()
    {
        $checkout = $this->getCheckoutEntity('flat_rate_2');

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->repository->expects($this->once())
            ->method('getEnabledRulesByMethod')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteWithLineItemThatDoesNotHaveShippingMethod()
    {
        $checkout = $this->getCheckoutEntity('flat_rate_1', null);

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->repository->expects($this->once())
            ->method('getEnabledRulesByMethod')
            ->willReturn([new ShippingMethodsConfigsRule()]);

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        $this->assertFalse($this->condition->evaluate([]));
    }

    /**
     * @dataProvider getSuccessfulInitializeData
     */
    public function testInitializeSuccess(array $options)
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
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

    public function testInitializeThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "entity" option');

        $this->condition->initialize([]);
    }

    public function testToArray()
    {
        $entity = new \stdClass();
        $this->condition->initialize([$entity]);
        $result = $this->condition->toArray();

        $key = '@line_items_shipping_methods_has_enabled_shipping_rules';

        $this->assertIsArray($result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($entity, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $stdClass = new ToStringStub();
        $options = ['entity' => $stdClass];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                'line_items_shipping_methods_has_enabled_shipping_rules',
                $stdClass
            ),
            $result
        );
    }
}
