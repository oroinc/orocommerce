<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Migrations\TaxEntitiesFactory;

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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */

        $data = require 'data/tax_table_rates.php';

        $this->loadAccountTaxCodes($manager, $data['account_tax_codes']);
        $this->loadProductTaxCodes($manager, $data['product_tax_codes']);
        $this->loadTaxes($manager, $data['taxes']);
        $this->loadTaxJurisdictions($manager, $data['tax_jurisdictions']);
        $this->loadTaxRules($manager, $data['tax_rules']);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param array $accountTaxCodes
     *
     * @return $this
     */
    private function loadAccountTaxCodes(EntityManager $manager, $accountTaxCodes)
    {
        foreach ($accountTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createAccountTaxCode($code, $data['description'], $manager, $this);
            if (isset($data['accounts'])) {
                foreach ($data['accounts'] as $accountName) {
                    $account = $manager->getRepository('OroB2BAccountBundle:Account')->findOneByName($accountName);
                    if (!is_null($account)) {
                        $taxCode->addAccount($account);
                    }
                }
            }
            if (isset($data['account_groups'])) {
                foreach ($data['account_groups'] as $groupName) {
                    $group = $manager->getRepository('OroB2BAccountBundle:AccountGroup')->findOneByName($groupName);
                    if (!is_null($group)) {
                        $taxCode->addAccountGroup($group);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param EntityManager $manager
     * @param array $productTaxCodes
     *
     * @return $this
     */
    private function loadProductTaxCodes(EntityManager $manager, $productTaxCodes)
    {
        foreach ($productTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createProductTaxCode($code, $data['description'], $manager, $this);
            foreach ($data['products'] as $sku) {
                $product = $manager->getRepository('OroB2BProductBundle:Product')->findOneBySku($sku);
                $taxCode->addProduct($product);
            }
        }

        return $this;
    }

    /**
     * @param EntityManager $manager
     * @param array $taxes
     *
     * @return $this
     */
    private function loadTaxes(EntityManager $manager, $taxes)
    {
        foreach ($taxes as $code => $data) {
            $this->entitiesFactory->createTax($code, $data['rate'], $data['description'], $manager, $this);
        }

        return $this;
    }

    /**
     * @param EntityManager $manager
     * @param array $taxJurisdictions
     *
     * @return $this
     */
    private function loadTaxJurisdictions(EntityManager $manager, $taxJurisdictions)
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
     * @param EntityManager $manager
     * @param array $taxRules
     *
     * @return $this
     */
    private function loadTaxRules(EntityManager $manager, $taxRules)
    {
        foreach ($taxRules as $rule) {
            $this->entitiesFactory->createTaxRule(
                $this->getReference($rule['account_tax_code']),
                $this->getReference($rule['product_tax_code']),
                $this->getReference($rule['tax_jurisdiction']),
                $this->getReference($rule['tax']),
                isset($rule['description']) ? $rule['description'] : '',
                $manager
            );
        }

        return $this;
    }

    //region Helper methods for the methods that the corresponding repositories do not have
    /**
     * @param EntityManager $manager
     * @param string $iso2Code
     *
     * @return Country|null
     */
    private function getCountryByIso2Code(EntityManager $manager, $iso2Code)
    {
        return $manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => $iso2Code]);
    }

    /**
     * @param EntityManager $manager
     * @param Country $country
     * @param string $code
     *
     * @return Region|null
     */
    private function getRegionByCountryAndCode(EntityManager $manager, Country $country, $code)
    {
        return $manager->getRepository('OroAddressBundle:Region')->findOneBy(['country' => $country, 'code' => $code]);
    }
    //endregion
}
