<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

trait VisibilityTrait
{
    private function updateVisibility(ManagerRegistry $registry, VisibilityInterface $visibility): VisibilityInterface
    {
        $em = $registry->getManagerForClass(ClassUtils::getClass($visibility));
        $em->persist($visibility);
        $em->flush();

        return $visibility;
    }

    private function getCategoryVisibility(ManagerRegistry $registry, Category $category): VisibilityInterface
    {
        $entity = $registry->getRepository(CategoryVisibility::class)
            ->findOneBy(['category' => $category]);

        if (!$entity) {
            $entity = (new CategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()));
        }
        return $entity;
    }

    private function getCategoryVisibilityForCustomerGroup(
        ManagerRegistry $registry,
        Category $category,
        CustomerGroup $customerGroup
    ): VisibilityInterface {
        $scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $entity = $registry->getRepository(CustomerGroupCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);
        if (!$entity) {
            $entity = (new CustomerGroupCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }
        return $entity;
    }

    private function getCategoryVisibilityForCustomer(
        ManagerRegistry $registry,
        Category $category,
        Customer $customer
    ): VisibilityInterface {
        $scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $entity = $registry->getRepository(CustomerCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);

        if (!$entity) {
            $entity = (new CustomerCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }
        return $entity;
    }
}
