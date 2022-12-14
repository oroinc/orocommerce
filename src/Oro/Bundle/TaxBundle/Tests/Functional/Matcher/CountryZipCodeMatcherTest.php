<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxJurisdictions;
use Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CountryZipCodeMatcherTest extends WebTestCase
{
    private const ZIP_US_NY_RANGE_INSIDE = '00200';
    private const ZIP_UNUSED_CODE = '00001';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadTaxRules::class]);
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatch(
        array $expectedRuleReferences,
        ?string $postalCode,
        ?string $country,
        ?string $region,
        ?string $regionText = null
    ) {
        $address = $this->createAddress($postalCode, $country, $region, $regionText);

        /** @var TaxCodeInterface $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        /** @var TaxCodeInterface $customerTaxCode */
        $customerTaxCode = $this
            ->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX . '.' . LoadCustomerTaxCodes::TAX_1);

        $zipCodeMatcher = $this->getContainer()->get('oro_tax.matcher.country_and_zip_code_matcher');
        /** @var TaxRule[] $rules */
        $rules = $zipCodeMatcher->match(
            $address,
            TaxCodes::create([
                TaxCode::create($productTaxCode->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                TaxCode::create($customerTaxCode->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
            ])
        );

        $actualRules = [];
        foreach ($rules as $rule) {
            $actualRules[$rule->getId()] = $rule;
        }

        $this->assertCount(count($expectedRuleReferences), $actualRules);
        foreach ($expectedRuleReferences as $reference) {
            /** @var TaxRule $expectedRule */
            $expectedRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . $reference);
            $this->assertArrayHasKey(
                $expectedRule->getId(),
                $actualRules,
                sprintf('Can\'t find expected reference with id "%s" in actual rules', $expectedRule->getId())
            );
            $this->assertEquals($expectedRule, $actualRules[$expectedRule->getId()]);
        }
    }

    public function matchProvider(): array
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
            'Match by country, zip unused' => [
                [LoadTaxRules::RULE_US_ONLY],
                self::ZIP_UNUSED_CODE,
                LoadTaxJurisdictions::COUNTRY_US,
                LoadTaxJurisdictions::STATE_US_NY
            ],
            'Match by country only' => [
                [LoadTaxRules::RULE_US_ONLY],
                null,
                LoadTaxJurisdictions::COUNTRY_US,
                null
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

    private function createAddress(
        ?string $postalCode,
        ?string $country,
        ?string $region,
        ?string $regionText
    ): Address {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $address = new Address();
        $address->setPostalCode($postalCode);
        $address->setCountry($em->getReference(Country::class, $country));
        if ($region) {
            $address->setRegion($em->getReference(Region::class, $region));
        } elseif ($regionText) {
            $address->setRegionText($regionText);
        }

        return $address;
    }
}
