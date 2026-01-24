<?php

namespace Oro\Bundle\ShoppingListBundle\Placeholder;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides authorization checks for shopping list-related UI placeholders.
 *
 * This filter is used in placeholder configurations to determine whether certain UI elements
 * related to shopping lists should be displayed based on the current user's permissions.
 * It checks ACL permissions to control the visibility of features like "Add to Shopping List" buttons
 * and other shopping list interaction elements in the storefront.
 */
class PlaceholderFilter
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return bool
     */
    public function userCanCreateLineItem()
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update');
    }
}
