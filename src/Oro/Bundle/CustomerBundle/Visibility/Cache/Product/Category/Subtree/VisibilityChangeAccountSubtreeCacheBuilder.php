<?php

namespace Oro\Bundle\CustomerBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;

class VisibilityChangeAccountSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param Account $account
     * @param int $categoryVisibility visible|hidden|config
     */
    public function resolveVisibilitySettings(Category $category, Account $account, $categoryVisibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $account);

        $this->registry->getManagerForClass('OroCustomerBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroCustomerBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->updateAccountCategoryVisibilityByCategory($account, $childCategoryIds, $categoryVisibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateAccountProductVisibilityByCategory($categoryIds, $categoryVisibility, $account);
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Account $account
     */
    protected function updateAccountProductVisibilityByCategory(array $categoryIds, $visibility, Account $account)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCustomerBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroCustomerBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where($qb->expr()->eq('apvr.account', ':account'))
            ->andWhere($qb->expr()->in('IDENTITY(apvr.category)', ':categoryIds'))
            ->setParameters(['account' => $account, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroCustomerBundle:Visibility\AccountCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.account', ':account')
            )
        )
        ->setParameter('account', $target);
    }
}
