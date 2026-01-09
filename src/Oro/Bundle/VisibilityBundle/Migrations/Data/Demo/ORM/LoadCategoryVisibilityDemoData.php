<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadScopeCustomerDemoData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Sets proper visibility setting to demo categories
 */
class LoadCategoryVisibilityDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCategoryDemoData::class,
            LoadScopeCustomerDemoData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
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
                    $this->getScope($manager, $row['scopeCustomer']),
                    $visibility
                );
                $manager->persist($customerCategoryVisibility);
            }
            if ($row['scopeCustomerGroup']) {
                $customerGroupCategoryVisibility = $this->createCustomerGroupCategoryVisibility(
                    $category,
                    $this->getScope($manager, $row['scopeCustomerGroup']),
                    $visibility
                );
                $manager->persist($customerGroupCategoryVisibility);
            }
        }
        fclose($handler);
        $manager->flush();
        $this->container->get('oro_visibility.visibility.cache.product.category.cache_builder')->buildCache();
    }

    private function getCategory(ObjectManager $manager, string $title): Category
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $queryBuilder = $manager->getRepository(Category::class)->findOneByDefaultTitleQueryBuilder($title);
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    private function getScope(ObjectManager $manager, int $id): Scope
    {
        return $manager->getRepository(Scope::class)->findOneBy(['id' => $id]);
    }

    private function createCategoryVisibility(Category $category, string $visibility): CategoryVisibility
    {
        $categoryVisibility = new CategoryVisibility();
        $categoryVisibility->setCategory($category);
        $categoryVisibility->setVisibility($visibility);

        return $categoryVisibility;
    }

    private function createCustomerCategoryVisibility(
        Category $category,
        Scope $scope,
        string $visibility
    ): CustomerCategoryVisibility {
        $customerCategoryVisibility = new CustomerCategoryVisibility();
        $customerCategoryVisibility->setCategory($category);
        $customerCategoryVisibility->setScope($scope);
        $customerCategoryVisibility->setVisibility($visibility);

        return $customerCategoryVisibility;
    }

    private function createCustomerGroupCategoryVisibility(
        Category $category,
        Scope $scope,
        string $visibility
    ): CustomerGroupCategoryVisibility {
        $customerGroupCategoryVisibility = new CustomerGroupCategoryVisibility();
        $customerGroupCategoryVisibility->setCategory($category);
        $customerGroupCategoryVisibility->setScope($scope);
        $customerGroupCategoryVisibility->setVisibility($visibility);

        return $customerGroupCategoryVisibility;
    }
}
