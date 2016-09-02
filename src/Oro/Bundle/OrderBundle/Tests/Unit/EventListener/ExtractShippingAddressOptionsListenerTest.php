<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\EventListener\ExtractShippingAddressOptionsListener;
use Oro\Bundle\PaymentBundle\Event\ExtractShippingAddressOptionsEvent;

class ExtractShippingAddressOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnExtractShippingAddressOptions()
    {
        $address = new OrderAddress();
        $expected = [
            'key1' => 'First name',
            'key2' => 'Last name',
            'key3' => 'Street',
            'key4' => 'Street2',
            'key5' => 'City',
            'key6' => null,
            'key7' => null,
            'key8' => null
        ];
        $address->setFirstName($expected['key1']);
        $address->setLastName($expected['key2']);
        $address->setStreet($expected['key3']);
        $address->setStreet2($expected['key4']);
        $address->setCity($expected['key5']);
        $address->setCountry($expected['key8']);

        $event = new ExtractShippingAddressOptionsEvent($address, array_keys($expected));
        $listener = new ExtractShippingAddressOptionsListener();
        $listener->onExtractShippingAddressOptions($event);
        $this->assertEquals($expected, $event->getOptions());
    }
}
