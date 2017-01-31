<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures\LoadRates;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\Rate;

/**
 * @dbIsolation
 */
class RateRepositoryTest extends WebTestCase
{
    /**
     * @var RateRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadRates::class,
        ]);

        $this->repository = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroDPDBundle:Rate')->getRepository('OroDPDBundle:Rate');
    }

    /**
     * @dataProvider getShippingServicesByCountryDataProvider
     *
     * @param $transport
     * @param $shippingService
     * @param string $country
     * @param $region
     * @param array $expectedRate
     */
    public function testFindRatesByServiceAndDestinationQuery($transport, $shippingService, $country, $region, array $expectedRate)
    {
        /** @var Rate $expectedRate */
        $expectedRate = $this->getEntitiesByReferences($expectedRate);

        //static::assertEquals($expectedShippingServices, $shippingServices);
    }

    /**
     * @return array
     */
    public function getShippingServicesByCountryDataProvider()
    {
        return [
            [
                'transport' => 'dpd.transport.1',
                'shippingService' => 'dpd.shipping_service.1',
                'country' => 'ZX',
                'expectedRate' => [
                    'dpd.rate.1',
                ],
            ],
        ];
    }

    /**
     * @param array $rules
     *
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
     *
     * @return Country
     */
    protected function findCountry($isoCode)
    {
        return static::getContainer()->get('doctrine')
            ->getManagerForClass('OroAddressBundle:Country')
            ->find('OroAddressBundle:Country', $isoCode);
    }
}
