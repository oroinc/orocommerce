<?php

namespace Oro\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\AccountBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserProfileController extends Controller
{
    /**
     * @Route("/", name="oro_account_frontend_account_user_profile")
     * @Layout
     * @AclAncestor("oro_account_frontend_account_user_view")
     *
     * @return array
     */
    public function profileAction()
    {
        return [
            'data' => [
                'entity' => $this->getUser()
            ]
        ];
    }

    /**
     * Edit account user form
     *
     * @Route("/update", name="oro_account_frontend_account_user_profile_update")
     * @Layout()
     * @AclAncestor("oro_account_frontend_account_user_update")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $accountUser = $this->getUser();
        $form = $this->get('oro_account.provider.frontend_account_user_form')
            ->getProfileForm($accountUser)
            ->getForm();
        $handler = new FrontendAccountUserHandler(
            $form,
            $request,
            $this->get('oro_account_user.manager')
        );
        $resultHandler = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'oro_account_frontend_account_user_profile_update'],
            ['route' => 'oro_account_frontend_account_user_profile'],
            $this->get('translator')->trans('oro.account.controller.accountuser.profile_updated.message'),
            $handler
        );

        if ($resultHandler instanceof Response) {
            return $resultHandler;
        }

        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }
}
