<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTaxesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadCountriesAndRegions::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->createTaxRule(
            $manager,
            $this->createTax($manager, 0.105),
            $this->createCustomerTaxCode($manager),
            $this->createProductTaxCode($manager),
            $this->createTaxJurisdiction($manager)
        );
        $manager->flush();

        $this->container->get('oro_tax.tax_codes.cache')->clear();
        $this->container->get('oro_tax.taxation_provider.cache')->clear();
    }

    private function createTaxRule(
        ObjectManager $manager,
        Tax $tax,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        TaxJurisdiction $taxJurisdiction
    ): void {
        $taxRule = new TaxRule();
        $taxRule->setCustomerTaxCode($customerTaxCode);
        $taxRule->setProductTaxCode($productTaxCode);
        $taxRule->setTaxJurisdiction($taxJurisdiction);
        $taxRule->setTax($tax);
        $taxRule->setOrganization($productTaxCode->getOrganization());
        $manager->persist($taxRule);
    }

    private function createTax(ObjectManager $manager, float $rate): Tax
    {
        $tax = new Tax();
        $tax->setCode('TAX1');
        $tax->setRate($rate);
        $manager->persist($tax);

        return $tax;
    }

    private function createCustomerTaxCode(ObjectManager $manager): CustomerTaxCode
    {
        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode('TAX1');
        $customerTaxCode->setOwner($this->getUser());
        $customerTaxCode->setOrganization($this->getUser()->getOrganization());
        $manager->persist($customerTaxCode);
        $this->getReference('customer')->setTaxCode($customerTaxCode);

        return $customerTaxCode;
    }

    private function createProductTaxCode(ObjectManager $manager): ProductTaxCode
    {
        $productTaxCode = new ProductTaxCode();
        $productTaxCode->setCode('TAX1');
        $productTaxCode->setOrganization($this->getUser()->getOrganization());
        $manager->persist($productTaxCode);
        $this->getReference('product1')->setTaxCode($productTaxCode);

        return $productTaxCode;
    }

    private function createTaxJurisdiction(ObjectManager $manager): TaxJurisdiction
    {
        $taxJurisdiction = new TaxJurisdiction();
        $taxJurisdiction->setCode('TAX1');
        $taxJurisdiction->setCountry($this->getReference('country_usa'));
        $manager->persist($taxJurisdiction);

        return $taxJurisdiction;
    }

    private function getUser(): User
    {
        return $this->getReference('user');
    }
}
