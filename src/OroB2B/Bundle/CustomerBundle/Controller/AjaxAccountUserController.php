<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class AjaxAccountUserController extends Controller
{
    /**
     * Send confirmation email
     *
     * @Route(
     *      "/confirmation/send/{id}",
     *      name="orob2b_customer_account_user_send_confirmation",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_customer_account_user_update")
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
                ->trans('orob2b.customer.controller.accountuser.confirmation_sent.message');
        } catch (\Exception $e) {
            $result['successful'] = false;
            $result['message'] = $e->getMessage();
        }

        return new JsonResponse($result);
    }

    /**
     * @Route(
     *      "/enable/{id}",
     *      name="orob2b_customer_account_user_enable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_customer_account_user_update")
     *
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function enableAction(AccountUser $accountUser)
    {
        $enableMessage = $this->get('translator')->trans('orob2b.customer.controller.accountuser.enabled.message');

        return $this->enableTrigger($accountUser, true, $enableMessage);
    }

    /**
     * @Route(
     *      "/disable/{id}",
     *      name="orob2b_customer_account_user_disable",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("orob2b_customer_account_user_update")
     *
     * @param AccountUser $accountUser
     * @return JsonResponse
     */
    public function disableAction(AccountUser $accountUser)
    {
        $disableMessage = $this->get('translator')->trans('orob2b.customer.controller.accountuser.disabled.message');

        return $this->enableTrigger($accountUser, false, $disableMessage);
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
