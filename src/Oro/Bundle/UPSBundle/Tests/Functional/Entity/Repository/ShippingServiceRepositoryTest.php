<?php

namespace Oro\Bundle\UPSBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingCountries;
use Oro\Bundle\UPSBundle\Tests\Functional\DataFixtures\LoadShippingServices;

/**
 * @dbIsolation
 */
class ShippingServiceRepositoryTest extends WebTestCase
{
    /**
     * @var ShippingServiceRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadShippingCountries::class,
            LoadShippingServices::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')->getRepository('OroUPSBundle:ShippingService');
    }

    /**
     * @dataProvider getShippingServicesByCountryDataProvider
     *
     * @param string $country
     * @param array $expectedServices
     */
    public function testGetShippingServicesByCountry($country, array $expectedServices)
    {
        /** @var ShippingService[]|array $expectedShippingServices */
        $expectedShippingServices = $this->getEntitiesByReferences($expectedServices);
        $shippingServices = $this->repository->getShippingServicesByCountry(
            $this->findCountry($country)
        );

        static::assertEquals($expectedShippingServices, $shippingServices);
    }

    /**
     * @return array
     */
    public function getShippingServicesByCountryDataProvider()
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

    /**
     * @param string $isoCode
     * @return Country
     */
    protected function findCountry($isoCode)
    {
        return static::getContainer()->get('doctrine')
            ->getManagerForClass('OroAddressBundle:Country')
            ->find('OroAddressBundle:Country', $isoCode);
    }
}
