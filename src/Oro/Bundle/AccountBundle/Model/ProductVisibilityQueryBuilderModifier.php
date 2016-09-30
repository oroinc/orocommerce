<?php

namespace Oro\Bundle\AccountBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;

class ProductVisibilityQueryBuilderModifier
{
    use ProductVisibilityTrait;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @param ConfigManager $configManager
     * @param TokenStorageInterface $tokenStorage
     * @param WebsiteManager $websiteManager
     * @param AccountUserRelationsProvider $relationsProvider
     */
    public function __construct(
        ConfigManager $configManager,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        AccountUserRelationsProvider $relationsProvider
    ) {
        $this->configManager = $configManager;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->relationsProvider = $relationsProvider;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function modify(QueryBuilder $queryBuilder)
    {
        $accountUser = $this->getAccountUser();
        $visibilities = [$this->getProductVisibilityResolvedTerm($queryBuilder)];

        $accountGroup = $this->relationsProvider->getAccountGroup($accountUser);
        if ($accountGroup) {
            $visibilities[] = $this->getAccountGroupProductVisibilityResolvedTerm(
                $queryBuilder,
                $accountGroup
            );
        }

        $account = $this->relationsProvider->getAccount($accountUser);
        if ($account) {
            $visibilities[] = $this->getAccountProductVisibilityResolvedTerm($queryBuilder, $account);
        }

        $queryBuilder->andWhere($queryBuilder->expr()->gt(implode(' + ', $visibilities), 0));
    }

    /**
     * @return AccountUser|null
     */
    protected function getAccountUser()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return string
     */
    protected function getProductVisibilityResolvedTerm(QueryBuilder $queryBuilder)
    {
        return $this->getProductVisibilityResolvedTermByWebsite(
            $queryBuilder,
            $this->websiteManager->getCurrentWebsite()
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param AccountGroup $account
     * @return string
     */
    protected function getAccountGroupProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, AccountGroup $account)
    {
        return $this->getAccountGroupProductVisibilityResolvedTermByWebsite(
            $queryBuilder,
            $account,
            $this->websiteManager->getCurrentWebsite()
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Account $account
     * @return string
     */
    protected function getAccountProductVisibilityResolvedTerm(QueryBuilder $queryBuilder, Account $account)
    {
        $queryBuilder->leftJoin(
            'Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved',
            'account_product_visibility_resolved',
            Join::WITH,
            $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq(
                    $this->getRootAlias($queryBuilder),
                    'account_product_visibility_resolved.product'
                ),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.account', ':_account'),
                $queryBuilder->expr()->eq('account_product_visibility_resolved.website', ':_website')
            )
        );

        $queryBuilder->setParameter('_account', $account);
        $queryBuilder->setParameter('_website', $this->websiteManager->getCurrentWebsite());

        $productFallback = $this->addCategoryConfigFallback('product_visibility_resolved.visibility');
        $accountFallback = $this->addCategoryConfigFallback('account_product_visibility_resolved.visibility');

        $term = <<<TERM
CASE WHEN account_product_visibility_resolved.visibility = %s
    THEN (COALESCE(%s, %s) * 100)
ELSE (COALESCE(%s, 0) * 100)
END
TERM;
        return sprintf(
            $term,
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $productFallback,
            $this->getProductConfigValue(),
            $accountFallback
        );
    }
}
