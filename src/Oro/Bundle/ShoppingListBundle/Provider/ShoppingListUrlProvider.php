<?php

namespace Oro\Bundle\ShoppingListBundle\Provider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides url for the shopping list entity depends on user access permissions.
 */
class ShoppingListUrlProvider
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $urlGenerator,
        ShoppingListLimitManager $shoppingListLimitManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->urlGenerator = $urlGenerator;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
    }

    public function getFrontendUrl(?ShoppingList $shoppingList = null): string
    {
        if (!$shoppingList || $this->shoppingListLimitManager->isOnlyOneEnabled()) {
            if ($this->authorizationChecker->isGranted('oro_shopping_list_frontend_update')) {
                return $this->urlGenerator->generate('oro_shopping_list_frontend_update');
            }

            return $this->urlGenerator->generate('oro_shopping_list_frontend_view');
        }

        if ($this->authorizationChecker->isGranted('oro_shopping_list_frontend_update', $shoppingList)) {
            return $this->urlGenerator->generate('oro_shopping_list_frontend_update', ['id' => $shoppingList->getId()]);
        }

        return $this->urlGenerator->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);
    }
}
