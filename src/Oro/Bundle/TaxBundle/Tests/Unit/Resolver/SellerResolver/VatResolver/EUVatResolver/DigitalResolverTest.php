<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalResolver;

class DigitalResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DigitalResolver
     */
    protected $resolver;

    /**
     * @var DigitalItemResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $itemResolver;

    public function setUp()
    {
        $itemResolverClass =
            'Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalItemResolver';

        $this->itemResolver = $this->getMockBuilder($itemResolverClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new DigitalResolver($this->itemResolver);
    }

    public function testResolve()
    {
        $taxableItem = new Taxable();
        $taxable = $this->getTaxable($taxableItem);

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
        $taxable->setDestination((new OrderAddress())->setCountry(new Country('US')));

        $this->itemResolver->expects($this->never())->method('resolve');

        $this->resolver->resolve($taxable);

        $taxable->removeItem($taxableItem);
        $this->resolver->resolve($taxable);

        $taxable->addItem($taxableItem);
        $taxable->setDestination(null);
        $this->resolver->resolve($taxable);
    }

    public function testResultLocked()
    {
        $result = new Result();
        $result->lockResult();
        $taxable = $this->getTaxable(new Taxable());
        $taxable->setResult($result);

        $this->itemResolver->expects($this->never())->method('resolve');

        $this->resolver->resolve($taxable);
    }

    /**
     * @param Taxable $taxableItem
     * @return Taxable
     */
    protected function getTaxable($taxableItem)
    {
        $taxable = new Taxable();
        $taxable->setDestination((new OrderAddress())->setCountry(new Country('UK')));
        $taxable->setOrigin(new OrderAddress());
        $taxable->addItem($taxableItem);

        return $taxable;
    }

    public function testTaxableAddresIsOrigin()
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
}
