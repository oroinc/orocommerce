<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

abstract class AbstractAjaxAccountUserController extends Controller
{
    /**
     * @param CustomerUser $accountUser
     * @return JsonResponse
     */
    public function getAccountIdAction(CustomerUser $accountUser)
    {
        return new JsonResponse([
            'accountId' => $accountUser->getAccount() ? $accountUser->getAccount()->getId() : null
        ]);
    }
}
