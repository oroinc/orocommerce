<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;

class UnitResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var Calculator|\PHPUnit\Framework\MockObject\MockObject */
    private $calculator;

    /** @var UnitResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->calculator = $this->createMock(Calculator::class);

        $this->resolver = new UnitResolver($this->calculator);
    }

    /**
     * @dataProvider unitPriceDataProvider
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

    public function unitPriceDataProvider(): array
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

    private function getTaxRule(string $taxCode, string $taxRate): TaxRule
    {
        $tax = new Tax();
        $tax->setRate($taxRate);
        $tax->setCode($taxCode);

        $taxRule = new TaxRule();
        $taxRule->setTax($tax);

        return $taxRule;
    }
}
