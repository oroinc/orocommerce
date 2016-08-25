<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;

class RoundingResolverTest extends \PHPUnit_Framework_TestCase
{
    use ResultComparatorTrait;

    /** @var RoundingResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new RoundingResolver();
    }

    public function testResolve()
    {
        $taxable = new Taxable();
        $taxable->setResult(
            new Result(
                [
                        Result::TOTAL => ResultElement::create('24.1879', '19.9912'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                        ],
                ]
            )
        );
        $itemTaxable = new Taxable();
        $itemTaxable->setResult(
            new Result(
                [
                        Result::UNIT => ResultElement::create('24.1879', '19.9912', '5.1312', '0.0000001'),
                        Result::ROW => ResultElement::create('24.1879', '19.8912', '5.5356', '0.0001'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5812'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3813'),
                            TaxResultElement::create('3', '0.066', '19.99', '1.1814'),
                        ],
                ]
            )
        );
        $taxable->addItem($itemTaxable);

        $this->resolver->resolve($taxable);

        $this->compareResult(
            new Result(
                [
                        Result::TOTAL => ResultElement::create('24.19', '19.99'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.6'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.4'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.2'),
                        ],
                ]
            ),
            $taxable->getResult()
        );

        $this->compareResult(
            new Result(
                [
                        Result::UNIT => ResultElement::create('24.19', '19.99', '5.13', '0.0000001'),
                        Result::ROW => ResultElement::create('24.19', '19.89', '5.54', '0.0001'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.58'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.38'),
                            TaxResultElement::create('3', '0.066', '19.99', '1.18'),
                        ],
                ]
            ),
            $itemTaxable->getResult()
        );
    }

    public function testCatchException()
    {
        $taxable = new Taxable();
        $taxable->setResult(
            new Result(
                [
                    Result::TOTAL => ResultElement::create('', '19.9912'),
                    Result::TAXES => [
                        TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                        TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                        TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                    ],
                ]
            )
        );

        $this->resolver->resolve($taxable);

        $this->compareResult(
            new Result(
                [
                    Result::TOTAL => ResultElement::create('', '19.99'),
                    Result::TAXES => [
                        TaxResultElement::create('1', '0.08', '19.99', '1.6'),
                        TaxResultElement::create('2', '0.07', '19.99', '1.4'),
                        TaxResultElement::create('3', '0.06', '19.99', '1.2'),
                    ],
                ]
            ),
            $taxable->getResult()
        );
    }
}
