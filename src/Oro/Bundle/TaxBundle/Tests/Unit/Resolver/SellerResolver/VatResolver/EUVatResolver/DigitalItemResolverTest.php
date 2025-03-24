<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalItemResolver;
use Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\DigitalKitItemResolver;
use Oro\Bundle\TaxBundle\Tests\Unit\Resolver\AbstractItemResolverTestCase;

class DigitalItemResolverTest extends AbstractItemResolverTestCase
{
    #[\Override]
    protected function createResolver(): DigitalItemResolver
    {
        return new DigitalItemResolver(
            $this->unitResolver,
            $this->rowTotalResolver,
            $this->matcher,
            new DigitalKitItemResolver(
                $this->unitResolver,
                $this->rowTotalResolver,
                $this->matcher
            )
        );
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $taxableAmount, array $taxRules): void
    {
        $taxableItem = new Taxable();
        $taxableItem->setPrice(10);
        $taxableItem->setQuantity(2);
        $taxableItem->setDestination((new Address())->setCountry(new Country('UK')));
        $taxableItem->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, true);
        $taxableItem->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');

        $taxable = new Taxable();
        $taxable->setKitTaxable(true);
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination((new Address())->setCountry(new Country('UK')));
        $taxable->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, true);
        $taxable->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');
        $taxable->addItem($taxableItem);

        $this->matcher->expects(self::exactly(2))
            ->method('match')
            ->willReturn($taxRules);

        $this->unitResolver->expects(self::exactly(2))
            ->method('resolveUnitPrice')
            ->withConsecutive(
                [$taxableItem->getResult(), $taxRules, $taxableItem->getPrice()],
                [$taxable->getResult(), $taxRules, $taxable->getPrice()],
            );

        $this->rowTotalResolver->expects(self::exactly(2))
            ->method('resolveRowTotal')
            ->withConsecutive(
                [$taxableItem->getResult(), $taxRules, $taxableItem->getPrice(), $taxableItem->getQuantity()],
                [$taxable->getResult(), $taxRules, $taxable->getPrice(), $taxable->getQuantity()],
            );

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

        self::assertTrue($taxable->getResult()->isResultLocked());
        self::assertEmpty($taxable->getResult()->getUnit()->getExcludingTax());
        self::assertEmpty($taxable->getResult()->getRow()->getExcludingTax());
    }

    public function testIsApplicable(): void
    {
        $taxable = new Taxable();
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setKitTaxable(true);
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $taxable->setPrice('19.99');
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addContext(Taxable::DIGITAL_PRODUCT, true);
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $taxable->addItem(new Taxable());
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $address = new Address();
        $taxable->setDestination($address);
        $this->resolver->resolve($taxable);

        self::assertFalse($taxable->getResult()->isResultLocked());

        $address->setCountry(new Country('UK'));

        $this->resolver->resolve($taxable);

        self::assertTrue($taxable->getResult()->isResultLocked());
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

        $this->matcher->expects(self::once())
            ->method('match')
            ->willReturn([]);
        $this->rowTotalResolver->expects(self::once())
            ->method('resolveRowTotal');
        $this->unitResolver->expects(self::once())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        self::assertSame($address, $taxable->getTaxationAddress());
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

        $this->matcher->expects(self::never())
            ->method('match');
        $this->rowTotalResolver->expects(self::never())
            ->method('resolveRowTotal');
        $this->unitResolver->expects(self::never())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        self::assertSame($origin, $taxable->getTaxationAddress());
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

        $this->matcher->expects(self::never())
            ->method('match');
        $this->rowTotalResolver->expects(self::never())
            ->method('resolveRowTotal');
        $this->unitResolver->expects(self::never())
            ->method('resolveUnitPrice');

        $this->resolver->resolve($taxable);

        self::assertSame($origin, $taxable->getTaxationAddress());
    }
}
