<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\OrderLimitLayoutProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderLimitLayoutProviderTest extends TestCase
{
    private OrderLimitProviderInterface|MockObject $shoppingListLimitProvider;
    private OrderLimitFormattedProviderInterface|MockObject $shoppingListLimitFormattedProvider;
    private TranslatorInterface|MockObject $translator;
    private OrderLimitLayoutProvider $provider;

    protected function setUp(): void
    {
        $this->shoppingListLimitProvider = $this->createMock(OrderLimitProviderInterface::class);
        $this->shoppingListLimitFormattedProvider = $this->createMock(OrderLimitFormattedProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new OrderLimitLayoutProvider(
            $this->shoppingListLimitProvider,
            $this->shoppingListLimitFormattedProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider isOrderLimitsMetProvider
     */
    public function testIsOrderLimitsMet(
        bool $isMinimumOrderAmountMet,
        bool $isMaximumOrderAmountMet,
        bool $expected
    ): void {
        $shoppingList = new ShoppingList();

        $this->shoppingListLimitProvider->expects($this->any())
            ->method('isMinimumOrderAmountMet')
            ->willReturn($isMinimumOrderAmountMet);

        $this->shoppingListLimitProvider->expects($this->any())
            ->method('isMaximumOrderAmountMet')
            ->willReturn($isMaximumOrderAmountMet);

        $this->assertEquals($expected, $this->provider->isOrderLimitsMet($shoppingList));
    }

    public function isOrderLimitsMetProvider(): array
    {
        return [
            'minimum and maximum amounts met' => [
                'isMinimumOrderAmountMet' => true,
                'isMaximumOrderAmountMet' => true,
                'expected' => true,
            ],
            'minimum amount not met' => [
                'isMinimumOrderAmountMet' => false,
                'isMaximumOrderAmountMet' => true,
                'expected' => false,
            ],
            'maximum amount not met' => [
                'isMinimumOrderAmountMet' => true,
                'isMaximumOrderAmountMet' => false,
                'expected' => false,
            ],
            'minimum and maximum amounts not met' => [
                'isMinimumOrderAmountMet' => false,
                'isMaximumOrderAmountMet' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider getErrorsProvider
     */
    public function testGetErrors(
        bool $isMinimumOrderAmountMet,
        string $minimumOrderAmountFormatted,
        string $minimumOrderAmountDifferenceFormatted,
        bool $isMaximumOrderAmountMet,
        string $maximumOrderAmountFormatted,
        string $getMaximumOrderAmountDifferenceFormatted,
        array $expected
    ): void {
        $shoppingList = new ShoppingList();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static fn (string $key, array $params) => sprintf(
                    '%s:%s',
                    $key,
                    reset($params)
                )
            );

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn($isMinimumOrderAmountMet);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMinimumOrderAmountFormatted')
            ->willReturn($minimumOrderAmountFormatted);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMinimumOrderAmountDifferenceFormatted')
            ->willReturn($minimumOrderAmountDifferenceFormatted);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn($isMaximumOrderAmountMet);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMaximumOrderAmountFormatted')
            ->willReturn($maximumOrderAmountFormatted);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMaximumOrderAmountDifferenceFormatted')
            ->willReturn($getMaximumOrderAmountDifferenceFormatted);

        $this->assertEquals($expected, $this->provider->getErrors($shoppingList));
    }

    public function getErrorsProvider(): array
    {
        return [
            'minimum and maximum amounts met' => [
                'isMinimumOrderAmountMet' => true,
                'minimumOrderAmountFormatted' => '',
                'minimumOrderAmountDifferenceFormatted' => '',
                'isMaximumOrderAmountMet' => true,
                'maximumOrderAmountFormatted' => '',
                'getMaximumOrderAmountDifferenceFormatted' => '',
                'expected' => [],
            ],
            'minimum amount not met' => [
                'isMinimumOrderAmountMet' => false,
                'minimumOrderAmountFormatted' => '$123.45',
                'minimumOrderAmountDifferenceFormatted' => '$23.50',
                'isMaximumOrderAmountMet' => true,
                'maximumOrderAmountFormatted' => '',
                'getMaximumOrderAmountDifferenceFormatted' => '',
                'expected' => [
                    [
                        'type' => 'message',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_message:$123.45'
                    ],
                    [
                        'type' => 'alert',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_alert:$23.50'
                    ],
                ],
            ],
            'maximum amount not met' => [
                'isMinimumOrderAmountMet' => true,
                'minimumOrderAmountFormatted' => '',
                'minimumOrderAmountDifferenceFormatted' => '',
                'isMaximumOrderAmountMet' => false,
                'maximumOrderAmountFormatted' => '$543.21',
                'getMaximumOrderAmountDifferenceFormatted' => '$5.32',
                'expected' => [
                    [
                        'type' => 'message',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_message:$543.21'
                    ],
                    [
                        'type' => 'alert',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_alert:$5.32'
                    ],
                ],
            ],
            'minimum and maximum amounts not met' => [
                'isMinimumOrderAmountMet' => false,
                'minimumOrderAmountFormatted' => '$123.45',
                'minimumOrderAmountDifferenceFormatted' => '$23.50',
                'isMaximumOrderAmountMet' => false,
                'maximumOrderAmountFormatted' => '$543.21',
                'getMaximumOrderAmountDifferenceFormatted' => '$5.32',
                'expected' => [
                    [
                        'type' => 'message',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_message:$123.45'
                    ],
                    [
                        'type' => 'alert',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_alert:$23.50'
                    ],
                    [
                        'type' => 'message',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_message:$543.21'
                    ],
                    [
                        'type' => 'alert',
                        'value' => 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_alert:$5.32'
                    ],
                ],
            ],
        ];
    }
}
