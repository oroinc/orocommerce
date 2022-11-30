<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingServices;

class AjaxUPSControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadShippingServices::class]);
    }

    /**
     * @dataProvider getShippingServicesByCountryDataProvider
     */
    public function testGetShippingServicesByCountryAction(string $countryCode, array $expectedServices)
    {
        $this->client->request('GET', $this->getUrl('oro_ups_country_shipping_services', ['code' => $countryCode]));

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        /** @var ShippingService[]|array $expectedShippingServices */
        $expectedShippingServices = $this->getEntitiesByReferences($expectedServices);
        $expected = [];
        foreach ($expectedShippingServices as $service) {
            $expected[] = ['id' => $service->getId(), 'description' => $service->getDescription()];
        }

        self::assertEquals($expected, $result);
    }

    public function getShippingServicesByCountryDataProvider(): array
    {
        return [
            [
                'country' => 'ZX',
                'expectedServices' => [
                    'ups.shipping_service.1',
                    'ups.shipping_service.2',
                ]
            ],
            [
                'country' => 'ZY',
                'expectedServices' => [
                    'ups.shipping_service.3',
                    'ups.shipping_service.4',
                ]
            ],
            [
                'country' => 'ZZ',
                'expectedServices' => [
                    'ups.shipping_service.5',
                    'ups.shipping_service.6',
                ]
            ],
        ];
    }

    private function getEntitiesByReferences(array $rules): array
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }
}
