<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;

use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;

class RowTotalResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RowTotalResolver
     */
    protected $resolver;

    /**
     * @var Calculator| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $calculator;

    /**
     * @var TaxationSettingsProvider| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->calculator = $this->createMock(Calculator::class);

        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->resolver = new RowTotalResolver($this->settingsProvider, $this->calculator);
    }

    protected function tearDown()
    {
        unset($this->calculator, $this->resolver, $this->settingsProvider);
    }

    public function testEmptyTaxRule()
    {
        $result = new Result();
        $amount = BigDecimal::zero();
        $taxRate = BigDecimal::zero();
        $resultElement = new ResultElement();

        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($amount, $taxRate)
            ->willReturn($resultElement);

        $this->resolver->resolveRowTotal($result, [], $amount, 0);

        $this->assertEquals($resultElement, $result->getRow());
        $this->assertEquals([], $result->getTaxes());
    }

    /**
     * @dataProvider rowTotalDataProvider
     * @param BigDecimal $amount
     * @param array      $taxRules
     * @param array      $expected
     * @param string     $taxRate
     * @param int        $quantity
     * @param bool       $isStartCalculationWithRowTotal
     */
    public function testResolveRowTotal(
        BigDecimal $amount,
        array $taxRules,
        array $expected,
        $taxRate,
        $quantity,
        $isStartCalculationWithRowTotal
    ) {
        $result = new Result();

        $this->settingsProvider->expects($this->once())
            ->method('isStartCalculationWithRowTotal')
            ->willReturn($isStartCalculationWithRowTotal);

        $calculateAmount = $amount->multipliedBy($quantity);

        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($calculateAmount, $taxRate)
            ->willReturn($expected['tax']);

        $this->resolver->resolveRowTotal($result, $taxRules, $amount, $quantity);
        $this->assertEquals($expected['row'], $result->getRow());
        $this->assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @return array
     */
    public function rowTotalDataProvider()
    {
        return [
            'without start calculation with row total' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'tax' => ResultElement::create('0.01255', '0.02365', '0.035655'),
                    'row' => ResultElement::create('0.01255', '0.02365', '0.035655', '-0.004345'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.02365', '0.019016'),
                        TaxResultElement::create('region', '0.07', '0.02365', '0.016639'),
                    ]

                ],
                'taxRate' => '0.15',
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
            ],
            'use zero tax' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('country', '0.00'),
                ],
                'expected' => [
                    'tax' => ResultElement::create('0.00', '19.99', '0.00'),
                    'row' => ResultElement::create('0.00', '19.99', '0.00', '0.00'),
                    'result' => [
                        TaxResultElement::create('country', '0.00', '19.99', '0.00'),
                    ]
                ],
                'taxRate' => '0.00',
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
                    'tax' => ResultElement::create('21.3893', '19.99', '1.3993'),
                    'row' => ResultElement::create('21.3893', '19.99', '1.3993', '-0.0007'),
                    'result' => [
                        TaxResultElement::create('country', '0.00', '19.99', '0.00'),
                        TaxResultElement::create('region', '0.07', '19.99', '1.3993'),
                    ]
                ],
                'taxRate' => '0.07',
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
            ],
            'with start calculation with row total' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                'expected' => [
                    'tax' => ResultElement::create('0.01255', '0.02365', '0.035655'),
                    'row' => ResultElement::create('0.01255', '0.02365', '0.035655', '-0.004345'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.02365', '0.019016'),
                        TaxResultElement::create('region', '0.07', '0.02365', '0.016639'),
                    ]

                ],
                'taxRate' => '0.15',
                'quantity' => 2,
                'isStartCalculationWithRowTotal' => true,
            ],
            'with more decimal places in tax rate' => [
                'amount' => BigDecimal::of('19.99'),
                'taxRules' => [
                    $this->getTaxRule('city', '0.081111'),
                    $this->getTaxRule('region', '0.070404')
                ],
                'expected' => [
                    'tax' => ResultElement::create('0.01255', '0.02365', '0.035655'),
                    'row' => ResultElement::create('0.01255', '0.02365', '0.035655', '-0.004345'),
                    'result' => [
                        TaxResultElement::create('city', '0.081111', '0.02365', '0.019087'),
                        TaxResultElement::create('region', '0.070404', '0.02365', '0.016568'),
                    ]

                ],
                'taxRate' => '0.151515',
                'quantity' => 1,
                'isStartCalculationWithRowTotal' => false,
            ],
        ];
    }

    /**
     * @dataProvider resolverRowTotalWithUnitPriceDataProvider
     * @param BigDecimal[] $amount
     * @param array        $taxRules
     * @param array        $expected
     * @param string       $taxRate
     * @param int          $quantity
     * @param bool|false $isStartCalculationWithUnitPrice
     */
    public function testResolverRowTotalWithStartCalculationWithUnitPrice(
        array $amount,
        array $taxRules,
        array $expected,
        $taxRate,
        $quantity,
        $isStartCalculationWithUnitPrice = false
    ) {
        $result = new Result();

        $this->settingsProvider->expects($this->once())
            ->method('isStartCalculationWithUnitPrice')
            ->willReturn($isStartCalculationWithUnitPrice);

        $this->calculator->expects($this->exactly(2))
            ->method('calculate')
            ->withConsecutive([$amount['amount'], $taxRate], [$amount['excludingAmount'], $taxRate])
            ->willReturnOnConsecutiveCalls($expected['tax'], $expected['excludingTax']);

        $this->calculator->expects($this->once())
            ->method('getAmountKey')
            ->willReturn(ResultElement::EXCLUDING_TAX);

        $this->resolver->resolveRowTotal($result, $taxRules, $amount['amount'], $quantity);
        $this->assertEquals($expected['row'], $result->getRow());
        $this->assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @return array
     */
    public function resolverRowTotalWithUnitPriceDataProvider()
    {
        return [
            [
                [
                    'amount' => BigDecimal::of('19.99'),
                    'excludingAmount' => BigDecimal::of('0.04')
                ],
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                [
                    'tax' => ResultElement::create('0.01255', '0.02365', '0.035655'),
                    'excludingTax' => ResultElement::create('0.022', '0.555', '0.5454'),
                    'row' => ResultElement::create('0.022', '0.555', '0.5454', '-0.0046'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.555', '0.290880'),
                        TaxResultElement::create('region', '0.07', '0.555', '0.254520'),
                    ]

                ],
                '0.15',
                2,
                true
            ]
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
