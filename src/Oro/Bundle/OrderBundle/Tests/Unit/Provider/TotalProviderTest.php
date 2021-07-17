<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;

class TotalProviderTest extends AbstractSubtotalProviderTest
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SubtotalProviderRegistry
     */
    protected $subtotalProviderRegistry;

    /**
     * @var TotalProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TotalProcessorProvider
     */
    protected $processorProvider;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|RateConverterInterface */
    protected $rateConverter;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|DefaultCurrencyProviderInterface */
    protected $currencyProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processorProvider =
            $this->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyProvider = $this
            ->createMock('Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface');
        $this->currencyProvider
            ->expects($this->any())
            ->method('getDefaultCurrency')
            ->willReturn('USD');
        $this->rateConverter = $this->createMock('Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface');

        $this->provider = new TotalProvider(
            $this->processorProvider,
            $this->currencyProvider,
            $this->rateConverter
        );
    }

    protected function tearDown(): void
    {
        unset($this->provider);
    }

    /**
     * @dataProvider subtotalsProvider
     */
    public function testGetTotalWithSubtotalsWithBaseCurrencyValues($original, $expectedResult)
    {
        $order = $this->createMock('Oro\Bundle\OrderBundle\Entity\Order');
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

        $this->processorProvider
            ->expects($this->once())
            ->method('getTotalForSubtotals')
            ->with($order, $subtotals)
            ->willReturn($total);

        $this->processorProvider
            ->expects($this->once())
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

    public function subtotalsProvider()
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
