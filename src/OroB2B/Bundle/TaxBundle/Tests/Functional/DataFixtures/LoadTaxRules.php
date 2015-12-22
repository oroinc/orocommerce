<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class LoadTaxRules extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_RULE_NAME = 'TAX_RULE_1';
    const DESCRIPTION = 'Tax rule description 1';

    const REFERENCE_PREFIX = 'tax_rule';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes',
            'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes',
            'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes',
            'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);
        $taxJurisdiction = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxJurisdictions::TAX_1
        );

        $this->createTaxRule($manager, $accountTaxCode, $productTaxCode, $tax, $taxJurisdiction, self::DESCRIPTION);

        $manager->flush();
    }

    /**
     * @param ObjectManager   $manager
     * @param AccountTaxCode  $accountTaxCode
     * @param ProductTaxCode  $productTaxCode
     * @param Tax             $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string          $description
     * @return TaxRule
     */
    protected function createTaxRule(
        ObjectManager $manager,
        AccountTaxCode $accountTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description
    ) {
        $taxRule = new TaxRule();
        $taxRule
            ->setAccountTaxCode($accountTaxCode)
            ->setProductTaxCode($productTaxCode)
            ->setTax($tax)
            ->setTaxJurisdiction($taxJurisdiction)
            ->setDescription($description);

        $manager->persist($taxRule);
        $this->addReference(self::REFERENCE_PREFIX . '.' . self::TAX_RULE_NAME, $taxRule);

        return $taxRule;
    }
}
