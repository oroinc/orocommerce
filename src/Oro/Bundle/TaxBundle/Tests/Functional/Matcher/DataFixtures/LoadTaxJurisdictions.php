<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class LoadTaxJurisdictions extends AbstractFixture
{
    public const REFERENCE_PREFIX = 'tax_jurisdiction_matcher';

    public const JURISDICTION_US_ONLY = 'JURISDICTION_US_ONLY';
    public const JURISDICTION_US_NY_RANGE = 'JURISDICTION_US_NY_RANGE';
    public const JURISDICTION_US_NY_SINGLE = 'JURISDICTION_US_NY_SINGLE';
    public const JURISDICTION_US_LA_RANGE = 'JURISDICTION_US_LA_RANGE';
    public const JURISDICTION_CA_ON_WITHOUT_ZIP = 'JURISDICTION_CA_ON_WITHOUT_ZIP';
    public const JURISDICTION_US_WITH_TEXT_STATE = 'JURISDICTION_US_WITH_TEXT_STATE';

    public const COUNTRY_US = 'US';
    public const COUNTRY_CA = 'CA';

    public const STATE_US_NY = 'US-NY';
    public const STATE_US_LA = 'US-LA';
    public const STATE_CA_ON = 'CA-ON';

    public const STATE_TEXT_SOME = 'Some unknown state';

    public const ZIP_US_NY_RANGE_START = '00150';
    public const ZIP_US_NY_RANGE_END = '00250';
    public const ZIP_US_NY_SINGLE = '00350';
    public const ZIP_US_LA_RANGE_START = '12150';
    public const ZIP_US_LA_RANGE_END = '12250';

    private const DATA = [
        self::JURISDICTION_US_ONLY            => [
            'country' => self::COUNTRY_US
        ],
        self::JURISDICTION_US_NY_RANGE        => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_NY,
            'zipCode' => [self::ZIP_US_NY_RANGE_START, self::ZIP_US_NY_RANGE_END]
        ],
        self::JURISDICTION_US_NY_SINGLE       => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_NY,
            'zipCode' => self::ZIP_US_NY_SINGLE
        ],
        self::JURISDICTION_US_LA_RANGE        => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_LA,
            'zipCode' => [self::ZIP_US_LA_RANGE_START, self::ZIP_US_LA_RANGE_END]
        ],
        self::JURISDICTION_US_WITH_TEXT_STATE => [
            'country'    => self::COUNTRY_US,
            'regionText' => self::STATE_TEXT_SOME
        ],
        self::JURISDICTION_CA_ON_WITHOUT_ZIP  => [
            'country' => self::COUNTRY_CA,
            'region'  => self::STATE_CA_ON
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach (self::DATA as $code => $item) {
            /** @var Country $country */
            $country = $manager->getReference(Country::class, $item['country']);

            $taxJurisdiction = new TaxJurisdiction();
            $taxJurisdiction->setCode($code);
            $taxJurisdiction->setDescription('Tax description');
            $taxJurisdiction->setCountry($country);
            if (isset($item['region'])) {
                /** @var Region $region */
                $region = $manager->getReference(Region::class, $item['region']);
                $taxJurisdiction->setRegion($region);
            } elseif (isset($item['regionText'])) {
                $taxJurisdiction->setRegionText($item['regionText']);
            }
            if (isset($item['zipCode'])) {
                if (\is_array($item['zipCode'])) {
                    $taxJurisdiction->addZipCode(
                        ZipCodeTestHelper::getRangeZipCode($item['zipCode'][0], $item['zipCode'][1])
                    );
                } else {
                    $taxJurisdiction->addZipCode(ZipCodeTestHelper::getSingleValueZipCode($item['zipCode']));
                }
            }
            $manager->persist($taxJurisdiction);
            $this->addReference(static::REFERENCE_PREFIX . '.' . $code, $taxJurisdiction);
        }
        $manager->flush();
    }
}
