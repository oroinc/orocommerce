<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProvider;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLimitFormattedProviderTest extends TestCase
{
    private OrderLimitConfigProvider|MockObject $orderLimitConfigProvider;
    private SubtotalProviderInterface|MockObject $subtotalProvider;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private NumberFormatter|MockObject $numberFormatter;
    private OrderLimitFormattedProvider $provider;

    protected function setUp(): void
    {
        $this->orderLimitConfigProvider = $this->createMock(OrderLimitConfigProvider::class);
        $this->subtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->provider = new OrderLimitFormattedProvider(
            $this->orderLimitConfigProvider,
            $this->subtotalProvider,
            $this->userCurrencyManager,
            $this->numberFormatter
        );
    }

    public function testGetMinimumOrderAmountFormatted(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->orderLimitConfigProvider->expects($this->once())
            ->method('getMinimumOrderAmount')
            ->willReturn(10.5);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(10.5)
            ->willReturn('$10.50');

        $this->assertEquals('$10.50', $this->provider->getMinimumOrderAmountFormatted());
    }

    public function testGetMinimumOrderAmountFormattedEmptyCurrency(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(null);

        $this->orderLimitConfigProvider->expects($this->never())
            ->method('getMinimumOrderAmount')
            ->willReturn(null);

        $this->numberFormatter->expects($this->never())
            ->method('formatCurrency');

        $this->assertEquals('', $this->provider->getMinimumOrderAmountFormatted());
    }

    public function testGetMinimumOrderAmountFormattedEmptyOrderAmount(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->orderLimitConfigProvider->expects($this->once())
            ->method('getMinimumOrderAmount')
            ->willReturn(null);

        $this->numberFormatter->expects($this->never())
            ->method('formatCurrency');

        $this->assertEquals('', $this->provider->getMinimumOrderAmountFormatted());
    }

    /**
     * @dataProvider getMinimumOrderAmountDifferenceFormattedProvider
     */
    public function testGetMinimumOrderAmountDifferenceFormatted(
        ?string $userCurrency,
        ?float $minimumOrderAmount,
        float $orderAmount,
        ?float $expectedFloat,
        string $expectedString
    ): void {
        $order = new Order();

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

        if (null !== $expectedFloat) {
            $this->numberFormatter->expects($this->once())
                ->method('formatCurrency')
                ->with($expectedFloat)
                ->willReturn($expectedString);
        } else {
            $this->numberFormatter->expects($this->never())
                ->method('formatCurrency');
        }

        $this->assertEquals($expectedString, $this->provider->getMinimumOrderAmountDifferenceFormatted($order));
    }

    public function getMinimumOrderAmountDifferenceFormattedProvider(): array
    {
        return [
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => null,
                'orderAmount' => 10.5,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 0,
                'orderAmount' => 10.50,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 10.50,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => null,
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 0.50,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 0.50,
                'expectedFloat' => 0.75,
                'expectedString' => '$0.75',
            ],
            [
                'userCurrency' => 'USD',
                'minimumOrderAmount' => 1.25,
                'orderAmount' => 0,
                'expectedFloat' => 1.25,
                'expectedString' => '$1.25',
            ],
        ];
    }

    public function testGetMaximumOrderAmountFormatted(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->orderLimitConfigProvider->expects($this->once())
            ->method('getMaximumOrderAmount')
            ->willReturn(10.5);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(10.5)
            ->willReturn('$10.50');

        $this->assertEquals('$10.50', $this->provider->getMaximumOrderAmountFormatted());
    }

    public function testGetMaximumOrderAmountFormattedEmptyCurrency(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(null);

        $this->orderLimitConfigProvider->expects($this->never())
            ->method('getMaximumOrderAmount')
            ->willReturn(null);

        $this->numberFormatter->expects($this->never())
            ->method('formatCurrency');

        $this->assertEquals('', $this->provider->getMaximumOrderAmountFormatted());
    }

    public function testGetMaximumOrderAmountFormattedEmptyOrderAmount(): void
    {
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->orderLimitConfigProvider->expects($this->once())
            ->method('getMaximumOrderAmount')
            ->willReturn(null);

        $this->numberFormatter->expects($this->never())
            ->method('formatCurrency');

        $this->assertEquals('', $this->provider->getMaximumOrderAmountFormatted());
    }

    /**
     * @dataProvider getMaximumOrderAmountDifferenceFormattedProvider
     */
    public function testGetMaximumOrderAmountDifferenceFormatted(
        ?string $userCurrency,
        ?float $maximumOrderAmount,
        float $orderAmount,
        ?float $expectedFloat,
        string $expectedString
    ): void {
        $order = new Order();

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($userCurrency);

        $this->orderLimitConfigProvider->expects($this->any())
            ->method('getMaximumOrderAmount')
            ->willReturn($maximumOrderAmount);

        $subtotal = new Subtotal();
        $subtotal->setAmount($orderAmount);

        $this->subtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        if (null !== $expectedFloat) {
            $this->numberFormatter->expects($this->once())
                ->method('formatCurrency')
                ->with($expectedFloat)
                ->willReturn($expectedString);
        } else {
            $this->numberFormatter->expects($this->never())
                ->method('formatCurrency');
        }

        $this->assertEquals($expectedString, $this->provider->getMaximumOrderAmountDifferenceFormatted($order));
    }

    public function getMaximumOrderAmountDifferenceFormattedProvider(): array
    {
        return [
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => null,
                'orderAmount' => 10.5,
                'expectedFloat' => 10.5,
                'expectedString' => '$10.5',
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 0,
                'orderAmount' => 10.5,
                'expectedFloat' => 10.5,
                'expectedString' => '$10.5',
            ],
            [
                'userCurrency' => null,
                'maximumOrderAmount' => 1.25,
                'orderAmount' => 10.50,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 1.25,
                'orderAmount' => 10.50,
                'expectedFloat' => 9.25,
                'expectedString' => '$9.25',
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 1.25,
                'orderAmount' => 0.50,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
            [
                'userCurrency' => 'USD',
                'maximumOrderAmount' => 1.25,
                'orderAmount' => 0,
                'expectedFloat' => null,
                'expectedString' => '',
            ],
        ];
    }
}
