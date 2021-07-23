<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
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

class ShippingContextCacheKeyGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ShippingContextCacheKeyGenerator
     */
    protected $generator;

    protected function setUp(): void
    {
        $this->generator = new ShippingContextCacheKeyGenerator();
    }

    /**
     * @param $params
     * @param ShippingContext|null $context
     *
     * @return ShippingContext
     */
    private function createContext($params, ShippingContext $context = null)
    {
        $actualParams = $params;

        if (null === $context) {
            $actualParams[ShippingContext::FIELD_LINE_ITEMS] = new DoctrineShippingLineItemCollection([]);
        } else {
            $actualParams = array_merge($context->all(), $actualParams);
        }

        return new ShippingContext($actualParams);
    }

    /**
     * @param array $lineItemsParams
     * @param ShippingContext|null $context
     *
     * @return ShippingContext
     */
    private function createContextWithLineItems(array $lineItemsParams, ShippingContext $context = null)
    {
        $lineItems = [];
        foreach ($lineItemsParams as $params) {
            $lineItems[] = new ShippingLineItem($params);
        }

        return $this->createContext(
            [
                ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($lineItems),
            ],
            $context
        );
    }

    public function testGenerateHashSimpleFields()
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $this->assertEquals(crc32(''), $this->generator->generateKey($context1));
        $this->assertEquals(crc32(''), $this->generator->generateKey($context2));

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

    public function testGenerateHashBillingAddress()
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

    public function testGenerateHashShippingAddress()
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

    public function testGenerateHashShippingOrigin()
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
    ) {
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

    public function testGenerateHashLineItemsOrder()
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $unit1 = new ProductUnit();
        $unit2 = new ProductUnit();

        $item1 = new ShippingLineItem([ShippingLineItem::FIELD_PRODUCT_UNIT => $unit1]);
        $item2 = new ShippingLineItem([ShippingLineItem::FIELD_PRODUCT_UNIT => $unit2]);

        $lineItems = new DoctrineShippingLineItemCollection([$item1, $item2]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $this->assertHashEquals($context1, $context2);

        $lineItems = new DoctrineShippingLineItemCollection([$item1]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $this->assertHashEquals($context1, $context2);
        $lineItems = new DoctrineShippingLineItemCollection([$item2]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);

        $item1 = new ShippingLineItem(
            [ShippingLineItem::FIELD_PRODUCT_UNIT => $unit1, ShippingLineItem::FIELD_QUANTITY => 1]
        );
        $item2 = new ShippingLineItem(
            [ShippingLineItem::FIELD_PRODUCT_UNIT => $unit2, ShippingLineItem::FIELD_QUANTITY => 2]
        );

        $lineItems = new DoctrineShippingLineItemCollection([$item1, $item2]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);
        $lineItems = new DoctrineShippingLineItemCollection([$item2, $item1]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerateHashLineItems()
    {
        $context1 = $this->createContext([]);
        $context2 = $this->createContext([]);

        $unit1 = new ProductUnit();
        $unit2 = new ProductUnit();

        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT => $unit1, ShippingLineItem::FIELD_QUANTITY => 1]],
            $context1
        );
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT => $unit2]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT => $unit2, ShippingLineItem::FIELD_QUANTITY => 2]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT => $unit2, ShippingLineItem::FIELD_QUANTITY => 1]],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRICE => Price::create(10, 'USD')]],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRICE => Price::create(11, 'USD')]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRICE => Price::create(10, 'EUR')]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRICE => Price::create(10, 'USD')]],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 1])]],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 2])]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 1])]],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_WEIGHT => $weight]],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'lbs']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_WEIGHT => $weight]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(12, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_WEIGHT => $weight]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $weight = Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_WEIGHT => $weight]],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 1]],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 2]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 1]],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'set']],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'item']],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'set']],
            $context2
        );
        $this->assertHashEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context1 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context1
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(2, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 1, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 1, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'inch']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context2
        );
        $this->assertHashNotEquals($context1, $context2);

        $dimensions = Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm']));
        $context2 = $this->createContextWithLineItems(
            [[ShippingLineItem::FIELD_DIMENSIONS => $dimensions]],
            $context2
        );

        $this->assertHashEquals($context1, $context2);
    }

    protected function assertHashEquals(ShippingContextInterface $context1, ShippingContextInterface $context2)
    {
        $this->assertEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }

    protected function assertHashNotEquals(ShippingContextInterface $context1, ShippingContextInterface $context2)
    {
        $this->assertNotEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }
}
