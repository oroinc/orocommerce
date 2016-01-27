<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\RoundingResolver;
use OroB2B\Bundle\TaxBundle\Tests\ResultComparatorTrait;

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
                    [
                        Result::TOTAL => ResultElement::create('24.1879', '19.9912'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5992'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3993'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.1994'),
                        ],
                    ],
                ]
            )
        );
        $itemTaxable = new Taxable();
        $itemTaxable->setResult(
            new Result(
                [
                    [
                        Result::UNIT => ResultElement::create('24.1879', '19.9912', '5.1312', '0.0000001'),
                        Result::ROW => ResultElement::create('24.1879', '19.8912', '5.5356', '00001'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.5812'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.3813'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.1814'),
                        ],
                    ],
                ]
            )
        );
        $taxable->addItem($itemTaxable);

        $event = new ResolveTaxEvent($taxable);

        $this->resolver->resolve($event);

        $this->compareResult(
            new Result(
                [
                    [
                        Result::TOTAL => ResultElement::create('24.19', '20'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.6'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.4'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.2'),
                        ],
                    ],
                ]
            ),
            $taxable->getResult()
        );

        $this->compareResult(
            new Result(
                [
                    [
                        Result::UNIT => ResultElement::create('24.19', '20', '5.14', '0.01'),
                        Result::ROW => ResultElement::create('24.19', '19.9', '5.54', '001'),
                        Result::TAXES => [
                            TaxResultElement::create('1', '0.08', '19.99', '1.59'),
                            TaxResultElement::create('2', '0.07', '19.99', '1.39'),
                            TaxResultElement::create('3', '0.06', '19.99', '1.19'),
                        ],
                    ],
                ]
            ),
            $itemTaxable->getResult()
        );
    }
}
