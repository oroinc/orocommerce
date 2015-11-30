<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var CategoryCaseBuilderInterface */
    protected $dependVisibilityCacheBuilder;

    /**
     * @param $dependVisibilityCacheBuilder
     */
    public function setDependVisibilityCacheBuilder(CategoryCaseBuilderInterface $dependVisibilityCacheBuilder)
    {
        $this->dependVisibilityCacheBuilder = $dependVisibilityCacheBuilder;
    }

    /**
     * @param VisibilityInterface|CategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();

        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);
        $this->updateProductVisibilityByCategoryForAccounts($category);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof CategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);
        $this->updateProductVisibilityByCategoryForAccounts($category);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement buildCache() method.
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNotNull('cv.visibility'));
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->isNull('cv.visibility'));
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'pvr')
            ->set('pvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('pvr.categoryId', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            $this->categoryVisibilityClass,
            'cv',
            Join::WITH,
            $qb->expr()->eq('node', 'cv.category')
        );
    }

    /**
     * @param Category $category
     */
    protected function updateProductVisibilityByCategoryForAccounts(Category $category)
    {
        $AccountCategoryVisibilities = $this->getAccountVisibilitySettings($category);

        foreach ($AccountCategoryVisibilities as $AccountCategoryVisibility) {
            $this->dependVisibilityCacheBuilder->resolveVisibilitySettings($AccountCategoryVisibility);
        }
    }

    /**
     * @param Category $category
     * @return \OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility[]
     */
    protected function getAccountVisibilitySettings(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->createQueryBuilder();

        $qb->select('accountCategoryVisibility')
            ->from('OroB2BAccountBundle:Visibility\AccountCategoryVisibility', 'accountCategoryVisibility')
            ->where($qb->expr()->eq('accountCategoryVisibility.visibility', ':categoryVisibility'))
            ->andWhere($qb->expr()->eq('accountCategoryVisibility.category', ':category'))
            ->setParameters([
                'categoryVisibility' => AccountCategoryVisibility::CATEGORY,
                'category' => $category
            ]);

        return $qb->getQuery()->getResult();
    }
}
