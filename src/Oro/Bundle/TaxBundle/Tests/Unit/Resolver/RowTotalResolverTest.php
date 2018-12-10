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
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;

class RowTotalResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $settingsProvider;

    /**
     * @var RowTotalResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->resolver = new RowTotalResolver($this->settingsProvider, new TaxCalculator());
    }

    public function testEmptyTaxRule()
    {
        $result = new Result();
        $amount = BigDecimal::zero();
        $resultElement = ResultElement::create('0', '0', '0', '0.00');

        $this->resolver->resolveRowTotal($result, [], $amount, 0);

        $this->assertEquals($resultElement, $result->getRow());
        $this->assertEquals([], $result->getTaxes());
    }

    /**
     * @dataProvider rowTotalDataProvider
     * @param BigDecimal $amount
     * @param array      $taxRules
     * @param array      $expected
     * @param int        $quantity
     * @param bool       $isStartCalculationWithRowTotal
     */
    public function testResolveRowTotal(
        BigDecimal $amount,
        array $taxRules,
        array $expected,
        $quantity,
        $isStartCalculationWithRowTotal
    ) {
        $result = new Result();

        $this->settingsProvider->expects($this->any())
            ->method('isStartCalculationWithRowTotal')
            ->willReturn($isStartCalculationWithRowTotal);
        $this->settingsProvider->expects($this->any())
            ->method('isStartCalculationWithUnitPrice')
            ->willReturn(!$isStartCalculationWithRowTotal);

        $this->resolver->resolveRowTotal($result, $taxRules, $amount, $quantity);
        $this->assertEquals($expected['row'], $result->getRow());
        $this->assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @return array
     */
    public function rowTotalDataProvider()
    {
        $taxResult1_1 = TaxResultElement::create('city', '0.08', '39.98', '3.198400');
        $taxResult1_2 = TaxResultElement::create('region', '0.07', '39.98', '2.798600');

        $taxResult2_1 = TaxResultElement::create('city', '0.08', '39.99', '3.199200');
        $taxResult2_2 = TaxResultElement::create('region', '0.07', '39.99', '2.799300');

        $taxResult3_1 = TaxResultElement::create('city', '0.081111', '39.98', '3.242818');
        $taxResult3_2 = TaxResultElement::create('region', '0.070404', '39.98', '2.814752');

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
            ],
            'use zero tax' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('country', '0.00'),
                ],
                'expected' => [
                    'row' => ResultElement::create('19.9900', '19.99', '0.0000', '0.0000'),
                    'result' => [
                        TaxResultElement::create('country', '0.00', '19.9900', '0.00'),
                    ]
                ],
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
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
                        TaxResultElement::create('country', '0.00', '19.99', '0.00'),
                        TaxResultElement::create('region', '0.07', '19.99', '1.3993'),
                    ]
                ],
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
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
            ],
        ];
    }

    /**
     * @param string $taxCode
     * @param string $taxRate
     * @return TaxRule
     */
    protected function getTaxRule($taxCode, $taxRate)
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
