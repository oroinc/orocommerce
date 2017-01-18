<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

abstract class AbstractAjaxCustomerUserController extends Controller
{
    /**
     * @param CustomerUser $customerUser
     * @return JsonResponse
     */
    public function getCustomerIdAction(CustomerUser $customerUser)
    {
        return new JsonResponse([
            'customerId' => $customerUser->getCustomer() ? $customerUser->getCustomer()->getId() : null
        ]);
    }
}
