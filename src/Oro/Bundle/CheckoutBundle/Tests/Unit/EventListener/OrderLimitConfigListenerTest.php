<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\OrderLimitConfigListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLimitConfigListenerTest extends TestCase
{
    private CurrencyProviderInterface|MockObject $currencyProvider;
    private ConfigManager|MockObject $configManager;
    private OrderLimitConfigListener $listener;

    protected function setUp(): void
    {
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new OrderLimitConfigListener(
            $this->currencyProvider,
            'oro_checkout.minimum_order_amount'
        );
    }

    public function testOnFormPreSetDataNoSettingsKey(): void
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->currencyProvider->expects($this->never())
            ->method('getCurrencyList');

        $this->listener->onFormPreSetData($event);

        self::assertEquals([], $event->getSettings());
    }

    public function testOnFormPreSetData(): void
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, [
            'oro_checkout___minimum_order_amount' => [
                'value' => [
                    [
                        'currency' => 'USD',
                        'value' => '123.45',
                    ],
                    [
                        'currency' => 'EUR',
                        'value' => '112.34',
                    ],
                ]
            ]
        ]);

        $this->currencyProvider->expects($this->once())
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'UAH',
                'USD',
            ]);

        $this->listener->onFormPreSetData($event);

        self::assertEquals([
            'oro_checkout___minimum_order_amount' => [
                'value' => [
                    [
                        'value' => '112.34',
                        'currency' => 'EUR',
                    ],
                    [
                        'value' => '',
                        'currency' => 'UAH',
                    ],
                    [
                        'value' => '123.45',
                        'currency' => 'USD',
                    ],
                ]
            ]
        ], $event->getSettings());
    }

    public function testOnFormPreSetDataWithEmptyConfig(): void
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, [
            'oro_checkout___minimum_order_amount' => [
                'value' => []
            ]
        ]);

        $this->currencyProvider->expects($this->once())
            ->method('getCurrencyList')
            ->willReturn([
                'EUR',
                'UAH',
                'USD',
            ]);

        $this->listener->onFormPreSetData($event);

        self::assertEquals([
            'oro_checkout___minimum_order_amount' => [
                'value' => [
                    [
                        'value' => '',
                        'currency' => 'EUR',
                    ],
                    [
                        'value' => '',
                        'currency' => 'UAH',
                    ],
                    [
                        'value' => '',
                        'currency' => 'USD',
                    ],
                ]
            ]
        ], $event->getSettings());
    }

    public function testOnFormPreSetDataWithEmptyCurrencyList(): void
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, [
            'oro_checkout___minimum_order_amount' => [
                'value' => [
                    [
                        'currency' => 'USD',
                        'value' => '123.45',
                    ],
                    [
                        'currency' => 'EUR',
                        'value' => '112.34',
                    ],
                ]
            ]
        ]);

        $this->currencyProvider->expects($this->once())
            ->method('getCurrencyList')
            ->willReturn([]);

        $this->listener->onFormPreSetData($event);

        self::assertEquals([
            'oro_checkout___minimum_order_amount' => [
                'value' => []
            ]
        ], $event->getSettings());
    }
}
