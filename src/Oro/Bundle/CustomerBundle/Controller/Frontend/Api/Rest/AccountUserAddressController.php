<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\CustomerBundle\Controller\Api\Rest\AccountUserAddressController as BaseAccountUserAddressController;

/**
 * @NamePrefix("oro_api_account_frontend_")
 */
class AccountUserAddressController extends BaseAccountUserAddressController
{
}
