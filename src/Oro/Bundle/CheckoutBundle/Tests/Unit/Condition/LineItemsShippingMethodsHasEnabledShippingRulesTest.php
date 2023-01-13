<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Condition\LineItemsShippingMethodsHasEnabledShippingRules;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class LineItemsShippingMethodsHasEnabledShippingRulesTest extends TestCase
{
    use EntityTrait;

    private ShippingMethodsConfigsRuleRepository|MockObject $repository;
    private CheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;
    private ContextAccessorInterface|MockObject $contextAccessor;
    private LineItemsShippingMethodsHasEnabledShippingRules $condition;

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

    public function testExecuteReturnsTrue()
    {
        $checkout = $this->getCheckoutEntity();

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->repository->expects($this->exactly(2))
            ->method('getEnabledRulesByMethod')
            ->will($this->onConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                [new ShippingMethodsConfigsRule()]
            ));

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    public function testExecuteReturnsFalse()
    {
        $checkout = $this->getCheckoutEntity();

        $this->repository->expects($this->exactly(2))
            ->method('getEnabledRulesByMethod')
            ->will($this->onConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
                []
            ));

        $this->condition->initialize(['entity' => $checkout]);
        $this->assertFalse($this->condition->evaluate([]));
    }

    public function testExecuteWithTheSameLineItemsShippingMethods()
    {
        $checkout = $this->getCheckoutEntity('flat_rate_2');

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->willReturn($checkout);

        $this->repository->expects($this->once())
            ->method('getEnabledRulesByMethod')
            ->will($this->onConsecutiveCalls(
                [new ShippingMethodsConfigsRule()],
            ));

        $this->condition->initialize(['entity' => new PropertyPath('entity')]);
        $this->assertTrue($this->condition->evaluate([]));
    }

    /**
     * @param array $options
     * @dataProvider getSuccessfulInitializeData
     */
    public function testInitializeSuccess($options)
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
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
        $entity = $stdClass = new \stdClass();
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

    private function getCheckoutEntity(
        string $shippingMethod1 = 'flat_rate_1',
        string $shippingMethod2 = 'flat_rate_2'
    ): Checkout {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, 1);
        $lineItem->setShippingMethod($shippingMethod1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, 2);
        $lineItem2->setShippingMethod($shippingMethod2);

        $checkout = new Checkout();
        $checkout->addLineItem($lineItem);
        $checkout->addLineItem($lineItem2);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn(new ArrayCollection([$lineItem, $lineItem2]));

        return $checkout;
    }
}
