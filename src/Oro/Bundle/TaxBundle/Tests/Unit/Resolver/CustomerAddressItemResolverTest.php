<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;

class CustomerAddressItemResolverTest extends AbstractItemResolverTestCase
{
    /** @var CustomerAddressItemResolver */
    protected $resolver;

    /**
     * {@inheritDoc}
     */
    protected function createResolver(): AbstractItemResolver
    {
        return new CustomerAddressItemResolver($this->unitResolver, $this->rowTotalResolver, $this->matcher);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTaxable(): Taxable
    {
        return new Taxable();
    }

    /**
     * {@inheritDoc}
     */
    protected function assertEmptyResult(Taxable $taxable): void
    {
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getUnit());
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getRow());
        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }

    /**
     * {@inheritDoc}
     */
    public function rulesDataProvider(): array
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

    public function testItemNotApplicable()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
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
}
