<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderShippingAddressOptionsProvider;
use PHPUnit\Framework\TestCase;

class PaymentOrderShippingAddressOptionsProviderTest extends TestCase
{
    /**
     * @var PaymentOrderShippingAddressOptionsProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->provider = new PaymentOrderShippingAddressOptionsProvider();
    }

    public function testGetShippingAddressOptions(): void
    {
        $region = new Region('Region');
        $region->setCode('regionCode');

        $address = new OrderAddress();
        $address
            ->setFirstName('First name')
            ->setLastName('Last name')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setCity('City')
            ->setPostalCode('Postal code')
            ->setRegion($region)
            ->setCountry(new Country('IsoCode'));

        $addressOptionModel = $this->provider->getShippingAddressOptions($address);

        $this->assertInstanceOf(AddressOptionModel::class, $addressOptionModel);
        $this->assertEquals('First name', $addressOptionModel->getFirstName());
        $this->assertEquals('Last name', $addressOptionModel->getLastName());
        $this->assertEquals('Street', $addressOptionModel->getStreet());
        $this->assertEquals('Street2', $addressOptionModel->getStreet2());
        $this->assertEquals('City', $addressOptionModel->getCity());
        $this->assertEquals('Postal code', $addressOptionModel->getPostalCode());
        $this->assertEquals('regionCode', $addressOptionModel->getRegionCode());
        $this->assertEquals('IsoCode', $addressOptionModel->getCountryIso2());
    }

    public function testGetShippingAddressOptionsWitEmptyAddress(): void
    {
        $address = new OrderAddress();

        $addressOptionModel = $this->provider->getShippingAddressOptions($address);

        $this->assertInstanceOf(AddressOptionModel::class, $addressOptionModel);
        $this->assertEquals('', $addressOptionModel->getFirstName());
        $this->assertEquals('', $addressOptionModel->getLastName());
        $this->assertEquals('', $addressOptionModel->getStreet());
        $this->assertEquals('', $addressOptionModel->getStreet2());
        $this->assertEquals('', $addressOptionModel->getCity());
        $this->assertEquals('', $addressOptionModel->getPostalCode());
        $this->assertEquals('', $addressOptionModel->getRegionCode());
        $this->assertEquals('', $addressOptionModel->getCountryIso2());
    }
}
