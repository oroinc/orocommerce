<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Resolver\CurrencyResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;
use PHPUnit\Framework\TestCase;

class CurrencyResolverTest extends TestCase
{
    use ResultComparatorTrait;

    private const CURRENCY = 'USD';

    private CurrencyResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolver = new CurrencyResolver();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testResolve(): void
    {
        $taxable = new Taxable();
        $taxable->setCurrency(self::CURRENCY);
        $taxable->setResult(
            new Result(
                [
                    Result::TOTAL => ResultElement::create('11', '10'),
                    Result::TAXES => [
                        TaxResultElement::create('1', '0.1', '10', '1'),
                    ],
                ]
            )
        );
        $itemTaxable = new Taxable();
        $itemTaxable->setCurrency(self::CURRENCY);
        $itemTaxable->setKitTaxable(true);
        $itemTaxable->setResult(
            new Result(
                [
                    Result::UNIT => ResultElement::create('11', '10', '1', '0'),
                    Result::ROW => ResultElement::create('11', '10', '1', '0'),
                    Result::TAXES => [
                        TaxResultElement::create('1', '0.1', '10', '1'),
                    ],
                ]
            )
        );
        $taxable->addItem($itemTaxable);

        $kitItemTaxable = new Taxable();
        $kitItemTaxable->setResult(
            new Result(
                [
                    Result::UNIT => ResultElement::create('3', '2', '1', '0'),
                    Result::ROW => ResultElement::create('3', '2', '1', '0'),
                    Result::TAXES => [
                        TaxResultElement::create('1', '0.5', '2', '1'),
                    ],
                ]
            )
        );

        $itemTaxable->addItem($kitItemTaxable);

        $this->resolver->resolve($taxable);

        $resultTotal = ResultElement::create('11', '10')
            ->setCurrency(self::CURRENCY);
        $resultTax = TaxResultElement::create('1', '0.1', '10', '1')
            ->setCurrency(self::CURRENCY);

        $this->compareResult(
            new Result(
                [
                    Result::TOTAL => $resultTotal,
                    Result::TAXES => [
                        $resultTax,
                    ],
                ]
            ),
            $taxable->getResult()
        );

        $resultUnit = ResultElement::create('11', '10', '1', '0')
            ->setCurrency(self::CURRENCY);
        $resultRow = ResultElement::create('11', '10', '1', '0')
            ->setCurrency(self::CURRENCY);
        $resultTax = TaxResultElement::create('1', '0.1', '10', '1')
            ->setCurrency(self::CURRENCY);

        $this->compareResult(
            new Result(
                [
                    Result::UNIT => $resultUnit,
                    Result::ROW => $resultRow,
                    Result::TAXES => [
                        $resultTax,
                    ],
                ]
            ),
            $itemTaxable->getResult()
        );

        $resultKitItemUnit = ResultElement::create('3', '2', '1', '0')
            ->setCurrency(self::CURRENCY);
        $resultKitItemRow = ResultElement::create('3', '2', '1', '0')
            ->setCurrency(self::CURRENCY);
        $resultKitItemTax = TaxResultElement::create('1', '0.5', '2', '1')
            ->setCurrency(self::CURRENCY);

        $this->compareResult(
            new Result(
                [
                    Result::UNIT => $resultKitItemUnit,
                    Result::ROW => $resultKitItemRow,
                    Result::TAXES => [
                        $resultKitItemTax,
                    ],
                ]
            ),
            $kitItemTaxable->getResult()
        );
    }
}
