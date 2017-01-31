<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures\LoadRates;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
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
     * @dataProvider testFindRatesByServiceAndDestinationDataProvider
     *
     * @param string $transportRef
     * @param string $shippingServiceRef
     * @param string $countryRef
     * @param string $regionRef
     * @param array  $expectedRatesRefs
     */
    public function testFindRatesByServiceAndDestination(
        $transportRef,
        $shippingServiceRef,
        $countryRef,
        $regionRef,
        array $expectedRatesRefs
    ) {
        /** @var Rate[] $expectedRates */
        $expectedRates = $this->getEntitiesByReferences($expectedRatesRefs);

        $transport = $this->getReference($transportRef);
        $shippingService = $this->getReference($shippingServiceRef);
        /** @var Country $country */
        $country = $this->getReference($countryRef);
        /** @var Region $region */
        $region = $this->getReference($regionRef);

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddress
            ->expects(self::any())
            ->method('getCountryIso2')
            ->willReturn($country->getIso2Code());
        $shippingAddress
            ->expects(self::any())
            ->method('getRegionCode')
            ->willReturn($region->getCode());

        $rates = $this->repository->findRatesByServiceAndDestination($transport, $shippingService, $shippingAddress);

        static::assertEquals($expectedRates, $rates);
    }

    /**
     * @dataProvider testFindRatesByServiceAndDestinationDataProvider
     *
     * @param string $transportRef
     * @param string $shippingServiceRef
     * @param string $countryRef
     * @param string $regionRef
     * @param array  $expectedRatesRefs
     */
    public function testFindFirstRateByServiceAndDestination(
        $transportRef,
        $shippingServiceRef,
        $countryRef,
        $regionRef,
        array $expectedRatesRefs
    ) {
        /** @var Rate $expectedRate */
        $expectedRate = $this->getEntitiesByReferences($expectedRatesRefs)[0];

        $transport = $this->getReference($transportRef);
        $shippingService = $this->getReference($shippingServiceRef);
        /** @var Country $country */
        $country = $this->getReference($countryRef);
        /** @var Region $region */
        $region = $this->getReference($regionRef);

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddress
            ->expects(self::any())
            ->method('getCountryIso2')
            ->willReturn($country->getIso2Code());
        $shippingAddress
            ->expects(self::any())
            ->method('getRegionCode')
            ->willReturn($region->getCode());

        $rate = $this->repository->findFirstRateByServiceAndDestination($transport, $shippingService, $shippingAddress);

        static::assertEquals($expectedRate, $rate);
    }

    /**
     * @return array
     */
    public function testFindRatesByServiceAndDestinationDataProvider()
    {
        return [
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.1',
                'expectedRatesRefs' => [
                    'dpd.rate.1',
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.2',
                'expectedRatesRefs' => [
                    'dpd.rate.2',
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.3',
                'expectedRatesRefs' => [
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.2',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.3',
                'expectedRatesRefs' => [
                    'dpd.rate.4',
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
