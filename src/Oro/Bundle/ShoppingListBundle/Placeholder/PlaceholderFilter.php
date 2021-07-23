<?php

namespace Oro\Bundle\ShoppingListBundle\Placeholder;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
