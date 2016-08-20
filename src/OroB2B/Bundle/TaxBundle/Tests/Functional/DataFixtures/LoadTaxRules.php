<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

class LoadTaxRules extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_RULE_1 = 'TAX_RULE_1';
    const TAX_RULE_2 = 'TAX_RULE_2';
    const TAX_RULE_3 = 'TAX_RULE_3';
    const TAX_RULE_4 = 'TAX_RULE_4';

    const DESCRIPTION = 'Tax rule description 1';

    const REFERENCE_PREFIX = 'tax_rule';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes',
            'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountTaxCode $accountTaxCode */
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        /** @var productTaxCode $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        /** @var Tax $tax */
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);

        /** @var TaxJurisdiction $taxJurisdiction */
        $taxJurisdiction = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1
        );

        /** @var TaxJurisdiction $taxJurisdiction2 */
        $taxJurisdiction2 = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_2
        );

        /** @var TaxJurisdiction $taxJurisdiction3 */
        $taxJurisdiction3 = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_3
        );

        /** @var TaxJurisdiction $taxJurisdiction4 */
        $taxJurisdiction4 = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_4
        );

        $this->createTaxRule(
            $manager,
            $accountTaxCode,
            $productTaxCode,
            $tax,
            $taxJurisdiction,
            self::DESCRIPTION,
            self::TAX_RULE_1
        );

        $this->createTaxRule(
            $manager,
            $accountTaxCode,
            $productTaxCode,
            $tax,
            $taxJurisdiction2,
            self::DESCRIPTION,
            self::TAX_RULE_2
        );

        $this->createTaxRule(
            $manager,
            $accountTaxCode,
            $productTaxCode,
            $tax,
            $taxJurisdiction3,
            self::DESCRIPTION,
            self::TAX_RULE_3
        );

        $this->createTaxRule(
            $manager,
            $accountTaxCode,
            $productTaxCode,
            $tax,
            $taxJurisdiction4,
            self::DESCRIPTION,
            self::TAX_RULE_4
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountTaxCode $accountTaxCode
     * @param ProductTaxCode $productTaxCode
     * @param Tax $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string $description
     * @param string $reference
     * @return TaxRule
     */
    protected function createTaxRule(
        ObjectManager $manager,
        AccountTaxCode $accountTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description,
        $reference
    ) {
        $taxRule = new TaxRule();
        $taxRule
            ->setAccountTaxCode($accountTaxCode)
            ->setProductTaxCode($productTaxCode)
            ->setTax($tax)
            ->setTaxJurisdiction($taxJurisdiction)
            ->setDescription($description);

        $manager->persist($taxRule);
        $this->addReference(static::REFERENCE_PREFIX . '.' . $reference, $taxRule);

        return $taxRule;
    }
}
