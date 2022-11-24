<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;

class LoadTaxJurisdictions extends AbstractFixture
{
    public const REFERENCE_PREFIX = 'tax_jurisdiction';

    public const COUNTRY_US = 'US';
    public const STATE_US_NY = 'US-NY';
    public const STATE_US_CA = 'US-CA';
    public const STATE_US_AL = 'US-AL';

    public const ZIP_CODE = '012345';

    private const DATA = [
        LoadTaxes::TAX_1 => [
            'country' => self::COUNTRY_US
        ],
        LoadTaxes::TAX_2 => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_NY
        ],
        LoadTaxes::TAX_3 => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_CA
        ],
        LoadTaxes::TAX_4 => [
            'country' => self::COUNTRY_US,
            'region'  => self::STATE_US_AL,
            'zipCode' => self::ZIP_CODE
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
            }
            if (isset($item['zipCode'])) {
                $taxJurisdiction->addZipCode(ZipCodeTestHelper::getSingleValueZipCode($item['zipCode']));
            }
            $manager->persist($taxJurisdiction);
            $this->addReference(static::REFERENCE_PREFIX . '.' . $code, $taxJurisdiction);
        }
        $manager->flush();
    }
}
