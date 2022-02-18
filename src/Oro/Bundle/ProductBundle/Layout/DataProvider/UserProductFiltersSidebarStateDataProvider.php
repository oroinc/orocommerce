<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Manager\UserProductFiltersSidebarStateManager;

/**
 * Provides a set of methods to check current state of product filters sidebar.
 */
class UserProductFiltersSidebarStateDataProvider
{
    private UserProductFiltersSidebarStateManager $userProductFiltersSidebarStateManager;

    public function __construct(UserProductFiltersSidebarStateManager $userProductFiltersSidebarStateManager)
    {
        $this->userProductFiltersSidebarStateManager = $userProductFiltersSidebarStateManager;
    }

    public function isProductFiltersSidebarExpanded(): bool
    {
        return $this->userProductFiltersSidebarStateManager->isProductFiltersSidebarExpanded();
    }

    public function isProductFiltersSidebarCollapsed(): bool
    {
        return !$this->userProductFiltersSidebarStateManager->isProductFiltersSidebarExpanded();
    }
}
