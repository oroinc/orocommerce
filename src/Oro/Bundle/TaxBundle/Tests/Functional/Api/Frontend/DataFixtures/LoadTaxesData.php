<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUser::class,
            LoadCountriesAndRegions::class,
            '@OroOrderBundle/Tests/Functional/Api/Frontend/DataFixtures/orders.yml'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTaxRule(
            $manager,
            $this->createTax($manager, 0.105),
            $this->createCustomerTaxCode($manager),
            $this->createProductTaxCode($manager),
            $this->createTaxJurisdiction($manager)
        );
        $manager->flush();

        $this->container->get('oro_tax.tax_codes.cache')->deleteAll();
        $this->container->get('oro_tax.taxation_provider.cache')->deleteAll();
    }

    private function createTaxRule(
        ObjectManager $manager,
        Tax $tax,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        TaxJurisdiction $taxJurisdiction
    ): void {
        $taxRule = new TaxRule();
        $taxRule
            ->setTax($tax)
            ->setCustomerTaxCode($customerTaxCode)
            ->setProductTaxCode($productTaxCode)
            ->setTaxJurisdiction($taxJurisdiction);

        $manager->persist($taxRule);
    }

    private function createTax(ObjectManager $manager, float $rate): Tax
    {
        $tax = new Tax();
        $tax
            ->setCode('TAX1')
            ->setRate($rate);

        $manager->persist($tax);

        return $tax;
    }

    private function createCustomerTaxCode(ObjectManager $manager): CustomerTaxCode
    {
        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode
            ->setCode('TAX1')
            ->setOwner($this->getUser())
            ->setOrganization($this->getUser()->getOrganization());

        /** @var Customer $customer */
        $customer = $this->getReference('customer');
        $customer->setTaxCode($customerTaxCode);

        $manager->persist($customerTaxCode);

        return $customerTaxCode;
    }

    private function createProductTaxCode(ObjectManager $manager): ProductTaxCode
    {
        $productTaxCode = new ProductTaxCode();
        $productTaxCode
            ->setCode('TAX1')
            ->setOrganization($this->getUser()->getOrganization());

        /** @var Product $product */
        $product = $this->getReference('product1');
        $product->setTaxCode($productTaxCode);

        $manager->persist($productTaxCode);

        return $productTaxCode;
    }

    private function createTaxJurisdiction(ObjectManager $manager): TaxJurisdiction
    {
        $taxJurisdiction = new TaxJurisdiction();
        $taxJurisdiction
            ->setCode('TAX1')
            ->setCountry($this->getReference('country_usa'));

        $manager->persist($taxJurisdiction);

        return $taxJurisdiction;
    }

    private function getUser(): User
    {
        return $this->getReference('user');
    }
}
