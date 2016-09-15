<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;

use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;

class UnitResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnitResolver
     */
    protected $resolver;

    /**
     * @var Calculator| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $calculator;

    protected function setUp()
    {
        $this->calculator = $this->getMockBuilder('Oro\Bundle\TaxBundle\Calculator\Calculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new UnitResolver($this->calculator);
    }

    protected function tearDown()
    {
        unset($this->calculator, $this->resolver);
    }

    /**
     * @dataProvider unitPriceDataProvider
     * @param array      $taxRules
     * @param BigDecimal $amount
     * @param            $taxRate
     * @param            $expected
     */
    public function testResolveUnitPrice(array $taxRules, BigDecimal $amount, $taxRate, ResultElement $expected)
    {
        $result = new Result();
        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($amount, $taxRate)
            ->willReturn($expected);

        $this->resolver->resolveUnitPrice($result, $taxRules, $amount);
        $this->assertEquals($expected, $result->getUnit());
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

        $this->resolver->resolveUnitPrice($result, [], $amount);

        $this->assertEquals($resultElement, $result->getUnit());

    }

    /**
     * @return array
     */
    public function unitPriceDataProvider()
    {
        return [
            [
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07')
                ],
                BigDecimal::of('19.99'),
                '0.15',
                ResultElement::create('0.00', '0.00', '0.00', '0.00'),
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
