<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityManager;

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
     * @param Category $category
     * @param Account $account
     * @param array $childCategoryIds
     * @param string $visibility
     */
    protected function updateCategoryVisibilityCache(
        Category $category,
        Account $account,
        array $childCategoryIds,
        $visibility
    ) {
        switch ($visibility) {
            case AccountCategoryVisibility::HIDDEN:
            case AccountCategoryVisibility::VISIBLE:
                $this->updateToStaticCategoryVisibility($category, $account, $childCategoryIds, $visibility);
                break;
            case AccountCategoryVisibility::CATEGORY:
                $this->updateToAllCategoryVisibility($category, $account, $childCategoryIds);
                break;
            case AccountCategoryVisibility::ACCOUNT_GROUP:
                $this->updateToAccountGroupCategoryVisibility($category, $account, $childCategoryIds);
                break;
            case AccountCategoryVisibility::PARENT_CATEGORY:
                $this->updateToParentCategoryVisibility($category, $account, $childCategoryIds);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown visibility %s', $visibility));
        }
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param array $childCategoryIds
     * @param int $visibility
     */
    protected function updateToStaticCategoryVisibility(
        Category $category,
        Account $account,
        array $childCategoryIds,
        $visibility
    ) {
        $visibility = $this->convertStaticCategoryVisibility($visibility);

        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if ($categoryVisibilityResolved) {
            $categoryVisibilityResolved->setVisibility($visibility);
        } else {
            $categoryVisibilityResolved = new AccountCategoryVisibilityResolved($category, $account);
            $categoryVisibilityResolved->setVisibility($visibility);
        }

        $categoryVisibilityResolved->setSource(AccountCategoryVisibilityResolved::SOURCE_STATIC);

        $this->getRepository()->updateAccountCategoryVisibilityByCategory($account, $childCategoryIds, $visibility);

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

    /**
     * @param Category $category
     * @param Account $account
     * @param array $childCategoryIds
     */
    protected function updateToAllCategoryVisibility(Category $category, Account $account, array $childCategoryIds)
    {
        $visibility = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getFallbackToAllVisibility($category, $this->getCategoryVisibilityConfigValue());
        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        if ($categoryVisibilityResolved) {
            $categoryVisibilityResolved->setVisibility($visibility);
        } else {
            $categoryVisibilityResolved = new AccountCategoryVisibilityResolved($category, $account);
            $categoryVisibilityResolved->setVisibility($visibility);
        }

        $categoryVisibilityResolved->setSource(AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL);

        $this->getRepository()->updateAccountCategoryVisibilityByCategory($account, $childCategoryIds, $visibility);

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
        return $this->getRepository()->findByPrimaryKey($category, $account);
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param array $childCategoryIds
     */
    protected function updateToAccountGroupCategoryVisibility(
        Category $category,
        Account $account,
        array $childCategoryIds
    ) {
        $categoryVisibilityResolved = $this->getCategoryVisibilityResolved($category, $account);

        // Fallback to account group is default for account and should be removed if exist
        if ($categoryVisibilityResolved) {
            $em = $this->getEntityManager();
            $em->remove($categoryVisibilityResolved);
            $em->flush();
        }

        $visibility = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getFallbackToGroupVisibility($category, $account->getGroup(), $this->getCategoryVisibilityConfigValue());

        $this->getRepository()->updateAccountCategoryVisibilityByCategory($account, $childCategoryIds, $visibility);
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param array $childCategoryIds
     */
    protected function updateToParentCategoryVisibility(Category $category, Account $account, array $childCategoryIds)
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

        $this->getRepository()->updateAccountCategoryVisibilityByCategory($account, $childCategoryIds, $visibility);

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
