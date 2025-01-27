<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLimitProviderTest extends TestCase
{
    private OrderLimitConfigProvider|MockObject $orderLimitConfigProvider;
    private SubtotalProviderInterface|MockObject $subtotalProvider;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private OrderLimitProvider $provider;

    protected function setUp(): void
    {
        $this->orderLimitConfigProvider = $this->createMock(OrderLimitConfigProvider::class);
        $this->subtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new OrderLimitProvider(
            $this->orderLimitConfigProvider,
            $this->subtotalProvider,
            $this->userCurrencyManager
        );
    }

    /**
     * @dataProvider isMinimumOrderAmountMetProvider
     */
    public function testIsMinimumOrderAmountMet(
        ?string $userCurrency,
        ?float $minimumOrderAmount,
        float $orderAmount,
        bool $expected
    ): void {
        $shoppingList = new ShoppingList();

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($userCurrency);

        $this->orderLimitConfigProvider->expects($this->any())
            ->method('getMinimumOrderAmount')
            ->with($userCurrency)
            ->willReturn($minimumOrderAmount);

        $subtotal = new Subtotal();
        $subtotal->setAmount($orderAmount);

        $this->subtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertSame($expected, $this->provider->isMinimumOrderAmountMet($shoppingList));
    }

    public function isMinimumOrderAmountMetProvider(): array
    {
        return [
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => null,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 0,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => null,
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 0.50,
                'expected' => false,
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 0,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isMaximumOrderAmountMetProvider
     */
    public function testIsMaximumOrderAmountMet(
        ?string $userCurrency,
        ?float $maximumOrderAmount,
        float $orderAmount,
        bool $expected
    ): void {
        $shoppingList = new ShoppingList();

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($userCurrency);

        $this->orderLimitConfigProvider->expects($this->any())
            ->method('getMaximumOrderAmount')
            ->with($userCurrency)
            ->willReturn($maximumOrderAmount);

        $subtotal = new Subtotal();
        $subtotal->setAmount($orderAmount);

        $this->subtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->assertSame($expected, $this->provider->isMaximumOrderAmountMet($shoppingList));
    }

    public function isMaximumOrderAmountMetProvider(): array
    {
        return [
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => null,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 0,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 100.25,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => null,
                'maximumOrderAmount' => 100.25,
                'orderAmount' => 10.50,
                'expected' => true,
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 100.25,
                'orderAmount' => 100.50,
                'expected' => false,
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 100.25,
                'orderAmount' => 0,
                'expected' => true,
            ],
        ];
    }
}
