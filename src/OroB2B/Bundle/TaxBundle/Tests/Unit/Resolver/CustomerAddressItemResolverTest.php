<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;

class CustomerAddressItemResolverTest extends AbstractItemResolverTestCase
{
    /** @var CustomerAddressItemResolver */
    protected $resolver;

    /** {@inheritdoc} */
    protected function createResolver()
    {
        return new CustomerAddressItemResolver($this->unitResolver, $this->rowTotalResolver, $this->matcher);
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
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ],
            ],
        ];
    }

    public function testResultLocked()
    {
        $result = new Result();
        $result->lockResult();
        $taxable = new Taxable();
        $taxable->setPrice('20');
        $taxable->setTaxationAddress(new Address());
        $taxable->setResult($result);

        $this->assertNothing();

        $this->resolver->resolve($taxable);
    }

    /** {@inheritdoc} */
    protected function assertEmptyResult(Taxable $taxable)
    {
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getUnit());
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getRow());
        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }
}
