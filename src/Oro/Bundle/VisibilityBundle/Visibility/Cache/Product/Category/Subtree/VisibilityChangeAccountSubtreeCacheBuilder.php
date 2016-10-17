<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountCategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;

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

        $scope = $this->scopeManager->find('account_category_visibility', ['account' => $account]);

        /** @var AccountCategoryVisibilityRepository $repository */
        $repository = $this->registry->getManagerForClass(AccountCategoryVisibilityResolved::class)
            ->getRepository(AccountCategoryVisibilityResolved::class);

        $repository->updateAccountCategoryVisibilityByCategory($scope, $childCategoryIds, $categoryVisibility);

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
        $scope = $this->scopeManager->find('account_category_visibility', ['account' => $account]);
        if (!$scope) {
            return;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where($qb->expr()->eq('apvr.scope', ':scope'))
            ->andWhere($qb->expr()->in('IDENTITY(apvr.category)', ':categoryIds'))
            ->setParameters(['scope' => $scope, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        $scope = $this->scopeManager->find('account_category_visibility', ['account' => $target]);

        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\AccountCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.scope', ':scope')
            )
        )
        ->setParameter('scope', $scope);
    }
}
