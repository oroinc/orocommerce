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

    /**
     * @param ConfigManager $configManager
     * @param TokenAccessor $tokenAccessor
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteManager $websiteManager
     */
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
        $limitConfig = $this->getShoppingListLimit();

        if ($limitConfig) {
            $user = $this->tokenAccessor->getUser();
            // Limit of created shopping lists is already reached
            if ($this->countUserShoppingLists($user) >= $limitConfig) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CustomerUser $user
     *
     * @return bool
     */
    public function isCreateEnabledForCustomerUser(CustomerUser $user)
    {
        $limitConfig = $this->getShoppingListLimit($user->getWebsite());

        if ($limitConfig) {
            // Limit of created shopping lists is already reached
            if ($this->countUserShoppingLists($user) >= $limitConfig) {
                return false;
            }
        }

        return true;
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

        $limitConfig = $this->getShoppingListLimit();

        if ($limitConfig) {
            $user = $this->tokenAccessor->getUser();
            // Limit set to one and user has one shopping list
            if ($limitConfig === 1 && $this->countUserShoppingLists($user) === 1) {
                return true;
            }
        }

        return false;
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
