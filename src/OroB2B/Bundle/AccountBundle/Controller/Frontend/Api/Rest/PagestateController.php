<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\NavigationBundle\Controller\Api\PagestateController as BasePagestateController;

/**
 * @NamePrefix("orob2b_api_frontend_")
 */
class PagestateController extends BasePagestateController
{
    /**
     * @return string
     */
    protected function getPageStateClass()
    {
        return $this->getParameter('orob2b_account.entity.page_state.class');
    }
}
