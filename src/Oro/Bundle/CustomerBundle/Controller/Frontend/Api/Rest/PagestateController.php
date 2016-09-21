<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\NavigationBundle\Controller\Api\PagestateController as BasePagestateController;

/**
 * @NamePrefix("oro_api_frontend_")
 */
class PagestateController extends BasePagestateController
{
    /**
     * @return string
     */
    protected function getPageStateClass()
    {
        return $this->getParameter('oro_account.entity.page_state.class');
    }
}
