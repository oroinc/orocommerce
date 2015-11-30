<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /**
     * @param VisibilityInterface|AccountGroupCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $accountGroup = $visibilitySettings->getAccountGroup();

        $visibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $accountGroup);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility, $accountGroup);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountGroupCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function categoryPositionChanged(Category $category)
    {
        $accountGroups = $this->getAccountGroupsForUpdate();

        foreach ($accountGroups as $accountGroup) {
            $visibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
            $visibility = $this->convertVisibility($visibility);

            $categoryIds = $this->getCategoryIdsForUpdate($category, $accountGroup);
            $this->updateProductVisibilityByCategory($categoryIds, $visibility, $accountGroup);
        }
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
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param AccountGroup $accountGroup
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility, AccountGroup $accountGroup)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->eq('agpvr.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->in('agpvr.categoryId', ':categoryIds'))
            ->setParameters(['accountGroup' => $accountGroup, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target = null)
    {
        return $qb->leftJoin(
            $this->categoryVisibilityClass,
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.accountGroup', ':accountGroup')
            )
        )
        ->setParameter('accountGroup', $target);
    }

    /**
     * @return AccountGroup[]
     */
    protected function getAccountGroupsForUpdate()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder();

        $qb->select('accountGroup')
            ->from('OroB2BAccountBundle:AccountGroup', 'accountGroup')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'AccountGroupCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('AccountGroupCategoryVisibility.accountGroup', 'accountGroup')
            )
            ->where($qb->expr()->eq('AccountGroupCategoryVisibility.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountGroupCategoryVisibility::PARENT_CATEGORY);

        return $qb->getQuery()->getResult();
    }
}
