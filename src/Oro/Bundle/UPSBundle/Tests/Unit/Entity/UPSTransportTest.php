<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class UPSTransportTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new UPSTransport(), [
            ['baseUrl', 'some string'],
            ['apiUser', 'some string'],
            ['apiPassword', 'some string'],
            ['apiKey', 'some string'],
            ['shippingAccountNumber', 'some string'],
            ['shippingAccountName', 'some string'],
            ['country', new Country('US')]
        ]);
        static::assertPropertyCollections(new UPSTransport(), [
            ['applicableShippingServices', new ShippingService()],
        ]);
    }

    public function testGetSettingsBag()
    {
        $entity = $this->getEntity(
            'Oro\Bundle\UPSBundle\Entity\UPSTransport',
            [
                'baseUrl' => 'some url',
                'apiUser' => 'some user',
                'apiPassword' => 'some password',
                'apiKey' => 'some key',
                'shippingAccountNumber' => 'some number',
                'shippingAccountName' => 'some name',
                'pickupType' => '01',
                'unitOfWeight' => 'LPS',
                'country' => new Country('US'),
                'applicableShippingServices' => [new ShippingService()]
            ]
        );

        /** @var ParameterBag $result */
        $result = $entity->getSettingsBag();
        static::assertEquals($result->get('base_url'), 'some url');
        static::assertEquals($result->get('api_user'), 'some user');
        static::assertEquals($result->get('api_password'), 'some password');
        static::assertEquals($result->get('api_key'), 'some key');
        static::assertEquals($result->get('shipping_account_number'), 'some number');
        static::assertEquals($result->get('shipping_account_name'), 'some name');
        static::assertEquals($result->get('pickup_type'), '01');
        static::assertEquals($result->get('unit_of_weight'), 'LPS');
        static::assertEquals($result->get('country'), new Country('US'));
        static::assertEquals(
            $result->get('applicable_shipping_services'),
            $entity->getApplicableShippingServices()->toArray()
        );
    }
}
