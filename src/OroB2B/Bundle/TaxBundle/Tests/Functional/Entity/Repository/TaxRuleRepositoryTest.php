<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;

/**
 * @dbIsolation
 */
class TaxRuleRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules']);
    }

    public function testFindByCountryAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_1);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByCountryAndTaxCode(
            TaxCodes::create(
                [
                    TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create($taxRule->getAccountTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
                ]
            ),
            $taxRule->getTaxJurisdiction()->getCountry()
        );

        $this->assertContainsId($taxRule, $result);
    }

    public function testFindByCountryAndRegionAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_2);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByRegionAndTaxCode(
            TaxCodes::create(
                [
                    TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create($taxRule->getAccountTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
                ]
            ),
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

        $this->assertContainsId($taxRule, $result);
    }

    public function testFindByZipCodeAndProductTaxCodeAndAccountTaxCode()
    {
        /** @var TaxRule $taxRule */
        $taxRule = $this->getReference(LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_4);

        /** @var TaxRule[] $result */
        $result = $this->getRepository()->findByZipCodeAndTaxCode(
            TaxCodes::create(
                [
                    TaxCode::create($taxRule->getProductTaxCode()->getCode(), TaxCodeInterface::TYPE_PRODUCT),
                    TaxCode::create($taxRule->getAccountTaxCode()->getCode(), TaxCodeInterface::TYPE_ACCOUNT),
                ]
            ),
            LoadTaxJurisdictions::ZIP_CODE,
            $taxRule->getTaxJurisdiction()->getCountry(),
            $taxRule->getTaxJurisdiction()->getRegion()
        );

        $this->assertContainsId($taxRule, $result);
    }

    /**
     * @param TaxRule $expectedTaxRule
     * @param array $result
     */
    protected function assertContainsId(TaxRule $expectedTaxRule, array $result)
    {
        $ids = array_map(
            function (TaxRule $taxRule) {
                return $taxRule->getId();
            },
            $result
        );

        $this->assertTrue(in_array($expectedTaxRule->getId(), $ids, true));
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
