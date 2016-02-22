<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxDigitalItemResolver;

class USSalesTaxDigitalItemResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var USSalesTaxDigitalItemResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new USSalesTaxDigitalItemResolver();
    }

    public function testResolver()
    {
        $taxable = new Taxable();
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('US'))
            ->setRegion((new Region('US-CA'))->setCode('CA'));

        $taxable
            ->setPrice('19.99')
            ->setDestination($address)
            ->addContext(Taxable::DIGITAL_PRODUCT, true);

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
    }

    public function testEmptyData()
    {
        $taxable = new Taxable();
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setPrice('19.99');
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addItem(new Taxable());
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());
    }
}
