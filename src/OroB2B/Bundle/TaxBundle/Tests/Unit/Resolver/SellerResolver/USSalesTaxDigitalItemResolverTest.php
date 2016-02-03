<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxDigitalItemResolver;
use OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\AbstractAddressResolverTestCase;

class USSalesTaxDigitalItemResolverTest extends AbstractAddressResolverTestCase
{
    /** @var CustomerAddressItemResolver */
    protected $resolver;

    /** {@inheritdoc} */
    protected function createResolver()
    {
        return new USSalesTaxDigitalItemResolver($this->settingsProvider, $this->matcher, $this->calculator);
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

    /**
     * @dataProvider rulesDataProvider
     * @param string $taxableAmount
     * @param array $taxRules
     * @param bool $isDigital
     * @param Result $expectedResult
     * @param bool $startWithRowTotal
     */
    public function testRules(
        $taxableAmount,
        array $taxRules,
        $isDigital,
        Result $expectedResult,
        $startWithRowTotal = false
    ) {
        $taxable = $this->getTaxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());
        $taxable->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, $isDigital);

        $this->matcher->expects($this->any())->method('match')->willReturn($taxRules);
        $this->settingsProvider->expects($this->any())->method('isStartCalculationWithRowTotal')
            ->willReturn($startWithRowTotal);

        $this->resolver->resolve($taxable);

        $this->compareResult($expectedResult, $taxable->getResult());
    }

    /** {@inheritdoc} */
    public function rulesDataProvider()
    {
        return [
            [
                '19.989',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                false,
                new Result(
                    [
                        Result::ROW => ResultElement::create('72.5637', '59.97', '12.5937', '0.0063'),
                        Result::UNIT => ResultElement::create('24.18669', '19.989', '4.19769', '0.00231'),
                        Result::TAXES => [
                            TaxResultElement::create('city', '0.08', '59.97', '4.7976'),
                            TaxResultElement::create('region', '0.07', '59.97', '4.1979'),
                            TaxResultElement::create('country', '0.06', '59.97', '3.5982'),
                        ],
                    ]
                ),
                true,
            ],
            [
                '19.989',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                    $this->getTaxRule('country', '0.06'),
                ],
                true,
                new Result(
                    [
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
