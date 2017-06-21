<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Maintains shopping lists limit for user
 */
class ShoppingListLimitManager
{
    /** @var ConfigManager */
    private $configManager;

    /** @var TokenAccessor*/
    protected $tokenAccessor;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param TokenAccessor $tokenAccessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        TokenAccessor $tokenAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager  = $configManager;
        $this->tokenAccessor = $tokenAccessor;
        $this->doctrineHelper = $doctrineHelper;
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
        $limitConfig = $this->configManager->get('oro_shopping_list.shopping_list_limit');

        if ($limitConfig) {
            // Limit of created shopping lists is already reached
            if ($this->countUserShoppingLists() >= $limitConfig) {
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

        $limitConfig = $this->configManager->get('oro_shopping_list.shopping_list_limit');

        if ($limitConfig) {
            // Limit set to one and user has one shopping list
            if ($limitConfig === 1 && $this->countUserShoppingLists() === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    private function countUserShoppingLists()
    {
        /** @var ShoppingListRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ShoppingList::class);
        $user = $this->tokenAccessor->getUser();

        return $repository->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId()
        );
    }
}
