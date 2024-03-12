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
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;
use PHPUnit\Framework\TestCase;

class UnitResolverTest extends TestCase
{
    /**
     * @dataProvider unitPriceDataProvider
     */
    public function testResolveUnitPrice(
        Taxable $taxable,
        array $taxRules,
        bool $isProductPricesIncludeTax,
        ResultElement $expected
    ): void {
        $calculator = $isProductPricesIncludeTax ? new IncludedTaxCalculator() : new TaxCalculator();
        $resolver = new UnitResolver($calculator);

        $resolver->resolveUnitPrice($taxable->getResult(), $taxRules, $taxable->getPrice());
        $this->assertEquals($expected, $taxable->getResult()->getUnit());
    }

    public function unitPriceDataProvider(): array
    {
        $taxRules = [$this->getTaxRule('city', '0.08'), $this->getTaxRule('region', '0.07')];

        $resultItem = new Result();
        $resultItem->offsetSet(Result::UNIT, ResultElement::create('2.54', '2.5', '0.04', '0.00'));
        $resultItem2 = new Result();
        $resultItem2->offsetSet(Result::UNIT, ResultElement::create('3.76', '3.7', '0.06', '0.00'));

        $taxable = $this->getTaxable(19.99, true);
        $taxable->getResult()->offsetSet(Result::ITEMS, [$resultItem, $resultItem2]);

        $taxableItem = (new Taxable())->setResult($resultItem);
        $taxableItem2 = (new Taxable())->setResult($resultItem2);
        $taxable->addItem($taxableItem->setPrice(2));
        $taxable->addItem($taxableItem2->setPrice(3));

        return [
            'simple taxable' => [
                $this->getTaxable(19.99, false),
                $taxRules,
                'isProductPricesIncludeTax' => false,
                ResultElement::create('22.9885', '19.99', '2.9985', '-0.0015'),
            ],
            'taxable with empty rules' => [
                $this->getTaxable(19.99, false),
                [],
                'isProductPricesIncludeTax' => false,
                ResultElement::create('19.99', '19.99', '0.00', '0.00'),
            ],
            'taxable with zero tax' => [
                $this->getTaxable(0.0, false),
                $taxRules,
                'isProductPricesIncludeTax' => false,
                ResultElement::create('0.00', '0', '0.00', '0.00'),
            ],
            'taxable with enabled isProductPricesIncludeTax' => [
                $this->getTaxable(19.99, false),
                $taxRules,
                'isProductPricesIncludeTax' => true,
                ResultElement::create('19.99', '17.382609', '2.607391', '-0.002609'),
            ],
            'kit taxable' => [
                $this->getTaxable(19.99, true),
                $taxRules,
                'isProductPricesIncludeTax' => false,
                ResultElement::create('22.9885', '19.99', '2.9985', '-0.0015'),
            ],
            'kit taxable with empty rules' => [
                $this->getTaxable(19.99, true),
                [],
                'isProductPricesIncludeTax' => false,
                ResultElement::create('19.99', '19.99', '0.00', '0.00'),
            ],
            'kit taxable with taxable items' => [
                $taxable,
                $taxRules,
                'isProductPricesIncludeTax' => false,
                ResultElement::create('22.9885', '19.99', '2.9985', '-0.0015'),
            ],
            'kit taxable with taxable items and enabled isProductPricesIncludeTax' => [
                $taxable,
                $taxRules,
                'isProductPricesIncludeTax' => true,
                ResultElement::create('19.99', '17.382609', '2.607391', '-0.002609'),
            ],
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

    private function getTaxable(float $amount, bool $kitTaxable): Taxable
    {
        $taxable = new Taxable();
        $taxable->setKitTaxable($kitTaxable);
        $taxable->setPrice(BigDecimal::of($amount));
        $taxable->setQuantity(2);

        return $taxable;
    }
}
