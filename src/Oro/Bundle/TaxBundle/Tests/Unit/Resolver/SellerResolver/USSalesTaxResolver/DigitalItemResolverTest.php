<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver\DigitalItemResolver;

class DigitalItemResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DigitalItemResolver
     */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new DigitalItemResolver();
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

    public function testResultLocket()
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

        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
        $this->assertEmpty($taxable->getResult()->getUnit()->getExcludingTax());
        $this->assertEmpty($taxable->getResult()->getRow()->getExcludingTax());
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
