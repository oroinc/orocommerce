<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules as BaseLoadTaxRules;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

class LoadTaxRules extends BaseLoadTaxRules
{
    const REFERENCE_PREFIX = 'tax_rule_matcher';

    const RULE_US_NY_RANGE = 'RULE_US_NY_RANGE';
    const RULE_US_NY_SINGLE = 'RULE_US_NY_SINGLE';
    const RULE_US_LA_RANGE = 'RULE_US_LA_RANGE';
    const RULE_CA_ON_WITHOUT_ZIP = 'RULE_CA_ON_WITHOUT_ZIP';
    const RULE_US_ONLY = 'RULE_US_ONLY';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes',
            'Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures\LoadTaxJurisdictions',
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {

        $this->createTaxRuleWithJurisdiction(
            $manager,
            $this->getTaxJurisdictionByReference(LoadTaxJurisdictions::JURISDICTION_US_ONLY),
            self::RULE_US_ONLY
        );

        $this->createTaxRuleWithJurisdiction(
            $manager,
            $this->getTaxJurisdictionByReference(LoadTaxJurisdictions::JURISDICTION_US_NY_RANGE),
            self::RULE_US_NY_RANGE
        );

        $this->createTaxRuleWithJurisdiction(
            $manager,
            $this->getTaxJurisdictionByReference(LoadTaxJurisdictions::JURISDICTION_US_NY_SINGLE),
            self::RULE_US_NY_SINGLE
        );

        $this->createTaxRuleWithJurisdiction(
            $manager,
            $this->getTaxJurisdictionByReference(LoadTaxJurisdictions::JURISDICTION_US_LA_RANGE),
            self::RULE_US_LA_RANGE
        );

        $this->createTaxRuleWithJurisdiction(
            $manager,
            $this->getTaxJurisdictionByReference(LoadTaxJurisdictions::JURISDICTION_CA_ON_WITHOUT_ZIP),
            self::RULE_CA_ON_WITHOUT_ZIP
        );

        $manager->flush();
    }

    /**
     * @param $code
     * @return TaxJurisdiction
     */
    protected function getTaxJurisdictionByReference($code)
    {
        return $this->getReference(LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . $code);
    }

    /**
     * @param EntityManager $manager
     * @param TaxJurisdiction $taxJurisdiction
     * @param string $reference
     * @return TaxRule
     */
    protected function createTaxRuleWithJurisdiction(
        EntityManager $manager,
        TaxJurisdiction $taxJurisdiction,
        $reference
    ) {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        /** @var ProductTaxCode $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        /** @var Tax $tax */
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);

        return $this->createTaxRule(
            $manager,
            $accountTaxCode,
            $productTaxCode,
            $tax,
            $taxJurisdiction,
            self::DESCRIPTION,
            $reference
        );
    }
}
