<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class ShippingContextCacheKeyGeneratorTest extends TestCase
{
    use EntityTrait;
    use ShippingLineItemTrait;

    private ShippingContextCacheKeyGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new ShippingContextCacheKeyGenerator();
    }

    private function createContext(array $params, ?ShippingContext $context = null): ShippingContext
    {
        $actualParams = $params;

        if (null === $context) {
            $actualParams[ShippingContext::FIELD_LINE_ITEMS] = new ArrayCollection([]);
        } else {
            $actualParams = array_merge($context->all(), $actualParams);
        }

        return new ShippingContext($actualParams);
    }

    private function createContextWithLineItems(array $lineItems, ?ShippingContext $context = null): ShippingContext
    {
        return $this->createContext(
            [
                ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection($lineItems),
            ],
            $context
        );
    }

    public function testGenerateHashSimpleFields(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        self::assertEquals(crc32(''), $this->generator->generateKey($context1));
        self::assertEquals(crc32(''), $this->generator->generateKey($context2));

        $context1 = $this->createContext([ShippingContext::FIELD_CURRENCY => 'USD'], $context1);
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_CURRENCY => 'EUR'], $context2);
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_CURRENCY => 'USD'], $context2);
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContext([ShippingContext::FIELD_PAYMENT_METHOD => 'payment_method'], $context1);
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext(
            [ShippingContext::FIELD_PAYMENT_METHOD => 'another_payment_method'],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_PAYMENT_METHOD => 'payment_method'], $context2);
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => new Price()], $context1);
        $this->assertHashEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => new Price()], $context2);
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => Price::create(10, 'USD')], $context1);
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => Price::create(11, 'USD')], $context2);
        $this->assertHashNotEquals($context1, $context2);
        $context1 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => Price::create(10, 'USD')], $context1);
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_SUBTOTAL => Price::create(10, 'USD')], $context2);
        $this->assertHashEquals($context1, $context2);
    }

    public function testGenerateHashBillingAddress(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $address1 = new ShippingAddressStub();
        $address2 = new ShippingAddressStub();

        $context1 = $this->createContext([ShippingContext::FIELD_BILLING_ADDRESS => $address1], $context1);
        $this->assertHashEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_BILLING_ADDRESS => $address2], $context2);
        $this->assertHashEquals($context1, $context2);

        $this->assertAddressesFieldAffectsHash($context1, $context2, $address1, $address2);
    }

    public function testGenerateHashShippingAddress(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $address1 = new ShippingAddressStub();
        $address2 = new ShippingAddressStub();

        $context1 = $this->createContext([ShippingContext::FIELD_SHIPPING_ADDRESS => $address1], $context1);
        $this->assertHashEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_SHIPPING_ADDRESS => $address2], $context2);
        $this->assertHashEquals($context1, $context2);

        $this->assertAddressesFieldAffectsHash($context1, $context2, $address1, $address2);
    }

    public function testGenerateHashShippingOrigin(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $address1 = new ShippingAddressStub();
        $address2 = new ShippingAddressStub();

        $context1 = $this->createContext([ShippingContext::FIELD_SHIPPING_ORIGIN => $address1], $context1);
        $this->assertHashEquals($context1, $context2);
        $context2 = $this->createContext([ShippingContext::FIELD_SHIPPING_ORIGIN => $address2], $context2);
        $this->assertHashEquals($context1, $context2);

        $this->assertAddressesFieldAffectsHash($context1, $context2, $address1, $address2);
    }

    protected function assertAddressesFieldAffectsHash(
        ShippingContext $context1,
        ShippingContext $context2,
        ShippingAddressStub $address1,
        ShippingAddressStub $address2
    ): void {
        $address1->setStreet('street');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setStreet('another_street');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setStreet('street');
        $this->assertHashEquals($context1, $context2);

        $address1->setStreet2('street2');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setStreet2('another_street2');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setStreet2('street2');
        $this->assertHashEquals($context1, $context2);

        $address1->setCity('city');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setCity('another_city');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setCity('city');
        $this->assertHashEquals($context1, $context2);

        $address1->setRegionText('region');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setRegionText('another_region');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setRegionText('region');
        $this->assertHashEquals($context1, $context2);

        $address1->setRegion((new Region(1))->setCode(1));
        $this->assertHashNotEquals($context1, $context2);
        $address2->setRegion((new Region(2))->setCode(2));
        $this->assertHashNotEquals($context1, $context2);
        $address2->setRegion((new Region(1))->setCode(1));
        $this->assertHashEquals($context1, $context2);

        $address1->setPostalCode('postal_code');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setPostalCode('another_postal_code');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setPostalCode('postal_code');
        $this->assertHashEquals($context1, $context2);

        $country1 = new Country('postal_code');
        $country2 = new Country('postal_code');

        $address1->setCountry($country1);
        $this->assertHashNotEquals($context1, $context2);
        $address2->setCountry(new Country('wrong_postal_code'));
        $this->assertHashNotEquals($context1, $context2);
        $address2->setCountry($country2);
        $this->assertHashEquals($context1, $context2);

        $country1->setName('postal_code');
        $this->assertHashNotEquals($context1, $context2);
        $country2->setName('another_postal_code');
        $this->assertHashNotEquals($context1, $context2);
        $country2->setName('postal_code');
        $this->assertHashEquals($context1, $context2);

        $country1->setIso3Code('code');
        $this->assertHashNotEquals($context1, $context2);
        $country2->setIso3Code('another_code');
        $this->assertHashNotEquals($context1, $context2);
        $country2->setIso3Code('code');
        $this->assertHashEquals($context1, $context2);

        $address1->setOrganization('organization');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setOrganization('another_organization');
        $this->assertHashNotEquals($context1, $context2);
        $address2->setOrganization('organization');
        $this->assertHashEquals($context1, $context2);
    }

    public function testGenerateHashLineItemsOrder(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $unit1 = (new ProductUnit())
            ->setCode('code');
        $unit2 = (new ProductUnit())
            ->setCode('code');

        $item1 = $this->getShippingLineItem(productUnit: $unit1);
        $item2 = $this->getShippingLineItem(productUnit: $unit2);

        $lineItems = new ArrayCollection([$item1]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $lineItems = new ArrayCollection([$item2]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);

        $item1 = $this->getShippingLineItem($unit1, 1);
        $item2 = $this->getShippingLineItem($unit2, 2);

        $lineItems = new ArrayCollection([$item1, $item2]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);
        $lineItems = new ArrayCollection([$item2, $item1]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerateHashLineItems(): void
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $unit1 = (new ProductUnit())
            ->setCode('code');
        $unit2 = (new ProductUnit())
            ->setCode('code');

        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)],
            $context1
        );
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit2)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit2, 2)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit2, 1)],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setPrice(Price::create(10, 'USD'))],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setPrice(Price::create(11, 'USD'))],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setPrice(Price::create(10, 'EUR'))],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setPrice(Price::create(10, 'USD'))],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setProduct($this->getEntity(Product::class, ['id' => 1]))],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setProduct($this->getEntity(Product::class, ['id' => 2]))],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setProduct($this->getEntity(Product::class, ['id' => 1]))],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setWeight($weight)],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'lbs']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setWeight($weight)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(12, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setWeight($weight)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)->setWeight($weight)],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);

        $shippingLineItem = $this->getShippingLineItem($unit1, 1);
        $shippingLineItem->set(ShippingLineItem::FIELD_ENTITY_IDENTIFIER, 2);
        $context2 = $this->createContextWithLineItems(
            [$shippingLineItem],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem($unit1, 1)],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem(unitCode: 'set')],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem(unitCode: 'item')],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem(unitCode: 'set')],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context1 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(2, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 1, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 1, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'inch']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [$this->getShippingLineItem()->setDimensions($dimensions)],
            $context2
        );

        $this->assertHashEquals($context1, $context2);
    }

    protected function assertHashEquals(ShippingContextInterface $context1, ShippingContextInterface $context2): void
    {
        self::assertEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }

    protected function assertHashNotEquals(ShippingContextInterface $context1, ShippingContextInterface $context2): void
    {
        self::assertNotEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }
}
