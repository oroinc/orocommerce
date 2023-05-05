<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodChecker;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class AvailableShippingMethodCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodsConfigsRulesProvider;

    /** @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextProvider;

    /** @var AvailableShippingMethodChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->shippingMethodsConfigsRulesProvider = $this->createMock(
            MethodsConfigsRulesByContextProviderInterface::class
        );
        $this->checkoutShippingContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->checker = new AvailableShippingMethodChecker(
            $this->shippingMethodsConfigsRulesProvider,
            $this->checkoutShippingContextProvider
        );
    }

    public function testHasAvailableShippingMethodsWhenNoAvailableShippingMethods(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $context = $this->createMock(ShippingContextInterface::class);

        $this->checkoutShippingContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($context);
        $this->shippingMethodsConfigsRulesProvider->expects(self::once())
            ->method('getShippingMethodsConfigsRules')
            ->with(self::identicalTo($context))
            ->willReturn([]);

        self::assertFalse($this->checker->hasAvailableShippingMethods($checkout));
    }

    public function testHasAvailableShippingMethodsWhenAvailableShippingMethodsExist(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $context = $this->createMock(ShippingContextInterface::class);

        $this->checkoutShippingContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($context);
        $this->shippingMethodsConfigsRulesProvider->expects(self::once())
            ->method('getShippingMethodsConfigsRules')
            ->with(self::identicalTo($context))
            ->willReturn([$this->createMock(ShippingMethodsConfigsRule::class)]);

        self::assertTrue($this->checker->hasAvailableShippingMethods($checkout));
    }
}
