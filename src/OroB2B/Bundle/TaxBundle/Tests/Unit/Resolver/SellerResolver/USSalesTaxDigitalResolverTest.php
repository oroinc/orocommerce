<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxDigitalItemResolver;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxDigitalResolver;

class USSalesTaxDigitalResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var USSalesTaxDigitalResolver */
    protected $resolver;

    /** @var USSalesTaxDigitalItemResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $itemResolver;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->itemResolver = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxDigitalItemResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new USSalesTaxDigitalResolver($this->itemResolver);
    }

    public function testEmptyCollection()
    {
        $this->itemResolver->expects($this->never())->method($this->anything());

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

        $this->itemResolver->expects($this->never())->method($this->anything());

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
}
