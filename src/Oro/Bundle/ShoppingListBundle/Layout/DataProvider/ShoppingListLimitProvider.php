<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

/**
 * Layout data provider for single Shopping List mode check.
 */
class ShoppingListLimitProvider
{
    /**
     * @var ShoppingListLimitManager
     */
    private $manager;

    /**
     * @param ShoppingListLimitManager $manager
     */
    public function __construct(ShoppingListLimitManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return bool
     */
    public function isOnlyOneEnabled(): bool
    {
        return $this->manager->isOnlyOneEnabled();
    }
}
