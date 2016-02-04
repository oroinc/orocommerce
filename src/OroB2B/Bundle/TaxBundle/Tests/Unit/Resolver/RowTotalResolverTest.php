<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Calculator\Calculator;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Resolver\RowTotalResolver;

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
        $this->calculator = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Calculator\Calculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->settingsProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->resolver->resolveRowTotal($result, [], $amount);

        $this->assertEquals($resultElement, $result->getUnit());
        $this->assertEquals([], $result->getTaxes());
    }

    /**
     * @dataProvider rowTotalDataProvider
     * @param BigDecimal $amount
     * @param array      $taxRules
     * @param array      $expected
     * @param array      $currentTaxRates
     * @param string     $taxRate
     * @param bool       $isStartCalculationWithRowTotal
     */
    public function testResolveRowTotal(
        BigDecimal $amount,
        array $taxRules,
        array $expected,
        array $currentTaxRates,
        $taxRate,
        $isStartCalculationWithRowTotal = false
    ) {
        $result = new Result();

        $this->settingsProvider->expects($this->once())
            ->method('isStartCalculationWithRowTotal')
            ->willReturn($isStartCalculationWithRowTotal);

        $this->calculator->expects($this->exactly(3))
            ->method('calculate')
            ->withConsecutive([$amount, $currentTaxRates[0]], [$amount, $currentTaxRates[1]], [$amount, $taxRate])
            ->willReturnOnConsecutiveCalls($expected['tax'], $expected['tax'], $expected['row']);

        $this->resolver->resolveRowTotal($result, $taxRules, $amount);
        $this->assertEquals($expected['row'], $result->getRow());
        $this->assertEquals($expected['result'], $result->getTaxes());
    }

    /**
     * @return array
     */
    public function rowTotalDataProvider()
    {
        return [
            [
                BigDecimal::of('19.99'),
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                [
                    'tax' => ResultElement::create('0.01', '0.02', '0.03', '0.04'),
                    'row' => ResultElement::create('0.00', '0.00', '0.00', '0.00'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.02', '0.03'),
                        TaxResultElement::create('region', '0.07', '0.02', '0.03'),
                    ]

                ],
                [
                    '0.08',
                    '0.07'
                ],
                '0.15'
            ],
            [
                BigDecimal::of('19.99'),
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                [
                    'tax' => ResultElement::create('0.01', '0.02', '0.03', '0.04'),
                    'row' => ResultElement::create('0.00', '0.00', '0.00', '0.00'),
                    'result' => [
                        TaxResultElement::create('city', '0.08', '0.02', '0.03'),
                        TaxResultElement::create('region', '0.07', '0.02', '0.03'),
                    ]

                ],
                [
                    '0.08',
                    '0.07'
                ],
                '0.15',
                true
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
