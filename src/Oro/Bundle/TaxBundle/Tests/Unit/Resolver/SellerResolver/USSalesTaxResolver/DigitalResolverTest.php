<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\USSalesTaxResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver\DigitalItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver\DigitalResolver;

class DigitalResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var DigitalItemResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $itemResolver;

    /** @var DigitalResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->itemResolver = $this->createMock(DigitalItemResolver::class);

        $this->resolver = new DigitalResolver($this->itemResolver);
    }

    public function testEmptyCollection()
    {
        $this->itemResolver->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve(new Taxable());
    }

    public function testGeneralStateCollection()
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

    public function testResolveCollection()
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

    public function testTaxableAddressIsOrigin()
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
