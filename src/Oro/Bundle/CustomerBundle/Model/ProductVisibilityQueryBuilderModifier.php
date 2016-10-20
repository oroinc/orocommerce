<?php

namespace Oro\Bundle\CustomerBundle\Model;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Visibility\ProductVisibilityTrait;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

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
        return $this->getAccountProductVisibilityResolvedTermByWebsite(
            $queryBuilder,
            $account,
            $this->websiteManager->getCurrentWebsite()
        );
    }
}

