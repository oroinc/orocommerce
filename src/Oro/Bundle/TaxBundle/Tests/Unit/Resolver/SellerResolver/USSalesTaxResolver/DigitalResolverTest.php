<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\USSalesTaxResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver\DigitalItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver\DigitalResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DigitalResolverTest extends TestCase
{
    private DigitalItemResolver|MockObject $itemResolver;

    private DigitalResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->itemResolver = $this->createMock(DigitalItemResolver::class);

        $this->resolver = new DigitalResolver($this->itemResolver);
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

    public function testGeneralStateCollection(): void
    {
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-IN'));
        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);

        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve($taxable);

        $taxable->setDestination($address);
        $this->resolver->resolve($taxable);
    }

    public function testResolveCollection(): void
    {
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('US'))
            ->setRegion((new Region('US-CA'))->setCode('CA'));
        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);
        $taxable->setDestination($address);

        $this->itemResolver->expects($this->once())
            ->method('resolve')
            ->with($taxableItem);

        $this->resolver->resolve($taxable);
    }

    public function testTaxableAddressIsOrigin(): void
    {
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('US'))
            ->setRegion((new Region('US-CA'))->setCode('CA'));

        $origin = new Address();
        $origin
            ->setCountry(new Country('DE'));

        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);
        $taxable->setDestination($address);
        $taxable->setOrigin($origin);
        $taxable->makeOriginAddressTaxable();

        $this->resolver->resolve($taxable);

        $this->assertSame($origin, $taxable->getTaxationAddress());
    }
}
