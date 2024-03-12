<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\CustomerAddress;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddress\CustomerAddressItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddress\CustomerAddressResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerAddressResolverTest extends TestCase
{
    private CustomerAddressItemResolver|MockObject $itemResolver;

    private CustomerAddressResolver $resolver;

    protected function setUp(): void
    {
        $this->itemResolver = $this->createMock(CustomerAddressItemResolver::class);

        $this->resolver = new CustomerAddressResolver($this->itemResolver);
    }

    public function testEmptyCollection(): void
    {
        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve(new Taxable());
        $this->resolver->resolve((new Taxable())->setKitTaxable(true));
    }

    public function testResolveCollection(): void
    {
        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);

        $this->itemResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $this->callback(function ($dispatchedTaxable) use ($taxableItem) {
                    $this->assertSame($taxableItem, $dispatchedTaxable);

                    return true;
                })
            );

        $this->resolver->resolve($taxable);
    }

    public function testResultLocked(): void
    {
        $result = new Result();
        $result->lockResult();
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());
        $taxable->setResult($result);

        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve($taxable);
    }
}
