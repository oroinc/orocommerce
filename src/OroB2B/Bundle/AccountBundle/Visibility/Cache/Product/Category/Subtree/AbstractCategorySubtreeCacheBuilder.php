<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractCategorySubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Account $account
     */
    protected function updateAccountCategoryVisibilityByCategory(array $categoryIds, $visibility, Account $account)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', $visibility)
            ->where($qb->expr()->eq('acvr.account', ':account'))
            ->andWhere($qb->expr()->in('IDENTITY(acvr.category)', ':categoryIds'))
            ->setParameters(['account' => $account, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param $visibility
     * @throws \Exception
     */
    protected function updateCategoryVisibilityCache(Category $category, Account $account, $visibility)
    {
        switch ($visibility) {
            case AccountCategoryVisibility::HIDDEN:
            case AccountCategoryVisibility::VISIBLE:
                $this->updateToStaticCategoryVisibility($category, $account, $visibility);
                break;
            case AccountCategoryVisibility::CATEGORY:
                $this->updateToAllCategoryVisibility($category, $account);
                break;
            case AccountCategoryVisibility::ACCOUNT_GROUP:
                $this->updateToAccountGroupCategoryVisibility($category, $account);
                break;
            case AccountCategoryVisibility::PARENT_CATEGORY:
                $this->updateToParentCategoryVisibility($category, $account);
                break;
            default:
                throw new \Exception();
        }
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param int $visibility
     */
    protected function updateToStaticCategoryVisibility(Category $category, Account $account, $visibility)
    {
        $visibility = $this->convertStaticCategoryVisibility($visibility);

        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if ($categoryVisibilityResolved) {
            $categoryVisibilityResolved->setVisibility($visibility);
        } else {
            $categoryVisibilityResolved = new AccountCategoryVisibilityResolved($category, $account);
            $categoryVisibilityResolved->setVisibility($visibility);
        }

        $categoryVisibilityResolved->setSource(AccountCategoryVisibilityResolved::SOURCE_STATIC);

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $account);
        $this->updateAccountCategoryVisibilityByCategory($childCategoryIds, $visibility, $account);

        $em = $this->getEntityManager();
        $em->persist($categoryVisibilityResolved);
        $em->flush();
    }

    /**
     * @param string $visibility
     * @return int
     */
    protected function convertStaticCategoryVisibility($visibility)
    {
        return ($visibility == CategoryVisibility::VISIBLE)
            ? BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            : BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN;

    }

    protected function updateToAllCategoryVisibility(Category $category, Account $account)
    {
        $visibility = $this->getRepository()
            ->getFallbackToAllValue($category, $this->getCategoryVisibilityConfigValue());
        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if ($categoryVisibilityResolved) {
            $categoryVisibilityResolved->setVisibility($visibility);
        } else {
            $categoryVisibilityResolved = new AccountCategoryVisibilityResolved($category, $account);
            $categoryVisibilityResolved->setVisibility($visibility);
        }

        $categoryVisibilityResolved->setSource(AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL);

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $account);
        $this->updateAccountCategoryVisibilityByCategory($childCategoryIds, $visibility, $account);

        $em = $this->getEntityManager();
        $em->persist($categoryVisibilityResolved);
        $em->flush();
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return int
     */
    protected function getAccountGroupCategoryVisibilityResolvedVisibility(
        Category $category,
        AccountGroup $accountGroup
    ) {
        $categoryVisibilityResolved = $this->getRepository()
            ->findOneBy(['category' => $category, 'accountGroup' => $accountGroup]);

        return $categoryVisibilityResolved->getVisibility();
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return null|AccountCategoryVisibilityResolved
     */
    protected function getCategoryVisibilityResolved(Category $category, Account $account)
    {
        return $this->getRepository()->findOneBy(['category' => $category, 'account' => $account]);
    }

    /**
     * @param Category $category
     * @param Account $account
     */
    protected function updateToAccountGroupCategoryVisibility(Category $category, Account $account)
    {
        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        // Fallback to account group is default for account and should be removed if exist
        if ($categoryVisibilityResolved) {
            $em = $this->getEntityManager();
            $em->remove($categoryVisibilityResolved);
            $em->flush();
        }

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $account);

        $visibility = $this->getRepository()
            ->getFallbackToGroupValue($category, $account, $this->getCategoryVisibilityConfigValue());

        $this->updateAccountCategoryVisibilityByCategory($childCategoryIds, $visibility, $account);
    }

    /**
     * @param Category $category
     * @param Account $account
     */
    protected function updateToParentCategoryVisibility(Category $category, Account $account)
    {
        $parentCategory = $category->getParentCategory();
        $visibility = $this->getRepository()->getCategoryVisibility(
            $parentCategory,
            $account,
            $this->getCategoryVisibilityConfigValue()
        );
        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if ($categoryVisibilityResolved) {
            $categoryVisibilityResolved->setVisibility($visibility);
        } else {
            $categoryVisibilityResolved = new AccountCategoryVisibilityResolved($category, $account);
            $categoryVisibilityResolved->setVisibility($visibility);
        }

        $categoryVisibilityResolved->setSource(AccountCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY);

        $childCategoryIds= $this->getChildCategoryIdsForUpdate($category, $account);
        $this->updateAccountCategoryVisibilityByCategory($childCategoryIds, $visibility, $account);

        $em = $this->getEntityManager();
        $em->persist($categoryVisibilityResolved);
        $em->flush();
    }

    /**
     * @return int
     */
    protected function getCategoryVisibilityConfigValue()
    {
        return ($this->configManager->get('oro_b2b_account.category_visibility') == CategoryVisibility::HIDDEN)
            ? BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            : BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }


    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }
}
