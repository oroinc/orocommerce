<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadProductVisibilityData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadGroups::class,
            LoadCustomers::class,
            LoadCategoryProductData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // set default fallback to categories
        $configVisibilities = $manager->getRepository(ProductVisibility::class)
            ->findBy(['visibility' => ProductVisibility::CONFIG]);
        foreach ($configVisibilities as $visibilityEntity) {
            $visibilityEntity->setVisibility(ProductVisibility::CATEGORY);
        }
        $manager->flush();

        // load visibilities
        foreach ($this->getProductVisibilities() as $productReference => $productVisibilityData) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibilities($manager, $product, $productVisibilityData);
        }
        $manager->flush();
    }

    protected function createProductVisibilities(ObjectManager $manager, Product $product, array $data): void
    {
        $productVisibility = new ProductVisibility();
        $productVisibility->setProduct($product)
            ->setVisibility($data['all']['visibility']);

        $scope = $this->getScopeForProductVisibilities();
        $productVisibility->setScope($scope);

        $manager->persist($productVisibility);

        $this->setReference($data['all']['reference'], $productVisibility);

        $this->createCustomerGroupVisibilities($manager, $product, $data['groups']);

        $this->createCustomerVisibilities($manager, $product, $data['customers']);
    }

    protected function createCustomerGroupVisibilities(
        ObjectManager $manager,
        Product $product,
        array $customerGroupsData
    ): void {
        foreach ($customerGroupsData as $groupReference => $customerGroupData) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($groupReference);

            $customerGroupProductVisibility = new CustomerGroupProductVisibility();
            $customerGroupProductVisibility->setProduct($product)
                ->setVisibility($customerGroupData['visibility']);

            $scope = $this->getScopeForCustomerGroupVisibilities($customerGroup);

            $customerGroupProductVisibility->setScope($scope);

            $manager->persist($customerGroupProductVisibility);

            $this->setReference($customerGroupData['reference'], $customerGroupProductVisibility);
        }
    }

    protected function createCustomerVisibilities(
        ObjectManager $manager,
        Product $product,
        array $customersData
    ): void {
        foreach ($customersData as $customerReference => $customerData) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerReference);

            $customerProductVisibility = new CustomerProductVisibility();
            $customerProductVisibility->setProduct($product)
                ->setVisibility($customerData['visibility']);

            $scope = $this->getScopeForCustomerVisibilities($customer);
            $customerProductVisibility->setScope($scope);

            $manager->persist($customerProductVisibility);

            $this->setReference($customerData['reference'], $customerProductVisibility);
        }
    }

    protected function getProductVisibilities(): array
    {
        $fixturesFileName = __DIR__ . '/data/product_visibilities.yml';

        return Yaml::parse(file_get_contents($fixturesFileName));
    }

    protected function getScopeForProductVisibilities(): Scope
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
    }

    protected function getScopeForCustomerGroupVisibilities(CustomerGroup $customerGroup): Scope
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate(CustomerGroupProductVisibility::VISIBILITY_TYPE, ['customerGroup' => $customerGroup]);
    }

    protected function getScopeForCustomerVisibilities(Customer $customer): Scope
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('customer_product_visibility', ['customer' => $customer]);
    }
}
