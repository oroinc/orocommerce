<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Matcher\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;

class LoadTaxRules extends AbstractFixture implements DependentFixtureInterface
{
    public const REFERENCE_PREFIX = 'tax_rule_matcher';

    public const RULE_US_NY_RANGE = 'RULE_US_NY_RANGE';
    public const RULE_US_NY_SINGLE = 'RULE_US_NY_SINGLE';
    public const RULE_US_LA_RANGE = 'RULE_US_LA_RANGE';
    public const RULE_CA_ON_WITHOUT_ZIP = 'RULE_CA_ON_WITHOUT_ZIP';
    public const RULE_US_ONLY = 'RULE_US_ONLY';

    private const DATA = [
        self::RULE_US_ONLY => [
            'taxJurisdiction' => LoadTaxJurisdictions::JURISDICTION_US_ONLY
        ],
        self::RULE_US_NY_RANGE => [
            'taxJurisdiction' => LoadTaxJurisdictions::JURISDICTION_US_NY_RANGE
        ],
        self::RULE_US_NY_SINGLE => [
            'taxJurisdiction' => LoadTaxJurisdictions::JURISDICTION_US_NY_SINGLE
        ],
        self::RULE_US_LA_RANGE => [
            'taxJurisdiction' => LoadTaxJurisdictions::JURISDICTION_US_LA_RANGE
        ],
        self::RULE_CA_ON_WITHOUT_ZIP => [
            'taxJurisdiction' => LoadTaxJurisdictions::JURISDICTION_CA_ON_WITHOUT_ZIP
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
            LoadTaxJurisdictions::class,
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
