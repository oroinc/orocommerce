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
     * @Route("/confirm/{id}", name="orob2b_customer_account_user_confirm", requirements={"id"="\d+"})
     * @AclAncestor("orob2b_customer_account_user_update")
     *
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
                'message' => $this->get('translator')->trans('orob2b.customer.controller.accountuser.confirmed.message')
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
}
