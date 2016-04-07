<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\CurrencyResolver;
use OroB2B\Bundle\TaxBundle\Tests\ResultComparatorTrait;

class CurrencyResolverTest extends \PHPUnit_Framework_TestCase
{
    use ResultComparatorTrait;

    const CURRENCY = 'USD';

    /** @var CurrencyResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new CurrencyResolver();
    }

    public function testResolve()
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
    }
}
