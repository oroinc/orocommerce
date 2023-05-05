<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

class LoadTaxRules extends AbstractFixture implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'tax_rule';

    public const TAX_RULE_1 = 'TAX_RULE_1';
    public const TAX_RULE_2 = 'TAX_RULE_2';
    public const TAX_RULE_3 = 'TAX_RULE_3';
    public const TAX_RULE_4 = 'TAX_RULE_4';

    private const DATA = [
        self::TAX_RULE_1 => [
            'taxJurisdiction' => LoadTaxes::TAX_1
        ],
        self::TAX_RULE_2 => [
            'taxJurisdiction' => LoadTaxes::TAX_2
        ],
        self::TAX_RULE_3 => [
            'taxJurisdiction' => LoadTaxes::TAX_3
        ],
        self::TAX_RULE_4 => [
            'taxJurisdiction' => LoadTaxes::TAX_4
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadCustomerTaxCodes::class,
            LoadProductTaxCodes::class,
            LoadTaxes::class,
            LoadTaxJurisdictions::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var CustomerTaxCode $customerTaxCode */
        $customerTaxCode = $this->getReference(
            LoadCustomerTaxCodes::REFERENCE_PREFIX . '.' . LoadCustomerTaxCodes::TAX_1
        );
        /** @var ProductTaxCode $productTaxCode */
        $productTaxCode = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1
        );
        /** @var Tax $tax */
        $tax = $this->getReference(
            LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1
        );
        foreach (self::DATA as $code => $item) {
            /** @var TaxJurisdiction $taxJurisdiction */
            $taxJurisdiction = $this->getReference(
                LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . $item['taxJurisdiction']
            );

            $taxRule = new TaxRule();
            $taxRule->setDescription('Tax rule description 1');
            $taxRule->setCustomerTaxCode($customerTaxCode);
            $taxRule->setProductTaxCode($productTaxCode);
            $taxRule->setTaxJurisdiction($taxJurisdiction);
            $taxRule->setTax($tax);
            $taxRule->setOrganization($productTaxCode->getOrganization());
            $manager->persist($taxRule);
            $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $taxRule);
        }
        $manager->flush();
    }
}
