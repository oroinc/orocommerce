<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

abstract class AbstractAjaxAccountUserController extends Controller
{
    /**
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function enableAction(AccountUser $accountUser)
    {
        $enableMessage = $this->get('translator')->trans('orob2b.account.controller.accountuser.enabled.message');

        return $this->enableTrigger($accountUser, true, $enableMessage);
    }

    /**
     *
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function disableAction(AccountUser $accountUser)
    {
        $disableMessage = $this->get('translator')->trans('orob2b.account.controller.accountuser.disabled.message');

        return $this->enableTrigger($accountUser, false, $disableMessage);
    }

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

    /**
     * @param AccountUser $accountUser
     * @param boolean $enabled
     * @param string $successMessage
     * @return JsonResponse
     */
    protected function enableTrigger(AccountUser $accountUser, $enabled, $successMessage)
    {
        $userManager = $this->get('orob2b_account_user.manager');
        $accountUser->setEnabled($enabled);
        $userManager->updateUser($accountUser);

        return new JsonResponse([
            'successful' => true,
            'message' => $successMessage
        ]);
    }
}
