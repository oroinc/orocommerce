<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingCountries;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingServices;

class ShippingServiceRepositoryTest extends WebTestCase
{
    private ShippingServiceRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadShippingCountries::class,
            LoadShippingServices::class,
        ]);

        $this->repository = self::getContainer()->get('doctrine')
            ->getRepository(ShippingService::class);
    }

    /**
     * @dataProvider getShippingServicesByCountryDataProvider
     */
    public function testGetShippingServicesByCountry(string $country, array $expectedServices)
    {
        /** @var ShippingService[]|array $expectedShippingServices */
        $expectedShippingServices = $this->getEntitiesByReferences($expectedServices);
        $shippingServices = $this->repository->getShippingServicesByCountry(
            $this->findCountry($country)
        );

        self::assertEquals($expectedShippingServices, $shippingServices);
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

    private function findCountry(string $isoCode): Country
    {
        return self::getContainer()->get('doctrine')
            ->getManagerForClass(Country::class)
            ->find(Country::class, $isoCode);
    }
}
