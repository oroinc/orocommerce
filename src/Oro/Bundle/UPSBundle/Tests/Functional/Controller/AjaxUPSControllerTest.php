<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

/**
 * @dbIsolation
 */
class AjaxUPSControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingServices']);
    }

    /**
     * @dataProvider getShippingServicesByCountryDataProvider
     *
     * @param string $countryCode
     * @param array $expectedServices
     */
    public function testGetShippingServicesByCountry($countryCode, array $expectedServices)
    {
        $this
            ->client
            ->request('GET', $this->getUrl('oro_ups_country_shipping_services', ['code' => $countryCode]));

        $result = static::getJsonResponseContent($this->client->getResponse(), 200);

        /** @var ShippingService[]|array $expectedShippingServices */
        $expectedShippingServices = $this->getEntitiesByReferences($expectedServices);
        $expected = [];
        foreach ($expectedShippingServices as $service) {
            $expected[] = ['id' => $service->getId(), 'description' => $service->getDescription()];
        }

        static::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getShippingServicesByCountryDataProvider()
    {
        return [
            [
                'country' => 'UU',
                'expectedServices' => [
                    'ups.shipping_service.1',
                    'ups.shipping_service.2',
                ]
            ],
            [
                'country' => 'CC',
                'expectedServices' => [
                    'ups.shipping_service.3',
                    'ups.shipping_service.4',
                ]
            ],
            [
                'country' => 'LL',
                'expectedServices' => [
                    'ups.shipping_service.5',
                    'ups.shipping_service.6',
                ]
            ],
        ];
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function getEntitiesByReferences(array $rules)
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }
}
