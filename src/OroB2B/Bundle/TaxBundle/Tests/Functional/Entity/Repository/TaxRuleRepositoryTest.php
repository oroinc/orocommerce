<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes as TaxFixture;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;

/**
 * @dbIsolation
 */
class ProductTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules']);
    }

    public function testFindByCountryAndProductTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_3);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByCountryAndProductTaxCode(
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getProductTaxCode()->getCode()
        );

        $this->assertEquals($taxRule->getId(), reset($result)->getId());
    }

    public function testFindByCountryAndRegionAndProductTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_1);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByCountryAndRegionAndProductTaxCode(
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getProductTaxCode()->getCode(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

        $this->assertEquals($taxRule->getId(), reset($result)->getId());
    }

    public function testFindByZipCodeAndProductTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_4);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByZipCodeAndProductTaxCode(
            LoadTaxJurisdictions::ZIP_CODE,
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getProductTaxCode()->getCode(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

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
