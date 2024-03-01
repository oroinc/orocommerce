<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\IncludedTaxCalculator;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowTotalResolverTest extends TestCase
{
    private TaxationSettingsProvider|MockObject $settingsProvider;

    private RowTotalResolver $resolver;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->resolver = new RowTotalResolver(
            $this->settingsProvider,
            new TaxCalculator(),
            new RoundingResolver()
        );
    }

    /**
     * @dataProvider rowTotalDataProvider
     */
    public function testResolveRowTotal(
        Taxable $taxable,
        array $taxRules,
        array $expected,
        bool $isStartCalculationWithRowTotal,
        bool $isStartCalculationOnItem = false,
        bool $isCalculateAfterPromotionsEnabled = false,
        bool $isProductPricesIncludeTax = false,
    ): void {
        if ($isProductPricesIncludeTax) {
            $this->resolver = new RowTotalResolver(
                $this->settingsProvider,
                new IncludedTaxCalculator(),
                new RoundingResolver()
            );
        }

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationWithRowTotal')
            ->willReturn($isStartCalculationWithRowTotal);
        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationWithUnitPrice')
            ->willReturn(!$isStartCalculationWithRowTotal);
        $this->settingsProvider->expects(self::any())
            ->method('isProductPricesIncludeTax')
            ->willReturn($isProductPricesIncludeTax);
        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationOnItem')
            ->willReturn($isStartCalculationOnItem);

        $this->settingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn($isCalculateAfterPromotionsEnabled);

        $result = $taxable->getResult();
        $this->resolver->resolveRowTotal($result, $taxRules, $taxable->getPrice(), $taxable->getQuantity());
        self::assertEquals($expected['row'], $result->getRow());
        self::assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function rowTotalDataProvider(): array
    {
        $resultWithDiscountsIncluded = ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030');
        $resultWithDiscountsIncluded->setDiscountsIncluded(true);

        $kitTaxable = $this->getTaxable('19.9949', 2);
        $kitTaxable->setKitTaxable(true);

        $kitItemTaxResult1_1 = TaxResultElement::create('city', '0.08', '2.5', '0.02');
        $kitItemTaxResult1_2 = TaxResultElement::create('region', '0.07', '2.5', '0.02');

        $kitItemTaxResult2_1 = TaxResultElement::create('city', '0.08', '3.7', '0.03');
        $kitItemTaxResult2_2 = TaxResultElement::create('region', '0.07', '3.7', '0.03');

        $resultItem = new Result();
        $resultItem->offsetSet(Result::ROW, ResultElement::create('2.54', '2.5', '0.04', '0.00'));
        $resultItem->offsetSet(Result::TAXES, [$kitItemTaxResult1_1, $kitItemTaxResult1_2]);
        $resultItem2 = new Result();
        $resultItem2->offsetSet(Result::ROW, ResultElement::create('3.76', '3.7', '0.06', '0.00'));
        $resultItem2->offsetSet(Result::TAXES, [$kitItemTaxResult2_1, $kitItemTaxResult2_2]);

        $kitTaxable2 = clone $kitTaxable;
        $kitTaxable2->getResult()->offsetSet(Result::ITEMS, [$resultItem, $resultItem2]);
        $taxableItem = (new Taxable())->setResult($resultItem);
        $taxableItem2 = (new Taxable())->setResult($resultItem2);
        $kitTaxable2->addItem($taxableItem);
        $kitTaxable2->addItem($taxableItem2);

        return [
            'without start calculation with row total' => [
                'taxable' => $this->getTaxable('19.9949', 2),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.198400'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.798600')
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'empty tax rules' => [
                'taxable' => $this->getTaxable('19.99', 1),
                'taxRules' => [],
                'expected' => [
                    'row' => ResultElement::create('19.99', '19.99', '0.00', '0.00'),
                    'result' => []
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'use zero tax' => [
                'taxable' => $this->getTaxable('19.99', 1),
                'taxRules' => [
                    $this->getTaxRule('country', '0.00'),
                ],
                'expected' => [
                    'row' => ResultElement::create('19.9900', '19.99', '0.0000', '0.0000'),
                    'result' => [TaxResultElement::create('country', '0.00', '19.99', '0')]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'use two taxes one of which is zero' => [
                'taxable' => $this->getTaxable('19.99', 1),
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
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'with start calculation with row total' => [
                'taxable' => $this->getTaxable('19.9949', 2),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.99', '3.199200'),
                        TaxResultElement::create('region', '0.07', '39.99', '2.799300')
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
            ],
            'with more decimal places in tax rate' => [
                'taxable' => $this->getTaxable('19.9949', 2),
                'taxRules' => [
                    $this->getTaxRule('city', '0.081111'),
                    $this->getTaxRule('region', '0.070404')
                ],
                'expected' => [
                    'row' => ResultElement::create('46.03756970', '39.98', '6.05756970', '-0.00243030'),
                    'result' => [
                        TaxResultElement::create('city', '0.081111', '39.98', '3.242818'),
                        TaxResultElement::create('region', '0.070404', '39.98', '2.814752')
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'with start calculation on item' => [
                'taxable' => $this->getTaxable('19.9949', 2),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.2'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.8')
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
            ],
            'calculate taxes after promotions' => [
                'taxable' => $this->getTaxable('19.9949', 2),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => $resultWithDiscountsIncluded,
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.2'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.8')
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
                'isCalculateAfterPromotionsEnabled' => true,
            ],
            'kit taxable' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.198400'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.798600'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'kit taxable with zero tax' => [
                'taxable' => $this->getTaxable('0.0', 2)->setKitTaxable(true),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('0.0000', '0.00', '0.0000', '0.0000'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.00', '0.000000'),
                        TaxResultElement::create('region', '0.07', '0.00', '0.000000'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'kit taxable with enabled isStartCalculationWithRowTotal' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.99', '3.199200'),
                        TaxResultElement::create('region', '0.07', '39.99', '2.799300'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
            ],
            'kit taxable with enabled isStartCalculationOnItem' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.2'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.8'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
            ],
            'kit taxable with enabled isStartCalculationWithRowTotal and isStartCalculationOnItem' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.99', '3.2'),
                        TaxResultElement::create('region', '0.07', '39.99', '2.8'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => true,
            ],
            'kit taxable with enabled isProductPricesIncludeTax' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('39.98', '34.765217', '5.214783', '0.004783'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '34.765217', '2.781218'),
                        TaxResultElement::create('region', '0.07', '34.765217', '2.433565'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
                'isCalculateAfterPromotionsEnabled' => false,
                'isProductPricesIncludeTax' => true,
            ],
            'kit taxable with enabled isStartCalculationWithRowTotal and isProductPricesIncludeTax' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('39.99', '34.773913', '5.216087', '-0.003913'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '34.773913', '2.781913'),
                        TaxResultElement::create('region', '0.07', '34.773913', '2.434174'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
                'isCalculateAfterPromotionsEnabled' => false,
                'isProductPricesIncludeTax' => true,
            ],
            'kit taxable with enabled isStartCalculationOnItem and isProductPricesIncludeTax' => [
                'taxable' => $kitTaxable,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('39.98', '34.765217', '5.214783', '0.004783'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '34.77', '2.78'),
                        TaxResultElement::create('region', '0.07', '34.77', '2.43'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => true,
                'isCalculateAfterPromotionsEnabled' => false,
                'isProductPricesIncludeTax' => true,
            ],
            'kit taxable with kit items' => [
                'taxable' => $kitTaxable2,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9770', '39.98', '5.9970', '-0.0030'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.98', '3.198400'),
                        TaxResultElement::create('region', '0.07', '39.98', '2.798600'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
            ],
            'kit taxable with kit items and enabled isStartCalculationWithRowTotal' => [
                'taxable' => $kitTaxable2,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '39.99', '3.199200'),
                        TaxResultElement::create('region', '0.07', '39.99', '2.799300'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
            ],
            'kit taxable with kit items and enabled isProductPricesIncludeTax' => [
                'taxable' => $kitTaxable2,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('39.98', '34.765217', '5.214783', '0.004783'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '34.765217', '2.781218'),
                        TaxResultElement::create('region', '0.07', '34.765217', '2.433565'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => false,
                'isStartCalculationOnItem' => false,
                'isCalculateAfterPromotionsEnabled' => false,
                'isProductPricesIncludeTax' => true,
            ],
            'kit taxable with kit items and enabled isStartCalculationWithRowTotal, isProductPricesIncludeTax' => [
                'taxable' => $kitTaxable2,
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'row' => ResultElement::create('39.99', '34.773913', '5.216087', '-0.003913'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '34.773913', '2.781913'),
                        TaxResultElement::create('region', '0.07', '34.773913', '2.434174'),
                    ]
                ],
                'isStartCalculationWithRowTotal' => true,
                'isStartCalculationOnItem' => false,
                'isCalculateAfterPromotionsEnabled' => false,
                'isProductPricesIncludeTax' => true,
            ],
        ];
    }

    private function getTaxRule(string $taxCode, string $taxRate): TaxRule
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax
            ->setRate($taxRate)
            ->setCode($taxCode);
        $taxRule->setTax($tax);

        return $taxRule;
    }

    private function getTaxable(float $amount, int $quantity): Taxable
    {
        $taxable = new Taxable();
        $taxable->setPrice(BigDecimal::of($amount));
        $taxable->setQuantity($quantity);

        return $taxable;
    }
}
