<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalKitItemResolver;
use Oro\Bundle\TaxBundle\Tests\Unit\Resolver\AbstractItemResolverTestCase;

class DigitalKitItemResolverTest extends AbstractItemResolverTestCase
{
    #[\Override]
    protected function createResolver(): AbstractItemResolver
    {
        return new DigitalKitItemResolver(
            $this->unitResolver,
            $this->rowTotalResolver,
            $this->matcher
        );
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $taxableAmount, array $taxRules): void
    {
        $taxable = new Taxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination((new Address())->setCountry(new Country('UK')));
        $taxable->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, true);
        $taxable->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');

        $this->matcher->expects(self::once())
            ->method('match')
            ->willReturn($taxRules);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), $taxRules, $taxable->getPrice());

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), $taxRules, $taxable->getPrice(), $taxable->getQuantity());

        $this->resolver->resolve($taxable);
    }

    public function resolveDataProvider(): array
    {
        return [
            [
                '19.99',
                [$this->getTaxRule('city', '0.08')],
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ],
            ],
        ];
    }

    public function testResultLocked(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice('19.99');
        $taxable->setDestination((new Address())->setCountry(new Country('UK')));
        $taxable->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, true);

        $taxable->getResult()->lockResult();

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
        $this->assertEmpty($taxable->getResult()->getUnit()->getExcludingTax());
        $this->assertEmpty($taxable->getResult()->getRow()->getExcludingTax());
    }

    public function testIsApplicable(): void
    {
        $taxable = new Taxable();
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setPrice('19.99');
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addContext(Taxable::DIGITAL_PRODUCT, true);
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addItem(new Taxable());
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $address = new Address();
        $taxable->setDestination($address);
        $this->resolver->resolve($taxable);

        $this->assertFalse($taxable->getResult()->isResultLocked());

        $address->setCountry(new Country('UK'));

        $this->resolver->resolve($taxable);

        $this->assertTrue($taxable->getResult()->isResultLocked());
    }

    public function testDestinationAddressForDigitalProductsAndEUBuyer(): void
    {
        $taxable = new Taxable();
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('DE'))
            ->setRegion((new Region('DE-BE'))->setCode('BE'));

        $origin = new Address();
        $origin
            ->setCountry(new Country('AT'));

        $taxable
            ->setPrice('19.99')
            ->setDestination($address)
            ->setOrigin($origin)
            ->addContext(Taxable::DIGITAL_PRODUCT, true);

        $taxable->makeOriginAddressTaxable();

        $this->matcher->expects($this->once())
            ->method('match')
            ->willReturn([]);
        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal');
        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        $this->assertSame($address, $taxable->getTaxationAddress());
    }

    public function testOriginAddressForNonDigitalProductsAndEUBuyer(): void
    {
        $taxable = new Taxable();
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('DE'))
            ->setRegion((new Region('DE-BE'))->setCode('BE'));

        $origin = new Address();
        $origin
            ->setCountry(new Country('AT'));

        $taxable
            ->setPrice('19.99')
            ->setDestination($address)
            ->setOrigin($origin)
            ->addContext(Taxable::DIGITAL_PRODUCT, false);

        $taxable->makeOriginAddressTaxable();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->rowTotalResolver->expects($this->never())
            ->method('resolveRowTotal');
        $this->unitResolver->expects($this->never())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        $this->assertSame($origin, $taxable->getTaxationAddress());
    }

    public function testDestinationAddressForDigitalProductsAndNonEUBuyer(): void
    {
        $taxable = new Taxable();
        $address = new OrderAddress();
        $address
            ->setCountry(new Country('US'))
            ->setRegion((new Region('US-FL'))->setCode('FL'));

        $origin = new Address();
        $origin
            ->setCountry(new Country('AT'));

        $taxable
            ->setPrice('19.99')
            ->setDestination($address)
            ->setOrigin($origin)
            ->addContext(Taxable::DIGITAL_PRODUCT, true);

        $taxable->makeOriginAddressTaxable();

        $this->matcher->expects($this->never())
            ->method('match');
        $this->rowTotalResolver->expects($this->never())
            ->method('resolveRowTotal');
        $this->unitResolver->expects($this->never())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        $this->assertSame($origin, $taxable->getTaxationAddress());
    }
}
