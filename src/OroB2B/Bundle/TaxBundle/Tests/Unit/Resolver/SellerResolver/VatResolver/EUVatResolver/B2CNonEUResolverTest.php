<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\B2CNonEUResolver;

class B2CNonEUResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMatcher;

    /**
     * @var B2CNonEUResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->countryMatcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new B2CNonEUResolver($this->countryMatcher);
    }

    public function tearDown()
    {
        unset($this->countryMatcher, $this->resolver);
    }

    /**
     * @expectedException \OroB2B\Bundle\TaxBundle\Resolver\StopPropagationException
     */
    public function testResolveWithException()
    {
        $taxable = new Taxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setOrigin(new OrderAddress());


        $this->countryMatcher->expects($this->exactly(2))
            ->method('isEuropeanUnionCountry')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->resolver->resolve($taxable);
    }

    public function testResolve()
    {
        $taxable = new Taxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setOrigin(new OrderAddress());


        $this->countryMatcher->expects($this->exactly(2))
            ->method('isEuropeanUnionCountry')
            ->willReturnOnConsecutiveCalls(true, true);

        $this->resolver->resolve($taxable);
    }
}
