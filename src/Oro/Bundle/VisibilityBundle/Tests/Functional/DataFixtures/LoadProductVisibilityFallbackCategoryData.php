<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductVisibilityFallbackCategoryData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var array
     */
    protected $products = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_4,
        LoadProductData::PRODUCT_5,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
    ];

    /**
     * @var array
     */
    protected $customerGroups = [
        LoadGroups::GROUP2,
        LoadGroups::GROUP3,
    ];

    /**
     * @var array
     */
    protected $customers = [
        'customer.level_1.1',
        'customer.level_1.2',
        'customer.level_1.2.1',
        'customer.level_1.2.1.1',
        'customer.level_1.3.1',
        'customer.level_1.3.1.1',
        'customer.level_1.4',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryVisibilityData::class,
            LoadCategoryProductData::class,
        ];
    }

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
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;
        $this->scopeManager = $this->container->get('oro_scope.scope_manager');

        foreach ($this->products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibility($product);
            foreach ($this->customerGroups as $customerGroupReference) {
                /** @var CustomerGroup $customerGroup */
                $customerGroup = $this->getReference($customerGroupReference);
                $this->createCustomerGroupProductVisibilityResolved($customerGroup, $product);
            }
            foreach ($this->customers as $customerReference) {
                /** @var Customer $customer */
                $customer = $this->getReference($customerReference);
                $this->createCustomerProductVisibilityResolved($customer, $product);
            }
        }

        $this->em->flush();
    }

    protected function createProductVisibility(Product $product)
    {
        $scope = $this->scopeManager->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(ProductVisibility::CATEGORY);

        $this->em->persist($productVisibility);
    }

    protected function createCustomerGroupProductVisibilityResolved(CustomerGroup $customerGroup, Product $product)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $customerGroupVisibility = (new CustomerGroupProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(CustomerGroupProductVisibility::CATEGORY);

        $this->em->persist($customerGroupVisibility);
    }

    protected function createCustomerProductVisibilityResolved(Customer $customer, Product $product)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerProductVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $customerVisibility = (new CustomerProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(CustomerProductVisibility::CATEGORY);

        $this->em->persist($customerVisibility);
    }
}
