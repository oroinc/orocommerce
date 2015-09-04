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
    public function confirmAction(AccountUser $accountUser)
    {
        $userManager = $this->get('orob2b_account_user.manager');

        try {
            $userManager->confirmRegistration($accountUser);
            $userManager->updateUser($accountUser);

            $response = [
                'successful' => true,
                'message' => $this->get('translator')->trans('orob2b.account.controller.accountuser.confirmed.message')
            ];
        } catch (\Exception $e) {
            $this->get('logger')->error(
                sprintf(
                    'Confirm account user failed: %s: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            $response = ['successful' => false];
        }

        return new JsonResponse($response);
    }

    /**
     * Send confirmation email
     *
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function sendConfirmationAction(AccountUser $accountUser)
    {
        $userManager = $this->get('orob2b_account_user.manager');

        $result = ['successful' => true];
        try {
            $userManager->sendConfirmationEmail($accountUser);
            $result['message'] = $this->get('translator')
                ->trans('orob2b.account.controller.accountuser.confirmation_sent.message');
        } catch (\Exception $e) {
            $result['successful'] = false;
            $result['message'] = $e->getMessage();
        }

        return new JsonResponse($result);
    }

    /**
     *
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
     *
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

    /**
     * @return JsonResponse
     */
    protected function renderDisableYourselfError()
    {
        $deniedMassage = $this->get('translator')
            ->trans('orob2b.account.controller.accountuser.disable_yourself_denied.message');
        return new JsonResponse([
            'successful' => false,
            'message' => $deniedMassage
        ]);
    }
}
