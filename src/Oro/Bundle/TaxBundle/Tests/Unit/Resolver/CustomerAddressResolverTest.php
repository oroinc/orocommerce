<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;
use Oro\Bundle\TaxBundle\Resolver\CustomerAddressResolver;

class CustomerAddressResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomerAddressItemResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $itemResolver;

    /** @var CustomerAddressResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->itemResolver = $this->createMock(CustomerAddressItemResolver::class);

        $this->resolver = new CustomerAddressResolver($this->itemResolver);
    }

    public function testEmptyCollection()
    {
        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve(new Taxable());
    }

    public function testResolveCollection()
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

    public function testResultLocked()
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
