<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Denies access to shopping lists created on another websites.
 */
class ShoppingListVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = [
        BasicPermission::VIEW,
        BasicPermission::CREATE,
        BasicPermission::EDIT,
        BasicPermission::DELETE
    ];

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    public function setWebsiteManager(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (!$currentWebsite = $this->websiteManager->getCurrentWebsite()) {
            return self::ACCESS_ABSTAIN;
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->doctrineHelper->getEntity($class, $identifier);
        if (!$shoppingList || !$shoppingList->getWebsite()) {
            return self::ACCESS_ABSTAIN;
        }

        return $shoppingList->getWebsite()->getId() === $currentWebsite->getId() ?
            self::ACCESS_GRANTED :
            self::ACCESS_DENIED;
    }
}
