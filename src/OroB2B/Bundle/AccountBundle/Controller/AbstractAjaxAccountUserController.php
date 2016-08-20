<?php

namespace Oro\Bundle\AccountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractAjaxAccountUserController extends Controller
{
    /**
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function getAccountIdAction(AccountUser $accountUser)
    {
        return new JsonResponse([
            'accountId' => $accountUser->getAccount() ? $accountUser->getAccount()->getId() : null
        ]);
    }
}
