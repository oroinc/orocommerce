<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
     * @param AccountGroup $accountGroup
     * @return VisibilityInterface
     */
    public function getCategoryVisibilityForAccountGroup(
        ManagerRegistry $registry,
        Category $category,
        AccountGroup $accountGroup
    ) {
        $entity = $registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupCategoryVisibility')
            ->findOneBy([
                'category' => $category,
                'accountGroup' => $accountGroup,
            ]);

        if (!$entity) {
            $entity = (new AccountGroupCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setAccountGroup($accountGroup);
        }
        return $entity;
    }

    /**
     * @param ManagerRegistry $registry
     * @param Category $category
     * @param Account $account
     * @return VisibilityInterface
     */
    public function getCategoryVisibilityForAccount(ManagerRegistry $registry, Category $category, Account $account)
    {
        $entity = $registry->getManagerForClass('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->findOneBy([
                'category' => $category,
                'account' => $account,
            ]);

        if (!$entity) {
            $entity = (new AccountCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity->getDefault($entity->getTargetEntity()))
                ->setAccount($account);
        }
        return $entity;
    }
}
