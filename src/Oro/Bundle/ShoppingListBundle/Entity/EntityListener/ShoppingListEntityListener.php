<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\Configuration;

class ShoppingListEntityListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    /**
     * @param ConfigManager $configManager
     * @param GuestShoppingListManager $guestShoppingListManager
     */
    public function __construct(ConfigManager $configManager, GuestShoppingListManager $guestShoppingListManager)
    {
        $this->configManager = $configManager;
        $this->guestShoppingListManager = $guestShoppingListManager;
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function prePersist(ShoppingList $shoppingList)
    {
        if (!$shoppingList->getOwner()) {
            $settingsKey = TreeUtils::getConfigKey(
                OroShoppingListExtension::ALIAS,
                Configuration::DEFAULT_GUEST_SHOPPING_LIST_OWNER
            );

            $userId = $this->configManager->get($settingsKey);

            /** @var User $user */
            $user = $this->guestShoppingListManager->getDefaultUser($userId);

            $shoppingList->setOwner($user);
        }
    }
}
