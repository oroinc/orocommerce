<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadScopeCustomerDemoData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets proper visibility setting to demo categories
 */
class LoadCategoryVisibilityDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
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
            'Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData',
            LoadScopeCustomerDemoData::class
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroVisibilityBundle/Migrations/Data/Demo/ORM/data/categories-visibility.csv');
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $category = $this->getCategory($manager, $row['category']);
            $visibility = $row['visibility'];

            if ($row['all']) {
                $categoryVisibility = $this->createCategoryVisibility($category, $visibility);
                $manager->persist($categoryVisibility);
            }
            if ($row['scopeCustomer']) {
                $customerCategoryVisibility = $this->createCustomerCategoryVisibility(
                    $category,
                    $this->getScopeCustomer($manager, $row['scopeCustomer']),
                    $visibility
                );
                $manager->persist($customerCategoryVisibility);
            }
            if ($row['scopeCustomerGroup']) {
                $customerGroupCategoryVisibility = $this->createCustomerGroupCategoryVisibility(
                    $category,
                    $this->getScopeCustomerGroup($manager, $row['scopeCustomerGroup']),
                    $visibility
                );
                $manager->persist($customerGroupCategoryVisibility);
            }
        }
        fclose($handler);
        $manager->flush();
        $this->container->get('oro_visibility.visibility.cache.product.category.cache_builder')->buildCache();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCategory(ObjectManager $manager, string  $title): Category
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $queryBuilder = $manager->getRepository(Category::class)->findOneByDefaultTitleQueryBuilder($title);
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param ObjectManager $manager
     * @param int $id
     * @return Scope
     */
    protected function getScopeCustomer(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
    }
    /**
     * @param ObjectManager $manager
     * @param int $id
     * @return Scope
     */
    protected function getScopeCustomerGroup(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
    }
    /**
     * @param Category $category
     * @param string $visibility
     * @return CategoryVisibility
     */
    protected function createCategoryVisibility(Category $category, $visibility)
    {
        $categoryVisibility = new CategoryVisibility();
        $categoryVisibility
            ->setCategory($category)
            ->setVisibility($visibility);
        return $categoryVisibility;
    }
    /**
     * @param Category $category
     * @param Scope $scope
     * @param string $visibility
     * @return CustomerCategoryVisibility
     */
    protected function createCustomerCategoryVisibility(Category $category, Scope $scope, $visibility)
    {
        $customerCategoryVisibility = new CustomerCategoryVisibility();
        $customerCategoryVisibility
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($visibility);
        return $customerCategoryVisibility;
    }
    /**
     * @param Category $category
     * @param Scope $scope
     * @param string $visibility
     * @return CustomerGroupCategoryVisibility
     */
    protected function createCustomerGroupCategoryVisibility(Category $category, Scope $scope, $visibility)
    {
        $customerGroupCategoryVisibility = new CustomerGroupCategoryVisibility();
        $customerGroupCategoryVisibility
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($visibility);
        return $customerGroupCategoryVisibility;
    }
}
