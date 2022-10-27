<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadCategoryVisibilityData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ObjectManager */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

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

        $categories = $this->getCategoryVisibilityData();

        foreach ($categories as $categoryReference => $categoryVisibilityData) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $this->createCategoryVisibilities($category, $categoryVisibilityData);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomers::class,
            LoadGroups::class,
            LoadCategoryData::class,
        ];
    }

    protected function createCategoryVisibilities(Category $category, array $categoryData)
    {
        $this->createCategoryVisibility($category, $categoryData['all']);

        $this->createCustomerGroupCategoryVisibilities($category, $categoryData['groups']);

        $this->createCustomerCategoryVisibilities($category, $categoryData['customers']);
    }

    protected function createCategoryVisibility(Category $category, array $data)
    {
        if (!$data['visibility']) {
            return;
        }

        $scope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, []);
        $categoryVisibility = (new CategoryVisibility())
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($data['visibility']);

        $this->setReference($data['reference'], $categoryVisibility);

        $this->em->persist($categoryVisibility);
        $this->em->flush($categoryVisibility);
    }

    protected function createCustomerGroupCategoryVisibilities(Category $category, array $customerGroupVisibilityData)
    {
        foreach ($customerGroupVisibilityData as $customerGroupReference => $data) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference($customerGroupReference);
            $scope = $this->scopeManager->findOrCreate(
                CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
                ['customerGroup' => $customerGroup]
            );

            $customerGroupCategoryVisibility = (new CustomerGroupCategoryVisibility())
                ->setCategory($category)
                ->setScope($scope)
                ->setVisibility($data['visibility']);

            $this->setReference($data['reference'], $customerGroupCategoryVisibility);

            $this->em->persist($customerGroupCategoryVisibility);
            $this->em->flush($customerGroupCategoryVisibility);
        }
    }

    protected function createCustomerCategoryVisibilities(Category $category, array $customerVisibilityData)
    {
        foreach ($customerVisibilityData as $customerReference => $data) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerReference);
            $scope = $this->scopeManager->findOrCreate(
                CustomerCategoryVisibility::VISIBILITY_TYPE,
                ['customer' => $customer]
            );

            $customerCategoryVisibility = (new CustomerCategoryVisibility())
                ->setCategory($category)
                ->setScope($scope)
                ->setVisibility($data['visibility']);

            $this->setReference($data['reference'], $customerCategoryVisibility);

            $this->em->persist($customerCategoryVisibility);
            $this->em->flush($customerCategoryVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getCategoryVisibilityData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'category_visibilities.yml';

        return Yaml::parse(file_get_contents($filePath));
    }
}
