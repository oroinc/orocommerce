<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var CategoryCaseBuilderInterface */
    protected $accountProductResolvedCacheBuilder;

    /** @var CategoryCaseBuilderInterface */
    protected $accountGroupProductResolvedCacheBuilder;

    /**
     * @param CategoryCaseBuilderInterface $accountProductResolvedCacheBuilder
     */
    public function setAccountProductCacheBuilder(CategoryCaseBuilderInterface $accountProductResolvedCacheBuilder)
    {
        $this->accountProductResolvedCacheBuilder = $accountProductResolvedCacheBuilder;
    }

    /**
     * @param CategoryCaseBuilderInterface $accountGroupProductResolvedCacheBuilder
     */
    public function setAccountGroupProductCacheBuilder(
        CategoryCaseBuilderInterface $accountGroupProductResolvedCacheBuilder
    ) {
        $this->accountGroupProductResolvedCacheBuilder = $accountGroupProductResolvedCacheBuilder;
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
        $this->updateProductVisibilityByCategoryForAccountGroups($category);
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
        $this->updateProductVisibilityByCategoryForAccountGroups($category);
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
        $accountCategoryVisibilities = $this->getAccountVisibilitySettings($category);

        foreach ($accountCategoryVisibilities as $accountCategoryVisibility) {
            $this->accountProductResolvedCacheBuilder->resolveVisibilitySettings($accountCategoryVisibility);
        }
    }

    /**
     * @param Category $category
     */
    protected function updateProductVisibilityByCategoryForAccountGroups(Category $category)
    {
        $accountGroups = $this->getAccountGroupsWithFallbackToAll($category);

        foreach ($accountGroups as $accountGroup) {
            $accountGroupVisibilitySettings = new AccountGroupCategoryVisibility();
            $accountGroupVisibilitySettings->setCategory($category);
            $accountGroupVisibilitySettings->setAccountGroup($accountGroup);

            $this->accountGroupProductResolvedCacheBuilder->resolveVisibilitySettings($accountGroupVisibilitySettings);
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

    /**
     * @param Category $category
     * @return \OroB2B\Bundle\AccountBundle\Entity\AccountGroup[]
     */
    protected function getAccountGroupsWithFallbackToAll(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder();

        $qb->select('accountGroup')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'accountGroupCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('accountGroupCategoryVisibility.accountGroup', 'accountGroup')
            )
            ->where($qb->expr()->isNull('accountGroupCategoryVisibility.visibility'))
            ->orWhere($qb->expr()->neq('accountGroupCategoryVisibility.category', ':category'))
            ->setParameter('category', $category)
            ->distinct();

        return $qb->getQuery()->getResult();
    }
}
