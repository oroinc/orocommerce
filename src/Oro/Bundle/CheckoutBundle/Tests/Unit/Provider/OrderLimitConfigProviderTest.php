<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitConfigProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLimitConfigProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private CurrencyProviderInterface|MockObject $currencyProvider;
    private OrderLimitConfigProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);

        $this->provider = new OrderLimitConfigProvider(
            $this->configManager,
            $this->currencyProvider
        );
    }

    public function testGetMinimumOrderAmount(): void
    {
        $this->currencyProvider->expects($this->exactly(2))
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'USD',
            ]);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_checkout.minimum_order_amount'],
                ['oro_checkout.maximum_order_amount'],
            )
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'currency' => 'EUR',
                        'value' => '112.34',
                    ],
                    [
                        'currency' => 'USD',
                        'value' => '123.45',
                    ],
                ],
                [
                    [
                        'currency' => 'EUR',
                        'value' => '532.11',
                    ],
                    [
                        'currency' => 'USD',
                        'value' => '543.21',
                    ],
                ],
            );

        $this->assertSame(123.45, $this->provider->getMinimumOrderAmount('USD'));
        $this->assertSame(543.21, $this->provider->getMaximumOrderAmount('USD'));
    }

    public function testGetMinimumOrderAmountEmptyEnabledCurrencies(): void
    {
        $this->currencyProvider->expects($this->exactly(2))
            ->method('getCurrencyList')
            ->willReturn([]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertNull($this->provider->getMinimumOrderAmount('USD'));
        $this->assertNull($this->provider->getMaximumOrderAmount('USD'));
    }

    public function testGetMinimumOrderAmountEmptyCurrentCurrency(): void
    {
        $this->currencyProvider->expects($this->exactly(2))
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'USD',
            ]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertNull($this->provider->getMinimumOrderAmount(''));
        $this->assertNull($this->provider->getMaximumOrderAmount(''));
    }

    public function testGetMinimumOrderAmountCurrentCurrencyNotInEnabledCurrencies(): void
    {
        $this->currencyProvider->expects($this->exactly(2))
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'USD',
            ]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->assertNull($this->provider->getMinimumOrderAmount('UAH'));
        $this->assertNull($this->provider->getMaximumOrderAmount('UAH'));
    }

    public function testGetMinimumOrderAmountEmptyOrderLimitConfig(): void
    {
        $this->currencyProvider->expects($this->exactly(2))
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'USD',
            ]);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_checkout.minimum_order_amount'],
                ['oro_checkout.maximum_order_amount'],
            )
            ->willReturnOnConsecutiveCalls(null, null);

        $this->assertNull($this->provider->getMinimumOrderAmount('USD'));
        $this->assertNull($this->provider->getMaximumOrderAmount('USD'));
    }
}
