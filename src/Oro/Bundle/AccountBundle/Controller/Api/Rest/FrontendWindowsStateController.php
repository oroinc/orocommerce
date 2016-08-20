<?php

namespace Oro\Bundle\AccountBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Oro\Bundle\WindowsBundle\Controller\Api\WindowsStateController;

/**
 * @RouteResource("windows")
 * @NamePrefix("orob2b_api_account_")
 */
class FrontendWindowsStateController extends WindowsStateController
{
    /**
     * @retrun WindowsStateManager
     */
    protected function getWindowsStatesManager()
    {
        return $this->get('orob2b_account.manager.windows_state');
    }
}
