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
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;

class TotalProviderTest extends AbstractSubtotalProviderTest
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $processorProvider;

    /** @var TotalProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);

        $currencyProvider = $this->createMock(DefaultCurrencyProviderInterface::class);
        $currencyProvider->expects($this->any())
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
    public function testGetTotalWithSubtotalsWithBaseCurrencyValues(array $original, array $expectedResult)
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->any())
            ->method('getBaseTotalValue')
            ->willReturn($original[TotalProcessorProvider::TYPE]['base_amount']);
        $order->expects($this->any())
            ->method('getBaseSubtotalValue')
            ->willReturn($original[TotalProcessorProvider::SUBTOTALS]['base_amount']);

        $total = new Subtotal();
        $total->setType(TotalProcessorProvider::TYPE)
            ->setAmount($original[TotalProcessorProvider::TYPE]['amount'])
            ->setCurrency($original[TotalProcessorProvider::TYPE]['currency'])
            ->setOperation(Subtotal::OPERATION_ADD)
            ->setLabel('Total');

        $subtotal = new Subtotal();
        $subtotal->setType(LineItemSubtotalProvider::TYPE)
            ->setAmount($original[TotalProcessorProvider::SUBTOTALS]['amount'])
            ->setCurrency($original[TotalProcessorProvider::SUBTOTALS]['currency'])
            ->setOperation(Subtotal::OPERATION_ADD)
            ->setLabel('Subtotal');

        $subtotals = new ArrayCollection([$subtotal]);

        $this->processorProvider->expects($this->once())
            ->method('getTotalForSubtotals')
            ->with($order, $subtotals)
            ->willReturn($total);

        $this->processorProvider->expects($this->once())
            ->method('getSubtotals')
            ->with($order)
            ->willReturn($subtotals);

        $totals = $this->provider->getTotalWithSubtotalsWithBaseCurrencyValues($order);
        $this->assertIsArray($totals);
        $this->assertArrayHasKey(TotalProcessorProvider::TYPE, $totals);
        $this->assertEquals(
            $totals[TotalProcessorProvider::TYPE],
            $expectedResult[TotalProcessorProvider::TYPE]
        );
        $this->assertArrayHasKey(TotalProcessorProvider::SUBTOTALS, $totals);
        $this->assertEquals(
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
}
