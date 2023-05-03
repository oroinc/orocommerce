<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TaxRuleRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadTaxRules::class]);
    }

    private function getRepository(): TaxRuleRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(TaxRule::class);
    }

    private function getTaxRule(string $code): TaxRule
    {
        return $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . $code);
    }

    private function assertContainsId(TaxRule $needle, array $haystack): void
    {
        $ids = [];
        /** @var TaxRule $taxRule */
        foreach ($haystack as $taxRule) {
            $ids[] = $taxRule->getId();
        }

        $this->assertContains($needle->getId(), $ids);
    }

    public function testFindByCountryAndProductTaxCodeAndCustomerTaxCode()
    {
        $taxRule = $this->getTaxRule(LoadTaxRules::TAX_RULE_1);

        $result = $this->getRepository()->findByCountryAndTaxCode(
            TaxCodes::create([
                TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                TaxCode::create($taxRule->getCustomerTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
            ]),
            $taxRule->getTaxJurisdiction()->getCountry()
        );

        $this->assertContainsId($taxRule, $result);
    }

    public function testFindByCountryAndRegionAndProductTaxCodeAndCustomerTaxCode()
    {
        $taxRule = $this->getTaxRule(LoadTaxRules::TAX_RULE_2);

        $result = $this->getRepository()->findByRegionAndTaxCode(
            TaxCodes::create([
                TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                TaxCode::create($taxRule->getCustomerTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
            ]),
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion(),
            null
        );

        $this->assertContainsId($taxRule, $result);
    }

    public function testFindByZipCodeAndProductTaxCodeAndCustomerTaxCode()
    {
        $taxRule = $this->getTaxRule(LoadTaxRules::TAX_RULE_4);

        $result = $this->getRepository()->findByZipCodeAndTaxCode(
            TaxCodes::create([
                TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                TaxCode::create($taxRule->getCustomerTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
            ]),
            LoadTaxJurisdictions::ZIP_CODE,
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion(),
            null
        );

        $this->assertContainsId($taxRule, $result);
    }

    public function testFindByCountryAndZipCodeAndTaxCode()
    {
        $taxRule = $this->getTaxRule(LoadTaxRules::TAX_RULE_4);

        $result = $this->getRepository()->findByCountryAndZipCodeAndTaxCode(
            TaxCodes::create([
                TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                TaxCode::create($taxRule->getCustomerTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
            ]),
            LoadTaxJurisdictions::ZIP_CODE,
            $taxRule->getTaxJurisdiction()->getCountry()
        );

        $this->assertContainsId($taxRule, $result);
    }
}
