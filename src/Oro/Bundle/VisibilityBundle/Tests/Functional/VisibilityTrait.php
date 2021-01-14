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
    /**
     * @param ManagerRegistry $registry
     * @param VisibilityInterface $visibility
     * @return VisibilityInterface
     */
    public function updateVisibility(ManagerRegistry $registry, VisibilityInterface $visibility)
    {
        $em = $registry->getManagerForClass(ClassUtils::getClass($visibility));
        $em->persist($visibility);
        $em->flush();

        return $visibility;
    }

    /**
     * @param ManagerRegistry $registry
     * @param Category $category
     * @return VisibilityInterface
     */
    public function getCategoryVisibility(ManagerRegistry $registry, Category $category)
    {
        $entity = $registry->getManagerForClass('OroVisibilityBundle:Visibility\CategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CategoryVisibility')
            ->findOneBy(['category' => $category]);

        if (!$entity) {
            $entity = (new CategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()));
        }
        return $entity;
    }

    /**
     * @param ManagerRegistry $registry
     * @param Category $category
     * @param CustomerGroup $customerGroup
     * @return VisibilityInterface
     */
    public function getCategoryVisibilityForCustomerGroup(
        ManagerRegistry $registry,
        Category $category,
        CustomerGroup $customerGroup
    ) {
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $entity = $registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->findOneBy([
                'category' => $category,
                'scope' => $scope,
            ]);
        if (!$entity) {
            $entity = (new CustomerGroupCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }
        return $entity;
    }

    /**
     * @param ManagerRegistry $registry
     * @param Category $category
     * @param Customer $customer
     * @return VisibilityInterface
     */
    public function getCategoryVisibilityForCustomer(ManagerRegistry $registry, Category $category, Customer $customer)
    {
        $scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $entity = $registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->findOneBy([
                'category' => $category,
                'scope' => $scope,
            ]);

        if (!$entity) {
            $entity = (new CustomerCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }
        return $entity;
    }
}
