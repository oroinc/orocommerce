<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;

class VisibilityChangeAccountSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param Scope $scope
     * @param int $categoryVisibility visible|hidden|config
     */
    public function resolveVisibilitySettings(Category $category, Scope $scope, $categoryVisibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $scope);

        /** @var AccountCategoryRepository $repository */
        $repository = $this->registry->getManagerForClass(AccountCategoryVisibilityResolved::class)
            ->getRepository(AccountCategoryVisibilityResolved::class);

        $repository->updateAccountCategoryVisibilityByCategory($scope, $childCategoryIds, $categoryVisibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateAccountProductVisibilityByCategory($categoryIds, $categoryVisibility, $scope);
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
     * @param Scope $scope
     */
    protected function updateAccountProductVisibilityByCategory(array $categoryIds, $visibility, Scope $scope)
    {
        if (!$categoryIds) {
            return;
        }
        $productScopes = $this->scopeManager->findRelatedScopeIds('account_product_visibility', $scope);

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where($qb->expr()->in('apvr.scope', ':scopes'))
            ->andWhere($qb->expr()->in('IDENTITY(apvr.category)', ':categoryIds'))
            ->setParameters(['scopes' => $productScopes, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {

        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\AccountCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.scope', ':scope')
            )
        )
        ->setParameter('scope', $target);
    }
}
