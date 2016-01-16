<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Matcher;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxJurisdictions;
use OroB2B\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxRules;

/**
 * @dbIsolation
 */
class ZipCodeMatcherTest extends WebTestCase
{
    const ZIP_US_NY_RANGE_INSIDE = '00200';
    const ZIP_UNUSED_CODE = '00001';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxRules'
            ]
        );
    }

    /**
     * @dataProvider matchProvider
     *
     * @param array $expectedRuleReferences
     * @param string $postalCode
     * @param string $country
     * @param string $region
     * @param string $regionText
     */
    public function testMatch($expectedRuleReferences, $postalCode, $country = null, $region = null, $regionText = null)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $address = (new Address())
            ->setPostalCode($postalCode)
            ->setCountry(LoadTaxJurisdictions::getCountryByCode($em, $country));

        if ($region) {
            $address->setRegion(LoadTaxJurisdictions::getRegionByCode($em, $region));
        } elseif ($regionText) {
            $address->setRegionText($regionText);
        }

        $zipCodeMatcher = $this->getContainer()->get('orob2b_tax.matcher.zip_code_matcher');
        $rules = $zipCodeMatcher->match($address);

        $expectedRules = [];
        foreach ($expectedRuleReferences as $reference) {
            $expectedRules[] = $this->getTaxRuleByReference($reference);
        }

        $this->assertEquals($this->valueToArray($expectedRules), $this->valueToArray($rules));
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        return [
            'Match by country, region and range' => [
                [LoadTaxRules::RULE_US_NY_RANGE, LoadTaxRules::RULE_US_ONLY],
                self::ZIP_US_NY_RANGE_INSIDE,
                LoadTaxJurisdictions::COUNTRY_US,
                LoadTaxJurisdictions::STATE_US_NY
            ],
            'Match by country, region and single zip' => [
                [LoadTaxRules::RULE_US_NY_SINGLE, LoadTaxRules::RULE_US_ONLY],
                LoadTaxJurisdictions::ZIP_US_NY_SINGLE,
                LoadTaxJurisdictions::COUNTRY_US,
                LoadTaxJurisdictions::STATE_US_NY
            ],
            'Match by country and region only' => [
                [LoadTaxRules::RULE_CA_ON_WITHOUT_ZIP],
                self::ZIP_UNUSED_CODE,
                LoadTaxJurisdictions::COUNTRY_CA,
                LoadTaxJurisdictions::STATE_CA_ON
            ],
            'Match by country' => [
                [LoadTaxRules::RULE_US_ONLY],
                self::ZIP_UNUSED_CODE,
                LoadTaxJurisdictions::COUNTRY_US,
                null,
                LoadTaxJurisdictions::STATE_TEXT_SOME
            ],
        ];
    }

    /**
     * @param string $reference
     * @return TaxRule
     */
    protected function getTaxRuleByReference($reference)
    {
        return $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . $reference);
    }
}
