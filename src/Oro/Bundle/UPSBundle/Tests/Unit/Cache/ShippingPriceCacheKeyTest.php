<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Cache;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCacheKey;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Tests\Unit\Cache\Stub\PriceRequestAddressStub;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingPriceCacheKeyTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(new ShippingPriceCacheKey(), [
            ['transport', $this->getEntity(UPSTransport::class, ['id' => 1])],
            ['priceRequest', new PriceRequest()],
            ['methodId', 'method'],
            ['typeId', 'type'],
        ]);
    }

    public function testGenerateKey()
    {
        $key1 = new ShippingPriceCacheKey();
        $key2 = new ShippingPriceCacheKey();
        $request1 = new PriceRequest();
        $key1->setPriceRequest($request1);
        $request2 = new PriceRequest();
        $key2->setPriceRequest($request2);

        $this->assertKeysEquals($key1, $key2);

        $key1->setMethodId('method');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('wrong_method');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('method');
        $this->assertKeysEquals($key1, $key2);

        $key1->setMethodId('type');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('wrong_type');
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setMethodId('type');
        $this->assertKeysEquals($key1, $key2);

        $key1->setTransport($this->getEntity(UPSTransport::class, ['id' => 1]));
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setTransport($this->getEntity(UPSTransport::class, ['id' => 2]));
        $this->assertKeysNotEquals($key1, $key2);
        $key2->setTransport($this->getEntity(UPSTransport::class, ['id' => 1]));
        $this->assertKeysEquals($key1, $key2);

        $request1->setService('03', 'Ground');
        $this->assertKeysEquals($key1, $key2);

        $request2->setService('03', 'Ground');
        $this->assertKeysEquals($key1, $key2);

        $request1->setUsername('username');
        $this->assertKeysEquals($key1, $key2);
        $request2->setUsername('wrong_username');
        $this->assertKeysEquals($key1, $key2);
        $request2->setUsername('username');
        $this->assertKeysEquals($key1, $key2);

        $request1->setPassword('password');
        $this->assertKeysEquals($key1, $key2);
        $request2->setPassword('wrong_password');
        $this->assertKeysEquals($key1, $key2);
        $request2->setPassword('password');
        $this->assertKeysEquals($key1, $key2);

        $request1->setAccessLicenseNumber('licence_number');
        $this->assertKeysEquals($key1, $key2);
        $request2->setAccessLicenseNumber('wrong_licence_number');
        $this->assertKeysEquals($key1, $key2);
        $request2->setAccessLicenseNumber('licence_number');
        $this->assertKeysEquals($key1, $key2);

        $request1->setRequestOption('rate');
        $this->assertKeysEquals($key1, $key2);
        $request2->setRequestOption('wrong_rate');
        $this->assertKeysEquals($key1, $key2);
        $request2->setRequestOption('rate');
        $this->assertKeysEquals($key1, $key2);

        $package1 = new Package();
        $package2 = new Package();

        $request1->addPackage($package1);
        $request1->addPackage($package2);
        $this->assertKeysNotEquals($key1, $key2);
        $request2->addPackage($package1);
        $this->assertKeysNotEquals($key1, $key2);
        $request2->addPackage($package2);
        $this->assertKeysEquals($key1, $key2);
    }

    public function testGenerateKeyShipFrom()
    {
        $key1 = new ShippingPriceCacheKey();
        $key2 = new ShippingPriceCacheKey();
        $request1 = new PriceRequest();
        $key1->setPriceRequest($request1);
        $request2 = new PriceRequest();
        $key2->setPriceRequest($request2);

        $address1 = new PriceRequestAddressStub();
        $address2 = new PriceRequestAddressStub();

        $request1->setShipFrom('Ship from name', $address1);
        $request2->setShipFrom('Ship from name', $address2);
        $this->assertKeysEquals($key1, $key2);

        $this->assertAddressesFieldAffectsKey($key1, $key2, $address1, $address2);
    }

    public function testGenerateKeyShipper()
    {
        $key1 = new ShippingPriceCacheKey();
        $key2 = new ShippingPriceCacheKey();
        $request1 = new PriceRequest();
        $key1->setPriceRequest($request1);
        $request2 = new PriceRequest();
        $key2->setPriceRequest($request2);

        $address1 = new PriceRequestAddressStub();
        $address2 = new PriceRequestAddressStub();

        $request1->setShipper('Shipper name', '12433', $address1);
        $request2->setShipper('Shipper name', '12433', $address2);
        $this->assertKeysEquals($key1, $key2);

        $this->assertAddressesFieldAffectsKey($key1, $key2, $address1, $address2);
    }

    public function testGenerateKeyShipTo()
    {
        $key1 = new ShippingPriceCacheKey();
        $key2 = new ShippingPriceCacheKey();
        $request1 = new PriceRequest();
        $key1->setPriceRequest($request1);
        $request2 = new PriceRequest();
        $key2->setPriceRequest($request2);

        $address1 = new PriceRequestAddressStub();
        $address2 = new PriceRequestAddressStub();

        $request1->setShipTo('Ship to name', $address1);
        $request2->setShipTo('Ship to name', $address2);
        $this->assertKeysEquals($key1, $key2);

        $this->assertAddressesFieldAffectsKey($key1, $key2, $address1, $address2);
    }

    protected function assertAddressesFieldAffectsKey(
        ShippingPriceCacheKey $key1,
        ShippingPriceCacheKey $key2,
        PriceRequestAddressStub $address1,
        PriceRequestAddressStub $address2
    ) {
        $address1->setStreet('street');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setStreet('another_street');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setStreet('street');
        $this->assertKeysEquals($key1, $key2);

        $address1->setStreet2('street2');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setStreet2('another_street2');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setStreet2('street2');
        $this->assertKeysEquals($key1, $key2);

        $address1->setCity('city');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setCity('another_city');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setCity('city');
        $this->assertKeysEquals($key1, $key2);

        $address1->setRegion((new Region(1))->setCode(1));
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setRegion((new Region(2))->setCode(2));
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setRegion((new Region(1))->setCode(1));
        $this->assertKeysEquals($key1, $key2);

        $address1->setPostalCode('postal_code');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setPostalCode('another_postal_code');
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setPostalCode('postal_code');
        $this->assertKeysEquals($key1, $key2);

        $country1 = new Country('postal_code');
        $country2 = new Country('postal_code');

        $address1->setCountry($country1);
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setCountry(new Country('wrong_postal_code'));
        $this->assertKeysNotEquals($key1, $key2);
        $address2->setCountry($country2);
        $this->assertKeysEquals($key1, $key2);
    }

    protected function assertKeysEquals(ShippingPriceCacheKey $key1, ShippingPriceCacheKey $key2)
    {
        $this->assertEquals($key1->generateKey(), $key2->generateKey());
    }

    protected function assertKeysNotEquals(ShippingPriceCacheKey $key1, ShippingPriceCacheKey $key2)
    {
        $this->assertNotEquals($key1->generateKey(), $key2->generateKey());
    }
}
