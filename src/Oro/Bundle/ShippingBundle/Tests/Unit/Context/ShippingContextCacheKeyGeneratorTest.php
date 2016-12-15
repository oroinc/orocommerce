<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
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

class ShippingContextCacheKeyGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingContextCacheKeyGenerator
     */
    protected $generator;

    public function setUp()
    {
        $this->generator = new ShippingContextCacheKeyGenerator();
    }

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

    /**
     * @param ShippingContext $context1
     * @param ShippingContext $context2
     * @param ShippingAddressStub $address1
     * @param ShippingAddressStub $address2
     */
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

        $item1 = new ShippingLineItem();
        $item1->setProductUnit($unit1);

        $item2 = new ShippingLineItem();
        $item2->setProductUnit($unit2);

        $lineItems = new DoctrineShippingLineItemCollection([$item1, $item2]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $this->assertHashEquals($context1, $context2);

        $lineItems = new DoctrineShippingLineItemCollection([$item1]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);
        $this->assertHashEquals($context1, $context2);
        $lineItems = new DoctrineShippingLineItemCollection([$item2]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);
        $this->assertHashEquals($context1, $context2);

        $item1->setQuantity(1);
        $item2->setQuantity(2);

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

        $item1 = new ShippingLineItem();
        $item1->setProductUnit($unit1);

        $item2 = new ShippingLineItem();
        $item2->setProductUnit($unit2);

        $lineItems = new DoctrineShippingLineItemCollection([$item1]);
        $context1 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context1);

        $lineItems = new DoctrineShippingLineItemCollection([$item2]);
        $context2 = $this->createContext([ShippingContext::FIELD_LINE_ITEMS => $lineItems], $context2);

        $item1->setQuantity(1);
        $this->assertHashNotEquals($context1, $context2);
        $item2->setQuantity(2);
        $this->assertHashNotEquals($context1, $context2);
        $item2->setQuantity(1);
        $this->assertHashEquals($context1, $context2);

        $item1->setPrice(Price::create(10, 'USD'));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setPrice(Price::create(11, 'USD'));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setPrice(Price::create(10, 'EUR'));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setPrice(Price::create(10, 'USD'));
        $this->assertHashEquals($context1, $context2);

        $item1->setProduct($this->getEntity(Product::class, ['id' => 1]));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProduct($this->getEntity(Product::class, ['id' => 2]));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProduct($this->getEntity(Product::class, ['id' => 1]));
        $this->assertHashEquals($context1, $context2);

        $item1->setWeight(Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setWeight(Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'lbs'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setWeight(Weight::create(12, $this->getEntity(WeightUnit::class, ['code' => 'kg'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setWeight(Weight::create(10, $this->getEntity(WeightUnit::class, ['code' => 'kg'])));
        $this->assertHashEquals($context1, $context2);

        $holder1 = $this->getMockForAbstractClass(ProductHolderInterface::class);
        $holder1->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn('id');

        $holder2 = $this->getMockForAbstractClass(ProductHolderInterface::class);
        $holder2->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn('wrong_id');

        $item1->setProductHolder($holder1);
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProductHolder($holder2);
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProductHolder($holder1);
        $this->assertHashEquals($context1, $context2);

        $item1->setProductUnit($this->getEntity(ProductUnit::class, ['code' => 'set']));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProductUnit($this->getEntity(ProductUnit::class, ['code' => 'item']));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setProductUnit($this->getEntity(ProductUnit::class, ['code' => 'set']));
        $this->assertHashEquals($context1, $context2);

        $item1->setDimensions(Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setDimensions(Dimensions::create(2, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setDimensions(Dimensions::create(1, 1, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setDimensions(Dimensions::create(1, 2, 1, $this->getEntity(LengthUnit::class, ['code' => 'cm'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setDimensions(Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'inch'])));
        $this->assertHashNotEquals($context1, $context2);
        $item2->setDimensions(Dimensions::create(1, 2, 3, $this->getEntity(LengthUnit::class, ['code' => 'cm'])));
        $this->assertHashEquals($context1, $context2);
    }

    /**
     * @param ShippingContextInterface $context1
     * @param ShippingContextInterface $context2
     */
    protected function assertHashEquals(ShippingContextInterface $context1, ShippingContextInterface $context2)
    {
        $this->assertEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }

    /**
     * @param ShippingContextInterface $context1
     * @param ShippingContextInterface $context2
     */
    protected function assertHashNotEquals(ShippingContextInterface $context1, ShippingContextInterface $context2)
    {
        $this->assertNotEquals($this->generator->generateKey($context1), $this->generator->generateKey($context2));
    }
}
