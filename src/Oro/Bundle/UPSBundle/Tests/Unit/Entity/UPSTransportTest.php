<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class UPSTransportTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new UPSTransport(), [
            ['upsTestMode', true],
            ['upsApiUser', 'some string'],
            ['upsApiPassword', 'some string'],
            ['upsApiKey', 'some string'],
            ['upsShippingAccountNumber', 'some string'],
            ['upsShippingAccountName', 'some string'],
            ['upsCountry', new Country('US')],
            ['upsInvalidateCacheAt', new \DateTime('2020-01-01')],
        ]);
        static::assertPropertyCollections(new UPSTransport(), [
            ['applicableShippingServices', new ShippingService()],
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        $entity = $this->getEntity(
            'Oro\Bundle\UPSBundle\Entity\UPSTransport',
            [
                'upsTestMode' => true,
                'upsApiUser' => 'some user',
                'upsApiPassword' => 'some password',
                'upsApiKey' => 'some key',
                'upsShippingAccountNumber' => 'some number',
                'upsShippingAccountName' => 'some name',
                'upsPickupType' => '01',
                'upsUnitOfWeight' => 'LPS',
                'upsCountry' => new Country('US'),
                'upsInvalidateCacheAt' => new \DateTime('2020-01-01'),
                'applicableShippingServices' => [new ShippingService()],
                'labels' => [(new LocalizedFallbackValue())->setString('UPS')],
            ]
        );

        /** @var ParameterBag $result */
        $result = $entity->getSettingsBag();

        static::assertTrue($result->get('test_mode'));
        static::assertEquals('some user', $result->get('api_user'));
        static::assertEquals('some password', $result->get('api_password'));
        static::assertEquals('some key', $result->get('api_key'));
        static::assertEquals('some number', $result->get('shipping_account_number'));
        static::assertEquals('some name', $result->get('shipping_account_name'));
        static::assertEquals('01', $result->get('pickup_type'));
        static::assertEquals('LPS', $result->get('unit_of_weight'));
        static::assertEquals(new Country('US'), $result->get('country'));
        static::assertEquals(new \DateTime('2020-01-01'), $result->get('invalidate_cache_at'));

        static::assertEquals(
            $result->get('applicable_shipping_services'),
            $entity->getApplicableShippingServices()->toArray()
        );
        static::assertEquals(
            $result->get('labels'),
            $entity->getLabels()->toArray()
        );
    }
}
