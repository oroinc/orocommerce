<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;

/**
 * @dbIsolation
 */
class TaxRuleRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules']);
    }

    public function testFindByCountryAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_3);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByCountryAndProductTaxCodeAndAccountTaxCode(
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getProductTaxCode()->getCode(),
            $taxRule->getAccountTaxCode()->getCode()
        );

        $this->assertEquals($taxRule->getId(), reset($result)->getId());
    }

    public function testFindByCountryAndRegionAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_1);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByCountryAndRegionAndProductTaxCodeAndAccountTaxCode(
            $taxRule->getProductTaxCode()->getCode(),
            $taxRule->getAccountTaxCode()->getCode(),
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

        $this->assertEquals($taxRule->getId(), reset($result)->getId());
    }

    public function testFindByZipCodeAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_4);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByZipCodeAndProductTaxCodeAndAccountTaxCode(
            $taxRule->getProductTaxCode()->getCode(),
            $taxRule->getAccountTaxCode()->getCode(),
            LoadTaxJurisdictions::ZIP_CODE,
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

        $this->assertCount(1, $result);
        $this->assertEquals($taxRule->getId(), reset($result)->getId());
    }

    /**
     * @return TaxRuleRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_tax.entity.tax_rule.class')
        );
    }
}
