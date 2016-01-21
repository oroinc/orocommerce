<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressResolver;

class CustomerAddressResolverTest extends AbstractAddressResolverTestCase
{
    /** @var CustomerAddressResolver */
    protected $resolver;

    /** {@inheritdoc} */
    protected function createResolver()
    {
        return new CustomerAddressResolver($this->settingsProvider, $this->matcher, $this->calculator, $this->rounding);
    }

    public function testItemNotApplicable()
    {
        $event = new ResolveTaxEvent(new Taxable(), new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testDestinationMissing()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testEmptyRules()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $taxable->setDestination(new OrderAddress());
        $taxable->setAmount(0);
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn([]);
        $this->calculator->expects($this->never())->method($this->anything());

        $this->resolver->resolve($event);

        $this->assertEquals(ResultElement::create(0, 0), $event->getResult()->getTotal());
        $this->assertEquals(new ResultElement(), $event->getResult()->getShipping());
        $this->assertEquals([], $event->getResult()->getTaxes());
    }

    /**
     * @dataProvider rulesDataProvider
     * @param string $taxableAmount
     * @param array $taxRules
     * @param Result $expectedResult
     */
    public function testRules($taxableAmount, array $taxRules, Result $expectedResult)
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);
        $this->assertRoundServiceCalled();
        $this->rounding->expects($this->any())->method('round')->willReturnCallback(
            function ($value) {
                return (string)round($value, 2);
            }
        );
        $this->resolver->resolve($event);

        $this->assertEquals($expectedResult, $event->getResult());
    }

    /**
     * @return array
     */
    public function rulesDataProvider()
    {
        return [
            [
                '59.99',
                [$this->getTaxRule('city', '0.08')],
                new Result(
                    [
                        Result::TOTAL => ResultElement::create('59.99', '55.19'),
                        Result::SHIPPING => new ResultElement(),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '59.99', '4.8'),
                        ],
                    ]
                ),
            ],
            [
                '59.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                new Result(
                    [
                        Result::TOTAL => ResultElement::create('59.99', '47.39'),
                        Result::SHIPPING => new ResultElement(),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '59.99', '4.8'),
                            TaxResultElement::create(null, '0.07', '59.99', '4.2'),
                            TaxResultElement::create(null, '0.06', '59.99', '3.6'),
                        ],
                    ]
                ),
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

    /**
     * @param ResolveTaxEvent $event
     */
    protected function assertEmptyResult(ResolveTaxEvent $event)
    {
        $this->assertEquals(new ResultElement(), $event->getResult()->getTotal());
        $this->assertEquals(new ResultElement(), $event->getResult()->getShipping());
        $this->assertEquals([], $event->getResult()->getTaxes());
    }
}
