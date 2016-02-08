<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalResolver;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\ItemDigitalResolver;

class DigitalResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DigitalResolver
     */
    protected $resolver;

    /**
     * @var  CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $countryMatcher;

    /**
     * @var ItemDigitalResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemResolver;

    public function setUp()
    {
        $itemResolverClass =
            'OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\ItemDigitalResolver';

        $this->countryMatcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemResolver = $this->getMockBuilder($itemResolverClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new DigitalResolver($this->countryMatcher, $this->itemResolver);
    }

    public function testResolve()
    {
        $taxableItem = new Taxable();
        $taxable = $this->getTaxable($taxableItem);

        $this->countryMatcher->expects($this->once())
            ->method('isEuropeanUnionCountry')
            ->willReturn(true);

        $this->itemResolver->expects($this->once())->method('resolve')->with(
            $this->callback(
                function ($dispatchedTaxable) use ($taxableItem) {
                    $this->assertSame($taxableItem, $dispatchedTaxable);
                    return true;
                }
            )
        );

        $this->resolver->resolve($taxable);
    }

    public function testEmptyParameters()
    {
        $taxableItem = new Taxable();
        $taxable = $this->getTaxable($taxableItem);

        $this->countryMatcher->expects($this->once())
            ->method('isEuropeanUnionCountry')
            ->willReturn(false);

        $this->itemResolver->expects($this->never())->method('resolve');

        $this->resolver->resolve($taxable);

        $taxable->removeItem($taxableItem);
        $this->resolver->resolve($taxable);

        $taxable->addItem($taxableItem);
        $taxable->setDestination(null);
        $this->resolver->resolve($taxable);
    }

    /**
     * @param Taxable $taxableItem
     * @return Taxable
     */
    protected function getTaxable($taxableItem)
    {
        $taxable = new Taxable();
        $taxable->setDestination(new OrderAddress());
        $taxable->setOrigin(new OrderAddress());
        $taxable->addItem($taxableItem);

        return $taxable;
    }
}
