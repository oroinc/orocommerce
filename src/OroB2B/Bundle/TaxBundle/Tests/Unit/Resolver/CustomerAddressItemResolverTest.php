<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;

class CustomerAddressItemResolverTest extends AbstractItemResolverTestCase
{
    /** @var CustomerAddressItemResolver */
    protected $resolver;

    /** {@inheritdoc} */
    protected function createResolver()
    {
        $resolver = new CustomerAddressItemResolver($this->unitResolver, $this->rowTotalResolver);
        $resolver->setMatcher($this->matcher);
        return $resolver;
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
                [$this->getTaxRule('city', '0.08')]
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ]
            ]
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
