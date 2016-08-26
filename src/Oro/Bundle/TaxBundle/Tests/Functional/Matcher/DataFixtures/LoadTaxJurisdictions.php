<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions as BaseLoadTaxJurisdictions;

class LoadTaxJurisdictions extends BaseLoadTaxJurisdictions
{
    const REFERENCE_PREFIX = 'tax_jurisdiction_matcher';

    const JURISDICTION_US_ONLY = 'JURISDICTION_US_ONLY';
    const JURISDICTION_US_NY_RANGE = 'JURISDICTION_US_NY_RANGE';
    const JURISDICTION_US_NY_SINGLE = 'JURISDICTION_US_NY_SINGLE';
    const JURISDICTION_US_LA_RANGE = 'JURISDICTION_US_LA_RANGE';
    const JURISDICTION_CA_ON_WITHOUT_ZIP = 'JURISDICTION_CA_ON_WITHOUT_ZIP';
    const JURISDICTION_US_WITH_TEXT_STATE = 'JURISDICTION_US_WITH_TEXT_STATE';

    const COUNTRY_CA = 'CA';

    const STATE_US_LA = 'US-LA';
    const STATE_CA_ON = 'CA-ON';

    const STATE_TEXT_SOME = 'Some unknown state';

    const ZIP_US_NY_RANGE_START = '00150';
    const ZIP_US_NY_RANGE_END = '00250';
    const ZIP_US_NY_SINGLE = '00350';
    const ZIP_US_LA_RANGE_START = '12150';
    const ZIP_US_LA_RANGE_END = '12250';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_US_ONLY,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_US)
        );

        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_US_NY_RANGE,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_US),
            $this->getRegionByCode($manager, self::STATE_US_NY),
            null,
            ZipCodeTestHelper::getRangeZipCode(self::ZIP_US_NY_RANGE_START, self::ZIP_US_NY_RANGE_END)
        );

        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_US_NY_SINGLE,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_US),
            $this->getRegionByCode($manager, self::STATE_US_NY),
            null,
            ZipCodeTestHelper::getSingleValueZipCode(self::ZIP_US_NY_SINGLE)
        );

        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_US_LA_RANGE,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_US),
            $this->getRegionByCode($manager, self::STATE_US_LA),
            null,
            ZipCodeTestHelper::getRangeZipCode(self::ZIP_US_LA_RANGE_START, self::ZIP_US_LA_RANGE_END)
        );

        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_US_WITH_TEXT_STATE,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_US),
            null,
            self::STATE_TEXT_SOME
        );

        $this->createTaxJurisdiction(
            $manager,
            self::JURISDICTION_CA_ON_WITHOUT_ZIP,
            self::DESCRIPTION,
            $this->getCountryByCode($manager, self::COUNTRY_CA),
            $this->getRegionByCode($manager, self::STATE_CA_ON)
        );

        $manager->flush();
    }
}
