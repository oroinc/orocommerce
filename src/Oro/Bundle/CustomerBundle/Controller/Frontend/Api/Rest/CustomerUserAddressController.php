<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\CustomerBundle\Controller\Api\Rest\CustomerUserAddressController as BaseCustomerUserAddressController;

/**
 * @NamePrefix("oro_api_customer_frontend_")
 */
class CustomerUserAddressController extends BaseCustomerUserAddressController
{
}
