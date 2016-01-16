<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;

class CustomerAddressItemResolverTest extends AbstractAddressResolverTestCase
{
    /** @var CustomerAddressItemResolver */
    protected $resolver;

    /** {@inheritdoc} */
    protected function createResolver()
    {
        return new CustomerAddressItemResolver(
            $this->settingsProvider,
            $this->matcher,
            $this->calculator,
            $this->rounding
        );
    }

    public function testItemNotApplicable()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testDestinationMissing()
    {
        $event = new ResolveTaxEvent(new Taxable(), new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testEmptyRules()
    {
        $taxable = new Taxable();
        $taxable->setPrice(Price::create(1, 'USD'));
        $taxable->setDestination(new OrderAddress());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn([]);
        $this->calculator->expects($this->never())->method($this->anything());

        $this->resolver->resolve($event);

        $this->assertEquals(ResultElement::create(0, 0), $event->getResult()->getRow());
        $this->assertEquals(ResultElement::create(0, 0), $event->getResult()->getUnit());
        $this->assertEquals([], $event->getResult()->getTaxes());
    }

    /**
     * @dataProvider rulesDataProvider
     * @param string $taxableAmount
     * @param array $taxRules
     * @param Result $expectedResult
     * @param bool $startWithRowTotal
     */
    public function testRules($taxableAmount, array $taxRules, Result $expectedResult, $startWithRowTotal = false)
    {
        $taxable = new Taxable();
        $taxable->setPrice(Price::create($taxableAmount, 'USD'));
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);
        $this->assertRoundServiceCalled();
        $this->settingsProvider->expects($this->once())->method('isStartCalculationWithRowTotal')
            ->willReturn($startWithRowTotal);
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
                '19.99',
                [$this->getTaxRule('city', '0.08')],
                new Result(
                    [
                        Result::ROW => ResultElement::create('59.97', '55.17', '4.8', '0'),
                        Result::UNIT => ResultElement::create('19.99', '18.39', '1.6', '0'),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '59.97', '4.8'),
                        ],
                    ]
                ),
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                new Result(
                    [
                        Result::ROW => ResultElement::create('59.97', '47.37', '12.6', '0'),
                        Result::UNIT => ResultElement::create('19.99', '15.79', '4.2', '0'),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '59.97', '4.8'),
                            TaxResultElement::create(null, '0.07', '59.97', '4.2'),
                            TaxResultElement::create(null, '0.06', '59.97', '3.6'),
                        ],
                    ]
                ),
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                new Result(
                    [
                        Result::ROW => ResultElement::create('59.97', '47.37', '12.6', '0'),
                        Result::UNIT => ResultElement::create('19.99', '15.79', '4.2', '0'),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '59.97', '4.8'),
                            TaxResultElement::create(null, '0.07', '59.97', '4.2'),
                            TaxResultElement::create(null, '0.06', '59.97', '3.6'),
                        ],
                    ]
                ),
                true,
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
        $this->assertEquals(new ResultElement(), $event->getResult()->getUnit());
        $this->assertEquals(new ResultElement(), $event->getResult()->getRow());
        $this->assertEquals([], $event->getResult()->getTaxes());
    }
}
