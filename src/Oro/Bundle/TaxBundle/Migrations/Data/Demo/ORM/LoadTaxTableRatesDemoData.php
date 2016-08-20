<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Migrations\TaxEntitiesFactory;

class LoadTaxTableRatesDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var TaxEntitiesFactory
     */
    private $entitiesFactory;

    public function __construct()
    {
        $this->entitiesFactory = new TaxEntitiesFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = require __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'tax_table_rates.php';

        $this->loadAccountTaxCodes($manager, $data['account_tax_codes']);
        $this->loadProductTaxCodes($manager, $data['product_tax_codes']);
        $this->loadTaxes($manager, $data['taxes']);
        $this->loadTaxJurisdictions($manager, $data['tax_jurisdictions']);
        $this->loadTaxRules($manager, $data['tax_rules']);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $accountTaxCodes
     *
     * @return $this
     */
    private function loadAccountTaxCodes(ObjectManager $manager, $accountTaxCodes)
    {
        foreach ($accountTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createAccountTaxCode($code, $data['description'], $manager, $this);
            if (isset($data['accounts'])) {
                foreach ($data['accounts'] as $accountName) {
                    $account = $manager->getRepository('OroAccountBundle:Account')->findOneByName($accountName);
                    if (null !== $account) {
                        $taxCode->addAccount($account);
                    }
                }
            }
            if (isset($data['account_groups'])) {
                foreach ($data['account_groups'] as $groupName) {
                    $group = $manager->getRepository('OroAccountBundle:AccountGroup')->findOneByName($groupName);
                    if (null !== $group) {
                        $taxCode->addAccountGroup($group);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $productTaxCodes
     *
     * @return $this
     */
    private function loadProductTaxCodes(ObjectManager $manager, $productTaxCodes)
    {
        foreach ($productTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createProductTaxCode($code, $data['description'], $manager, $this);
            foreach ($data['products'] as $sku) {
                $product = $manager->getRepository('OroProductBundle:Product')->findOneBySku($sku);
                if ($product) {
                    $taxCode->addProduct($product);
                }
            }
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxes
     *
     * @return $this
     */
    private function loadTaxes(ObjectManager $manager, $taxes)
    {
        foreach ($taxes as $code => $data) {
            $this->entitiesFactory->createTax($code, $data['rate'], $data['description'], $manager, $this);
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxJurisdictions
     *
     * @return $this
     */
    private function loadTaxJurisdictions(ObjectManager $manager, $taxJurisdictions)
    {
        foreach ($taxJurisdictions as $code => $data) {
            $country = $this->getCountryByIso2Code($manager, $data['country']);
            $region = $this->getRegionByCountryAndCode($manager, $country, $data['state']);

            $this->entitiesFactory->createTaxJurisdiction(
                $code,
                $data['description'],
                $country,
                $region,
                $data['zip_codes'],
                $manager,
                $this
            );
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxRules
     *
     * @return $this
     */
    private function loadTaxRules(ObjectManager $manager, $taxRules)
    {
        foreach ($taxRules as $rule) {
            /** @var \Oro\Bundle\TaxBundle\Entity\AccountTaxCode $accountTaxCode */
            $accountTaxCode = $this->getReference($rule['account_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\ProductTaxCode $productTaxCode */
            $productTaxCode = $this->getReference($rule['product_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\TaxJurisdiction $taxJurisdiction */
            $taxJurisdiction = $this->getReference($rule['tax_jurisdiction']);

            /** @var \Oro\Bundle\TaxBundle\Entity\Tax $tax */
            $tax = $this->getReference($rule['tax']);

            $this->entitiesFactory->createTaxRule(
                $accountTaxCode,
                $productTaxCode,
                $taxJurisdiction,
                $tax,
                isset($rule['description']) ? $rule['description'] : '',
                $manager
            );
        }

        return $this;
    }

    //region Helper methods for the methods that the corresponding repositories do not have
    /**
     * @param ObjectManager $manager
     * @param string $iso2Code
     *
     * @return Country|null
     */
    private function getCountryByIso2Code(ObjectManager $manager, $iso2Code)
    {
        return $manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => $iso2Code]);
    }

    /**
     * @param ObjectManager $manager
     * @param Country $country
     * @param string $code
     *
     * @return Region|null
     */
    private function getRegionByCountryAndCode(ObjectManager $manager, Country $country, $code)
    {
        return $manager->getRepository('OroAddressBundle:Region')->findOneBy(['country' => $country, 'code' => $code]);
    }
    //endregion
}
