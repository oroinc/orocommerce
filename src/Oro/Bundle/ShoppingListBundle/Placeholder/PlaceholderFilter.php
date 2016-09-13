<?php

namespace Oro\Bundle\ShoppingListBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class PlaceholderFilter
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @return bool
     */
    public function userCanCreateLineItem()
    {
        return $this->securityFacade->isGranted('oro_shopping_list_line_item_frontend_add');
    }
}
