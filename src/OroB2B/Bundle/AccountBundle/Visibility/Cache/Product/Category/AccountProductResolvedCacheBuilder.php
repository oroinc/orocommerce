<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeAccountSubtreeCacheBuilder;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /** @var VisibilityChangeAccountSubtreeCacheBuilder */
    protected $visibilityChangeAccountSubtreeCacheBuilder;

    /**
     * @param VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
     */
    public function setVisibilityChangeAccountSubtreeCacheBuilder(
        VisibilityChangeAccountSubtreeCacheBuilder $visibilityChangeAccountSubtreeCacheBuilder
    ) {
        $this->visibilityChangeAccountSubtreeCacheBuilder = $visibilityChangeAccountSubtreeCacheBuilder;
    }

    /**
     * @param VisibilityInterface|AccountCategoryVisibility $visibilitySettings
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        $category = $visibilitySettings->getCategory();
        $account = $visibilitySettings->getAccount();

        $this->visibilityChangeAccountSubtreeCacheBuilder->resolveVisibilitySettings($category, $account);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        return $visibilitySettings instanceof AccountCategoryVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        // TODO: Implement buildCache() method.
    }

    /**
     * @return Account[]
     */
    protected function getAccountsForUpdate()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:Account')
            ->createQueryBuilder();

        $qb->select('account')
            ->from('OroB2BAccountBundle:Account', 'account')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountCategoryVisibility',
                'AccountCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('AccountCategoryVisibility.account', 'account')
            )
            ->where($qb->expr()->eq('AccountCategoryVisibility.visibility', ':parentCategory'))
            ->setParameter('parentCategory', AccountCategoryVisibility::PARENT_CATEGORY);

        return $qb->getQuery()->getResult();
    }
}
