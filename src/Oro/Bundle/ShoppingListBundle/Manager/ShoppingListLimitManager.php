<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Maintains shopping lists limit for user
 */
class ShoppingListLimitManager
{
    /** @var ConfigManager */
    private $configManager;

    /** @var TokenAccessor */
    protected $tokenAccessor;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var bool */
    private $isCreateEnabled;

    /** @var bool */
    private $isCreateEnabledForCustomerUser;

    /** @var bool */
    private $isOnlyOneEnabled;

    public function __construct(
        ConfigManager $configManager,
        TokenAccessor $tokenAccessor,
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager
    ) {
        $this->configManager  = $configManager;
        $this->tokenAccessor = $tokenAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteManager = $websiteManager;
    }

    public function resetState()
    {
        $this->isCreateEnabled = null;
        $this->isCreateEnabledForCustomerUser = null;
        $this->isOnlyOneEnabled = null;
    }

    /**
     * Check if shopping list configuration limit is reached for logged customer user
     *
     * @return bool
     */
    public function isReachedLimit()
    {
        return !$this->isCreateEnabled();
    }

    /**
     * Restricts creating new shopping list if configuration limit is reached / or Customer is not logged in
     * @return bool
     */
    public function isCreateEnabled()
    {
        //Shopping list create disabled for not logged users not depending n limit setting
        if (!$this->tokenAccessor->hasUser()) {
            return false;
        }

        if (null !== $this->isCreateEnabled) {
            return $this->isCreateEnabled;
        }

        $limitConfig = $this->getShoppingListLimit();
        $this->isCreateEnabled = true;

        if ($limitConfig) {
            $user = $this->tokenAccessor->getUser();
            // Limit of created shopping lists is already reached
            if ($this->countUserShoppingLists($user) >= $limitConfig) {
                $this->isCreateEnabled = false;
            }
        }

        return $this->isCreateEnabled;
    }

    /**
     * @param CustomerUser $user
     *
     * @return bool
     */
    public function isCreateEnabledForCustomerUser(CustomerUser $user)
    {
        if (null !== $this->isCreateEnabledForCustomerUser) {
            return $this->isCreateEnabledForCustomerUser;
        }

        $limitConfig = $this->getShoppingListLimit($user->getWebsite());

        $this->isCreateEnabledForCustomerUser = true;
        if ($limitConfig) {
            // Limit of created shopping lists is already reached
            if ($this->countUserShoppingLists($user) >= $limitConfig) {
                $this->isCreateEnabledForCustomerUser = false;
            }
        }

        return $this->isCreateEnabledForCustomerUser;
    }

    /**
     * Checks is only one shopping list is available for a user
     * @return bool
     */
    public function isOnlyOneEnabled()
    {
        if (!$this->tokenAccessor->hasUser()) {
            return true;
        }

        if (null !== $this->isOnlyOneEnabled) {
            return $this->isOnlyOneEnabled;
        }

        $limitConfig = $this->getShoppingListLimit();

        $this->isOnlyOneEnabled = false;
        if ($limitConfig) {
            $user = $this->tokenAccessor->getUser();
            // Limit set to one and user has one shopping list
            if ($limitConfig === 1 && $this->countUserShoppingLists($user) === 1) {
                $this->isOnlyOneEnabled = true;
            }
        }

        return $this->isOnlyOneEnabled;
    }

    /**
     * @param AbstractUser $user
     * @return int
     */
    private function countUserShoppingLists(AbstractUser $user)
    {
        /** @var ShoppingListRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ShoppingList::class);

        $currentWebsite = $this->websiteManager->getCurrentWebsite();

        return $repository->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId(),
            $currentWebsite
        );
    }

    /**
     * @param Website|null $website
     * @return integer
     */
    private function getShoppingListLimit(Website $website = null)
    {
        return (int) $this->configManager->get('oro_shopping_list.shopping_list_limit', false, false, $website);
    }

    /**
     * @return integer
     */
    public function getShoppingListLimitForUser()
    {
        if (!$this->tokenAccessor->hasUser()) {
            return 1;
        }
        $user = $this->tokenAccessor->getUser();

        return $this->getShoppingListLimit(
            $user->getWebsite()
        );
    }
}
