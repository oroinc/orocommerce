<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads demo data for taxes.
 */
class LoadTaxTableRatesDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
            LoadCustomerDemoData::class,
            LoadCustomerGroupDemoData::class,
            LoadOrganizationAndBusinessUnitData::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $data = require $locator->locate('@OroTaxBundle/Migrations/Data/Demo/ORM/data/tax_table_rates.php');

        $this->loadCustomerTaxCodes($manager, $data['customer_tax_codes']);
        $this->loadProductTaxCodes($manager, $data['product_tax_codes']);
        $this->loadTaxes($manager, $data['taxes']);
        $this->loadTaxJurisdictions($manager, $data['tax_jurisdictions']);
        $this->loadTaxRules($manager, $data['tax_rules']);

        $manager->flush();
    }

    private function loadCustomerTaxCodes(ObjectManager $manager, array $customerTaxCodes): void
    {
        $owner = $this->getAdminUser($manager);
        foreach ($customerTaxCodes as $code => $data) {
            $taxCode = new CustomerTaxCode();
            $taxCode->setCode($code);
            $taxCode->setDescription($data['description']);
            $taxCode->setOwner($owner);
            $taxCode->setOrganization($owner->getOrganization());
            $manager->persist($taxCode);
            $this->addReference($code, $taxCode);

            if (isset($data['customers'])) {
                foreach ($data['customers'] as $customerName) {
                    $customer = $manager->getRepository(Customer::class)->findOneByName($customerName);
                    if (null !== $customer) {
                        $customer->setTaxCode($taxCode);
                    }
                }
            }
            if (isset($data['customer_groups'])) {
                foreach ($data['customer_groups'] as $groupName) {
                    $group = $manager->getRepository(CustomerGroup::class)->findOneByName($groupName);
                    if (null !== $group) {
                        $group->setTaxCode($taxCode);
                    }
                }
            }
        }
    }

    private function loadProductTaxCodes(ObjectManager $manager, array $productTaxCodes): void
    {
        $owner = $this->getAdminUser($manager);
        foreach ($productTaxCodes as $code => $data) {
            $taxCode = new ProductTaxCode();
            $taxCode->setCode($code);
            $taxCode->setDescription($data['description']);
            $taxCode->setOrganization($owner->getOrganization());
            $manager->persist($taxCode);
            $this->addReference($code, $taxCode);
            foreach ($data['products'] as $sku) {
                $product = $manager->getRepository(Product::class)->findOneBySku($sku);
                if ($product) {
                    $product->setTaxCode($taxCode);
                }
            }
        }
    }

    private function loadTaxes(ObjectManager $manager, array $taxes): void
    {
        foreach ($taxes as $code => $data) {
            $tax = new Tax();
            $tax->setCode($code);
            $tax->setRate($data['rate']);
            $tax->setDescription($data['description']);
            $manager->persist($tax);
            $this->addReference($code, $tax);
        }
    }

    private function loadTaxJurisdictions(ObjectManager $manager, array $taxJurisdictions): void
    {
        foreach ($taxJurisdictions as $code => $data) {
            $country = $this->getCountryByIso2Code($manager, $data['country']);

            $jurisdiction = new TaxJurisdiction();
            $jurisdiction->setCode($code);
            $jurisdiction->setDescription($data['description']);
            $jurisdiction->setCountry($country);
            $jurisdiction->setRegion($this->getRegionByCountryAndCode($manager, $country, $data['state']));
            foreach ($data['zip_codes'] as $zipCodeData) {
                $zipCode = new ZipCode();
                if (\is_array($zipCodeData)) {
                    $zipCode->setZipRangeStart($zipCodeData['start']);
                    $zipCode->setZipRangeEnd($zipCodeData['end']);
                } else {
                    $zipCode->setZipCode($zipCodeData);
                }
                $jurisdiction->addZipCode($zipCode);
            }
            $manager->persist($jurisdiction);
            $this->addReference($code, $jurisdiction);
        }
    }

    private function loadTaxRules(ObjectManager $manager, array $taxRules): void
    {
        foreach ($taxRules as $rule) {
            /** @var CustomerTaxCode $customerTaxCode */
            $customerTaxCode = $this->getReference($rule['customer_tax_code']);
            /** @var ProductTaxCode $productTaxCode */
            $productTaxCode = $this->getReference($rule['product_tax_code']);
            /** @var TaxJurisdiction $taxJurisdiction */
            $taxJurisdiction = $this->getReference($rule['tax_jurisdiction']);
            /** @var Tax $tax */
            $tax = $this->getReference($rule['tax']);

            $taxRule = new TaxRule();
            $taxRule->setDescription($rule['description'] ?? '');
            $taxRule->setCustomerTaxCode($customerTaxCode);
            $taxRule->setProductTaxCode($productTaxCode);
            $taxRule->setTaxJurisdiction($taxJurisdiction);
            $taxRule->setTax($tax);
            $taxRule->setOrganization($productTaxCode->getOrganization());
            $manager->persist($taxRule);
        }
    }

    private function getCountryByIso2Code(ObjectManager $manager, string $iso2Code): Country
    {
        $country = $manager->getRepository(Country::class)->findOneBy(['iso2Code' => $iso2Code]);
        if (null === $country) {
            throw new \RuntimeException(sprintf('%s country should exist.', $iso2Code));
        }

        return $country;
    }

    private function getRegionByCountryAndCode(ObjectManager $manager, Country $country, string $code): Region
    {
        $region = $manager->getRepository(Region::class)->findOneBy(['country' => $country, 'code' => $code]);
        if (null === $region) {
            throw new \RuntimeException(sprintf(
                '%s region for %s country should exist.',
                $code,
                $country->getIso2Code()
            ));
        }

        return $region;
    }

    private function getAdminUser(ObjectManager $manager): User
    {
        $repository = $manager->getRepository(Role::class);
        $role = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
        if (null === $role) {
            throw new \RuntimeException(sprintf('%s role should exist.', User::ROLE_ADMINISTRATOR));
        }
        $user = $repository->getFirstMatchedUser($role);
        if (null === $user) {
            throw new \RuntimeException('An administrator user should exist.');
        }

        return $user;
    }
}
