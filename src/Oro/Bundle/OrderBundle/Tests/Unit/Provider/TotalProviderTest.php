<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalProviderTest extends \PHPUnit\Framework\TestCase
{
    private TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject $processorProvider;

    private TotalProvider $provider;

    private RateConverterInterface $rateConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);

        $currencyProvider = $this->createMock(DefaultCurrencyProviderInterface::class);
        $currencyProvider->expects(self::any())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        $this->provider = new TotalProvider(
            $this->processorProvider,
            $currencyProvider,
            $this->rateConverter
        );
    }

    /**
     * @dataProvider subtotalsProvider
     */
    public function testGetTotalFromOrderWithSubtotalsWithBaseCurrencyValues(
        array $original,
        array $expectedResult
    ): void {
        $order = $this->prepareOrder($original);
        $total = $this->getTotal($original);
        $subtotals = $this->getSubTotalCollection($original);

        $this->processorProvider->expects(self::once())
            ->method('getTotalFromOrder')
            ->with($order)
            ->willReturn($total);

        $this->processorProvider->expects(self::once())
            ->method('getSubtotals')
            ->with($order)
            ->willReturn($subtotals);

        $totals = $this->provider->getTotalFromOrderWithSubtotalsWithBaseCurrencyValues($order);
        self::assertIsArray($totals);
        self::assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        self::assertEquals(
            $totals[TotalProcessorProvider::TYPE],
            $expectedResult[TotalProcessorProvider::TYPE]
        );
        self::assertArrayHasKey(TotalProcessorProvider::SUBTOTALS, $totals);
        self::assertEquals(
            $totals[TotalProcessorProvider::SUBTOTALS],
            $expectedResult[TotalProcessorProvider::SUBTOTALS]
        );
    }

    /**
     * @dataProvider subtotalsProvider
     */
    public function testGetTotalWithSubtotalsWithBaseCurrencyValues(array $original, array $expectedResult): void
    {
        $order = $this->prepareOrder($original);
        $total = $this->getTotal($original);
        $subtotals = $this->getSubTotalCollection($original);

        $this->processorProvider->expects(self::once())
            ->method('getTotalForSubtotals')
            ->with($order, $subtotals)
            ->willReturn($total);

        $this->processorProvider->expects(self::once())
            ->method('getSubtotals')
            ->with($order)
            ->willReturn($subtotals);

        $totals = $this->provider->getTotalWithSubtotalsWithBaseCurrencyValues($order);
        self::assertIsArray($totals);
        self::assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        self::assertEquals(
            $totals[TotalProcessorProvider::TYPE],
            $expectedResult[TotalProcessorProvider::TYPE]
        );
        self::assertArrayHasKey(TotalProcessorProvider::SUBTOTALS, $totals);
        self::assertEquals(
            $totals[TotalProcessorProvider::SUBTOTALS],
            $expectedResult[TotalProcessorProvider::SUBTOTALS]
        );
    }

    public function subtotalsProvider(): array
    {
        return [
            'set with totals not in base currency' => [
                'original' => [
                    TotalProcessorProvider::TYPE => [
                        'amount' => 5826.97,
                        'base_amount' => 6700,
                        'currency' => 'EUR'
                    ],
                    TotalProcessorProvider::SUBTOTALS => [
                        'amount' => 5336.68,
                        'base_amount' => 6300,
                        'currency' => 'EUR'
                    ]
                ],
                'expected' => [
                    TotalProcessorProvider::TYPE => [
                        'type' => 'total',
                        'label' => 'Total',
                        'amount' => 5826.97,
                        'signedAmount' => 5826.97,
                        'currency' => 'EUR',
                        'visible' => null,
                        'data' => [
                            'baseAmount' => 6700,
                            'baseCurrency' => 'USD'
                        ],
                    ],
                    TotalProcessorProvider::SUBTOTALS => [
                        [
                            'type' => 'subtotal',
                            'label' => 'Subtotal',
                            'amount' => 5336.68,
                            'signedAmount' => 5336.68,
                            'currency' => 'EUR',
                            'visible' => null,
                            'data' => [
                                'baseAmount' => 6300,
                                'baseCurrency' => 'USD'
                            ],
                        ]
                    ]
                ]
            ],
            'set with totals in base currency' => [
                'original' => [
                    TotalProcessorProvider::TYPE => [
                        'amount' => 5826.97,
                        'base_amount' => 5826.97,
                        'currency' => 'USD'
                    ],
                    TotalProcessorProvider::SUBTOTALS => [
                        'amount' => 5336.68,
                        'base_amount' => 5336.68,
                        'currency' => 'USD'
                    ]
                ],
                'expected' => [
                    TotalProcessorProvider::TYPE => [
                        'type' => 'total',
                        'label' => 'Total',
                        'amount' => 5826.97,
                        'signedAmount' => 5826.97,
                        'currency' => 'USD',
                        'visible' => null,
                        'data' => null,
                    ],
                    TotalProcessorProvider::SUBTOTALS => [
                        [
                            'type' => 'subtotal',
                            'label' => 'Subtotal',
                            'amount' => 5336.68,
                            'signedAmount' => 5336.68,
                            'currency' => 'USD',
                            'visible' => null,
                            'data' => null,
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getTotal(array $original): Subtotal
    {
        $total = new Subtotal();
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($original[TotalProcessorProvider::TYPE]['amount']);
        $total->setCurrency($original[TotalProcessorProvider::TYPE]['currency']);
        $total->setOperation(Subtotal::OPERATION_ADD);
        $total->setLabel('Total');

        return $total;
    }

    private function getSubTotalCollection(array $original): ArrayCollection
    {
        $subtotal = new Subtotal();
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($original[TotalProcessorProvider::SUBTOTALS]['amount']);
        $subtotal->setCurrency($original[TotalProcessorProvider::SUBTOTALS]['currency']);
        $subtotal->setOperation(Subtotal::OPERATION_ADD);
        $subtotal->setLabel('Subtotal');

        return new ArrayCollection([$subtotal]);
    }

    private function prepareOrder(array $original): Order
    {
        $order = $this->createMock(Order::class);
        $order->expects(self::any())
            ->method('getBaseTotalValue')
            ->willReturn($original[TotalProcessorProvider::TYPE]['base_amount']);
        $order->expects(self::any())
            ->method('getBaseSubtotalValue')
            ->willReturn($original[TotalProcessorProvider::SUBTOTALS]['base_amount']);

        return $order;
    }
}
