<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ShippingMethodsListener;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodsConfigsRulesProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsListenerTest extends AbstractMethodsListenerTest
{
    use EntityTrait;

    /**
     * @var ShippingMethodsConfigsRulesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configsRuleProvider;

    /**
     * @var CheckoutShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextFactory;

    protected function setUp()
    {
        $this->configsRuleProvider = $this->getMockBuilder(ShippingMethodsConfigsRulesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextFactory = $this->getMockBuilder(CheckoutShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager
    ) {
        return new ShippingMethodsListener(
            $addressProvider,
            $orderAddressSecurityProvider,
            $orderAddressManager,
            $this->configsRuleProvider,
            $this->contextFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAddressType()
    {
        return AddressType::TYPE_SHIPPING;
    }

    /**
     * {@inheritdoc}
     */
    protected function expectsHasMethodsConfigsWithoutAddressInvocation(array $willReturnConfigs)
    {
        $context = $this->createMock(ShippingContextInterface::class);
        $this->contextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Checkout::class))
            ->willReturn($context);

        $this->configsRuleProvider
            ->expects($this->once())
            ->method('getAllFilteredShippingMethodsConfigs')
            ->with($context)
            ->willReturn($willReturnConfigs);
    }

    /**
     * {@inheritdoc}
     */
    protected function expectsHasMethodsConfigsForAddressesInvocation(
        $expectedCalls,
        array $willReturnConfigsOnConsecutiveCalls
    ) {
        $shippingContext = $this->createMock(ShippingContextInterface::class);

        $this->contextFactory
            ->expects($this->exactly($expectedCalls))
            ->method('create')
            ->with($this->callback(function (Checkout $checkout) {
                $this->assertInstanceOf(OrderAddress::class, $checkout->getShippingAddress());

                return $checkout instanceof Checkout;
            }))
            ->willReturn($shippingContext);

        $this->configsRuleProvider
            ->expects($this->exactly($expectedCalls))
            ->method('getAllFilteredShippingMethodsConfigs')
            ->with($shippingContext)
            ->willReturnOnConsecutiveCalls(...$willReturnConfigsOnConsecutiveCalls);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMethodConfig(array $params)
    {
        return $this->getEntity(ShippingMethodsConfigsRule::class, $params);
    }
}
