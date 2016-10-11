<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\EventListener\ExtractAddressOptionsListener;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

class ExtractAddressOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtractAddressOptionsListener */
    private $listener;

    public function setUp()
    {
        $this->listener = new ExtractAddressOptionsListener();
    }
    
    public function tearDown()
    {
        unset($this->listener);
    }

    public function testOnExtractShippingAddressOptions()
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

        $event = new ExtractAddressOptionsEvent($address);
        $this->listener->onExtractShippingAddressOptions($event);

        $addressOptionModel = $event->getModel();

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

    public function testOnExtractShippingAddressOptionsWitEmptyAddress()
    {
        $address = new OrderAddress();

        $event = new ExtractAddressOptionsEvent($address);
        $this->listener->onExtractShippingAddressOptions($event);

        $addressOptionModel = $event->getModel();

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
