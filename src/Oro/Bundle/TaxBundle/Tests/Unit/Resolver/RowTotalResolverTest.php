<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;

class RowTotalResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $settingsProvider;

    /**
     * @var RoundingResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    private $roundingResolver;

    /**
     * @var RowTotalResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->roundingResolver = $this->createMock(RoundingResolver::class);

        $this->resolver = new RowTotalResolver(
            $this->settingsProvider,
            new TaxCalculator(),
            $this->roundingResolver
        );
    }

    public function testEmptyTaxRule(): void
    {
        $result = new Result();
        $amount = BigDecimal::zero();
        $resultElement = ResultElement::create('0', '0', '0', '0.00');

        $this->resolver->resolveRowTotal($result, [], $amount, 0);

        self::assertEquals($resultElement, $result->getRow());
        self::assertEquals([], $result->getTaxes());
    }

    /**
     * @dataProvider rowTotalDataProvider
     */
    public function testResolveRowTotal(
        BigDecimal $amount,
        array $taxRules,
        array $expected,
        int $quantity,
        bool $isStartCalculationWithRowTotal,
        bool $isStartCalculationOnItem = false,
        bool $isCalculateAfterPromotionsEnabled = false
    ): void {
        $result = new Result();

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationWithRowTotal')
            ->willReturn($isStartCalculationWithRowTotal);
        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationWithUnitPrice')
            ->willReturn(!$isStartCalculationWithRowTotal);

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationOnItem')
            ->willReturn($isStartCalculationOnItem);
        if ($isStartCalculationOnItem) {
            $this->roundingResolver->expects(self::atLeastOnce())
                ->method('round')
                ->with($this->isInstanceOf(TaxResultElement::class));
        } else {
            $this->roundingResolver->expects(self::never())
                ->method('round');
        }

        $this->settingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn($isCalculateAfterPromotionsEnabled);

        $this->resolver->resolveRowTotal($result, $taxRules, $amount, $quantity);
        self::assertEquals($expected['row'], $result->getRow());
        self::assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function rowTotalDataProvider(): array
    {
        $taxResult1_1 = TaxResultElement::create('city', '0.08', '39.98', '3.198400');
        $taxResult1_2 = TaxResultElement::create('region', '0.07', '39.98', '2.798600');

        $taxResult2_1 = TaxResultElement::create('city', '0.08', '39.99', '3.199200');
        $taxResult2_2 = TaxResultElement::create('region', '0.07', '39.99', '2.799300');

        $taxResult3_1 = TaxResultElement::create('city', '0.081111', '39.98', '3.242818');
        $taxResult3_2 = TaxResultElement::create('region', '0.070404', '39.98', '2.814752');

        $resultWithDiscountsIncluded = ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030');
        $resultWithDiscountsIncluded->setDiscountsIncluded(true);

        return [
            'without start calculation with row total' => [
                'amount' => BigDecimal::of('19.9949'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        $taxResult1_1,
                        $taxResult1_2,
                    ]

                ],
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'use zero tax' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('country', '0.00'),
                ],
                'expected' => [
                    'row' => ResultElement::create('19.9900', '19.99', '0.0000', '0.0000'),
                    'result' => [
                        TaxResultElement::create('country', '0.00', '19.99', '0'),
                    ]
                ],
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'use two taxes one of which is zero' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('country', '0.00'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('21.3893', '19.99', '1.3993', '-0.0007'),
                    'result' => [
                        TaxResultElement::create('country', '0.00', '19.99', '0.000000'),
                        TaxResultElement::create('region', '0.07', '19.99', '1.399300'),
                    ]
                ],
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'with start calculation with row total' => [
                'amount' => BigDecimal::of('19.9949'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
                    'result' => [
                        $taxResult2_1,
                        $taxResult2_2,
                    ]

                ],
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
            ],
            'with more decimal places in tax rate' => [
                'amount' => BigDecimal::of('19.9949'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.081111'),
                    $this->getTaxRule('region', '0.070404')
                ],
                'expected' => [
                    'row' => ResultElement::create('46.03756970', '39.98', '6.05756970', '-0.00243030'),
                    'result' => [
                        $taxResult3_1,
                        $taxResult3_2,
                    ]

                ],
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'with start calculation on item' => [
                'amount' => BigDecimal::of('19.9949'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        $taxResult1_1,
                        $taxResult1_2,
                    ]

                ],
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
            ],
            'option Calculate Taxes After Promotions is enabled' => [
                'amount' => BigDecimal::of('19.9949'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => $resultWithDiscountsIncluded,
                    'result' => [
                        $taxResult1_1,
                        $taxResult1_2,
                    ]

                ],
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
                'isCalculateAfterPromotionsEnabled' => true,
            ],
        ];
    }

    protected function getTaxRule(string $taxCode, string $taxRate): TaxRule
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax
            ->setRate($taxRate)
            ->setCode($taxCode);
        $taxRule->setTax($tax);

        return $taxRule;
    }
}
