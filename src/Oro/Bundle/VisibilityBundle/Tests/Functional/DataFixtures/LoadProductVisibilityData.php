<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousCustomerGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadProductVisibilityData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadGroups::class,
            LoadCustomers::class,
            LoadCategoryProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // set default fallback to categories
        $configVisibilities = $manager->getRepository('OroVisibilityBundle:Visibility\ProductVisibility')
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

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param array $data
     */
    protected function createProductVisibilities(ObjectManager $manager, Product $product, array $data)
    {
        $productVisibility = new ProductVisibility();
        $productVisibility->setProduct($product)
            ->setVisibility($data['all']['visibility']);

        $scope = $this->container->get('oro_scope.scope_manager')
            ->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
        $productVisibility->setScope($scope);

        $manager->persist($productVisibility);

        $this->setReference($data['all']['reference'], $productVisibility);

        $this->createCustomerGroupVisibilities($manager, $product, $data['groups']);

        $this->createCustomerVisibilities($manager, $product, $data['customers']);
    }

    /**
     * @param string $groupReference
     * @return CustomerGroup
     */
    private function getCustomerGroup($groupReference)
    {
        if ($groupReference === 'customer_group.anonymous') {
            $customerGroup = $this->container
                ->get('doctrine')
                ->getManagerForClass('OroCustomerBundle:CustomerGroup')
                ->getRepository('OroCustomerBundle:CustomerGroup')
                ->findOneBy(['name' => LoadAnonymousCustomerGroup::GROUP_NAME_NON_AUTHENTICATED]);
        } else {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($groupReference);
        }

        return $customerGroup;
    }

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param array $customerGroupsData
     */
    protected function createCustomerGroupVisibilities(
        ObjectManager $manager,
        Product $product,
        array $customerGroupsData
    ) {
        foreach ($customerGroupsData as $groupReference => $customerGroupData) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getCustomerGroup($groupReference);

            $customerGroupProductVisibility = new CustomerGroupProductVisibility();
            $customerGroupProductVisibility->setProduct($product)
                ->setVisibility($customerGroupData['visibility']);

            $scopeManager = $this->container->get('oro_scope.scope_manager');
            $scope = $scopeManager->findOrCreate(
                CustomerGroupProductVisibility::VISIBILITY_TYPE,
                ['customerGroup' => $customerGroup]
            );

            $customerGroupProductVisibility->setScope($scope);

            $manager->persist($customerGroupProductVisibility);

            $this->setReference($customerGroupData['reference'], $customerGroupProductVisibility);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param array $customersData
     */
    protected function createCustomerVisibilities(
        ObjectManager $manager,
        Product $product,
        array $customersData
    ) {
        foreach ($customersData as $customerReference => $customerData) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerReference);

            $customerProductVisibility = new CustomerProductVisibility();
            $customerProductVisibility->setProduct($product)
                ->setVisibility($customerData['visibility']);

            $scopeManager = $this->container->get('oro_scope.scope_manager');
            $scope = $scopeManager->findOrCreate('customer_product_visibility', ['customer' => $customer]);
            $customerProductVisibility->setScope($scope);

            $manager->persist($customerProductVisibility);

            $this->setReference($customerData['reference'], $customerProductVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getProductVisibilities()
    {
        $fixturesFileName = __DIR__ . '/data/product_visibilities.yml';

        return Yaml::parse(file_get_contents($fixturesFileName));
    }
}
