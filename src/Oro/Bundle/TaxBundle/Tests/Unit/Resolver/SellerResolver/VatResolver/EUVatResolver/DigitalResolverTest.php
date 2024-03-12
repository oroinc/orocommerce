<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DigitalResolverTest extends TestCase
{
    private DigitalItemResolver|MockObject $itemResolver;

    private DigitalResolver $resolver;

    protected function setUp(): void
    {
        $this->itemResolver = $this->createMock(DigitalItemResolver::class);

        $this->resolver = new DigitalResolver($this->itemResolver);
    }

    public function testResolve(): void
    {
        $taxableItem = new Taxable();
        $taxable = $this->getTaxable($taxableItem);

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

    public function testEmptyCollection(): void
    {
        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve(new Taxable());
    }

    public function testKitTaxableWithoutItems(): void
    {
        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve((new Taxable())->setKitTaxable(true));
    }

    public function testEmptyParameters(): void
    {
        $taxableItem = new Taxable();
        $taxable = $this->getTaxable($taxableItem);
        $taxable->setDestination((new OrderAddress())->setCountry(new Country('US')));

        $this->itemResolver->expects($this->never())
            ->method('resolve');

        $this->resolver->resolve($taxable);

        $taxable->removeItem($taxableItem);
        $this->resolver->resolve($taxable);

        $taxable->addItem($taxableItem);
        $taxable->setDestination(null);
        $this->resolver->resolve($taxable);
    }

    public function testResultLocked(): void
    {
        $result = new Result();
        $result->lockResult();
        $taxable = $this->getTaxable(new Taxable());
        $taxable->setResult($result);

        $this->itemResolver->expects($this->never())
            ->method('resolve');

        $this->resolver->resolve($taxable);
    }

    public function testTaxableAddressIsOrigin(): void
    {
        $address = new OrderAddress();
        $address->setCountry(new Country('DE'));

        $origin = new Address();
        $origin->setCountry(new Country('AT'));

        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);
        $taxable->setDestination($address);
        $taxable->setOrigin($origin);
        $taxable->makeOriginAddressTaxable();

        $this->resolver->resolve($taxable);

        $this->assertSame($origin, $taxable->getTaxationAddress());
    }

    private function getTaxable(Taxable $taxableItem): Taxable
    {
        $taxable = new Taxable();
        $taxable->setDestination((new OrderAddress())->setCountry(new Country('UK')));
        $taxable->setOrigin(new OrderAddress());
        $taxable->addItem($taxableItem);

        return $taxable;
    }
}
