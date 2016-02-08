<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

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
        return new CustomerAddressItemResolver($this->settingsProvider, $this->matcher, $this->calculator);
    }

    /** {@inheritdoc} */
    protected function getTaxable()
    {
        return new Taxable();
    }

    public function testItemNotApplicable()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
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
                        Result::ROW => ResultElement::create('64.7676', '59.97', '4.7976', '-0.0024'),
                        Result::UNIT => ResultElement::create('21.5892', '19.99', '1.5992', '-0.0008'),
                        Result::TAXES => [
                            TaxResultElement::create('city', '0.08', '59.97', '4.7976'),
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
                        Result::ROW => ResultElement::create('68.9655', '59.97', '8.9955', '-0.0045'),
                        Result::UNIT => ResultElement::create('22.9885', '19.99', '2.9985', '-0.0015'),
                        Result::TAXES => [
                            TaxResultElement::create('city', '0.08', '59.97', '4.7976'),
                            TaxResultElement::create('region', '0.07', '59.97', '4.1979'),
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
                        Result::ROW => ResultElement::create('72.5637', '59.97', '12.5937', '-0.0063'),
                        Result::UNIT => ResultElement::create('24.1879', '19.99', '4.1979', '-0.0021'),
                        Result::TAXES => [
                            TaxResultElement::create('city', '0.08', '59.97', '4.7976'),
                            TaxResultElement::create('region', '0.07', '59.97', '4.1979'),
                            TaxResultElement::create('country', '0.06', '59.97', '3.5982'),
                        ],
                    ]
                ),
            ],
            [
                '19.989',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                new Result(
                    [
                        Result::ROW => ResultElement::create('72.5637', '59.97', '12.5937', '0.0037'),
                        Result::UNIT => ResultElement::create('24.18669', '19.989', '4.19769', '-0.00231'),
                        Result::TAXES => [
                            TaxResultElement::create('city', '0.08', '59.97', '4.7976'),
                            TaxResultElement::create('region', '0.07', '59.97', '4.1979'),
                            TaxResultElement::create('country', '0.06', '59.97', '3.5982'),
                        ],
                    ]
                ),
                true,
            ],
        ];
    }

    /** {@inheritdoc} */
    protected function assertEmptyResult(Taxable $taxable)
    {
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getUnit());
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getRow());
        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }
}
