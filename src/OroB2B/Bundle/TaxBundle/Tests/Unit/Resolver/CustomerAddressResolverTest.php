<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
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
        return new CustomerAddressResolver($this->settingsProvider, $this->matcher, $this->calculator);
    }

    /** {@inheritdoc} */
    protected function getTaxable()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());

        return $taxable;
    }

    public function testItemNotApplicable()
    {
        $event = new ResolveTaxEvent(new Taxable(), new Result());

        $this->assertNothing();

        $this->resolver->resolve($event);

        $this->assertEmptyResult($event);
    }

    public function testEmptyRules()
    {
        $taxable = $this->getTaxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setRawAmount('1');
        $event = new ResolveTaxEvent($taxable, new Result());

        $this->matcher->expects($this->once())->method('match')->willReturn([]);
        $this->resolver->resolve($event);

        $this->assertEquals(
            [
                ResultElement::INCLUDING_TAX => '1',
                ResultElement::EXCLUDING_TAX => '1',
                ResultElement::TAX_AMOUNT => '0',
                ResultElement::ADJUSTMENT => '0',
            ],
            $this->extractScalarValues($event->getResult()->getTotal())
        );
        $this->assertEquals([], $this->extractScalarValues($event->getResult()->getShipping()));
        $this->assertEquals([], $event->getResult()->getTaxes());
    }


    /** {@inheritdoc} */
    public function rulesDataProvider()
    {
        return [
            [
                '19.99',
                [$this->getTaxRule('city', '0.08')],
                new Result(
                    [
                        Result::TOTAL => ResultElement::createFromRaw('21.5892', '19.99', '1.5992', '0.0008'),
                        Result::SHIPPING => new ResultElement(),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '19.99', '1.5992'),
                        ],
                    ]
                ),
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ],
                new Result(
                    [
                        Result::TOTAL => ResultElement::createFromRaw('22.9885', '19.99', '2.9985', '0.0015'),
                        Result::SHIPPING => new ResultElement(),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '19.99', '1.5992'),
                            TaxResultElement::create(null, '0.07', '19.99', '1.3993'),
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
                        Result::TOTAL => ResultElement::createFromRaw('24.1879', '19.99', '4.1979', '0.0021'),
                        Result::SHIPPING => new ResultElement(),
                        Result::TAXES => [
                            TaxResultElement::create(null, '0.08', '19.99', '1.5992'),
                            TaxResultElement::create(null, '0.07', '19.99', '1.3993'),
                            TaxResultElement::create(null, '0.06', '19.99', '1.1994'),
                        ],
                    ]
                ),
            ],
        ];
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
