<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxJurisdictions;
use Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxRules;

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
                'Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxRules',
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
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $address = (new Address())
            ->setPostalCode($postalCode)
            ->setCountry(LoadTaxJurisdictions::getCountryByCode($em, $country));

        if ($region) {
            $address->setRegion(LoadTaxJurisdictions::getRegionByCode($em, $region));
        } elseif ($regionText) {
            $address->setRegionText($regionText);
        }

        /** @var TaxCodeInterface $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        /** @var TaxCodeInterface $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $zipCodeMatcher = $this->getContainer()->get('orob2b_tax.matcher.zip_code_matcher');
        /** @var TaxRule[] $rules */
        $rules = $zipCodeMatcher->match(
            $address,
            TaxCodes::create(
                [
                    TaxCode::create($productTaxCode->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create($accountTaxCode->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
                ]
            )
        );

        $actualRules = [];
        foreach ($rules as $rule) {
            $actualRules[$rule->getId()] = $rule;
        }

        foreach ($expectedRuleReferences as $reference) {
            $expectedRule = $this->getTaxRuleByReference($reference);
            $this->assertTrue(
                array_key_exists($expectedRule->getId(), $actualRules),
                sprintf('Can\'t find expected reference with id "%s" in actual rules', $expectedRule->getId())
            );
            $this->assertEquals($expectedRule, $actualRules[$expectedRule->getId()]);
        }
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
                LoadTaxJurisdictions::STATE_US_NY,
            ],
            'Match by country, region and single zip' => [
                [LoadTaxRules::RULE_US_NY_SINGLE, LoadTaxRules::RULE_US_ONLY],
                LoadTaxJurisdictions::ZIP_US_NY_SINGLE,
                LoadTaxJurisdictions::COUNTRY_US,
                LoadTaxJurisdictions::STATE_US_NY,
            ],
            'Match by country and region only' => [
                [LoadTaxRules::RULE_CA_ON_WITHOUT_ZIP],
                self::ZIP_UNUSED_CODE,
                LoadTaxJurisdictions::COUNTRY_CA,
                LoadTaxJurisdictions::STATE_CA_ON,
            ],
            'Match by country' => [
                [LoadTaxRules::RULE_US_ONLY],
                self::ZIP_UNUSED_CODE,
                LoadTaxJurisdictions::COUNTRY_US,
                null,
                LoadTaxJurisdictions::STATE_TEXT_SOME,
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
