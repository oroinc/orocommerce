<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;

class VisibilityChangeCustomerSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param Scope    $scope
     * @param int      $categoryVisibility visible|hidden|config
     *
     * @return array|int[] Affected categories id
     */
    public function resolveVisibilitySettings(Category $category, Scope $scope, $categoryVisibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $scope);

        /** @var CustomerCategoryRepository $repository */
        $repository = $this->registry->getManagerForClass(CustomerCategoryVisibilityResolved::class)
            ->getRepository(CustomerCategoryVisibilityResolved::class);

        $repository->updateCustomerCategoryVisibilityByCategory($scope, $childCategoryIds, $categoryVisibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateCustomerProductVisibilityByCategory($categoryIds, $categoryVisibility, $scope);

        return $categoryIds;
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', CustomerCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', CustomerCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Scope $scope
     */
    protected function updateCustomerProductVisibilityByCategory(array $categoryIds, $visibility, Scope $scope)
    {
        if (!$categoryIds) {
            return;
        }
        $productScopes = $this->scopeManager->findRelatedScopeIds('customer_product_visibility', $scope);

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', ':visibility')
            ->where($qb->expr()->in('apvr.scope', ':scopes'))
            ->andWhere($qb->expr()->in('IDENTITY(apvr.category)', ':categoryIds'))
            ->setParameters(['scopes' => $productScopes, 'categoryIds' => $categoryIds, ':visibility' => $visibility]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\CustomerCategoryVisibility',
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
