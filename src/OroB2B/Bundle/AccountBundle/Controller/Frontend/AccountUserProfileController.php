<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserProfileController extends Controller
{
    /**
     * Create account user form
     *
     * @Route("/register", name="orob2b_account_frontend_account_user_register")
     * @Layout()
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_profile'));
        }

        if (!$this->isRegistrationAllowed()) {
            return $this->redirect($this->generateUrl('orob2b_account_account_user_security_login'));
        }

        return $this->handleForm($request);
    }

    /**
     * @return bool
     */
    protected function isRegistrationAllowed()
    {
        return (bool) $this->get('oro_config.manager')->get('oro_b2b_account.registration_allowed');
    }

    /**
     * @param Request $request
     * @return LayoutContext|RedirectResponse
     */
    protected function handleForm(Request $request)
    {
        $form = $this->get('orob2b_account.provider.frontend_account_user_registration_form')->getForm();
        $userManager = $this->get('orob2b_account_user.manager');
        $handler = new FrontendAccountUserHandler($form, $request, $userManager);

        if ($userManager->isConfirmationRequired()) {
            $registrationMessage = 'orob2b.account.controller.accountuser.registered_with_confirmation.message';
        } else {
            $registrationMessage = 'orob2b.account.controller.accountuser.registered.message';
        }
        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            ['route' => 'orob2b_account_account_user_security_login'],
            ['route' => 'orob2b_account_account_user_security_login'],
            $this->get('translator')->trans($registrationMessage),
            $handler
        );
        if ($response instanceof Response) {
            return $response;
        }
        return [];
    }

    /**
     * @Route("/confirm-email", name="orob2b_account_frontend_account_user_confirmation")
     * @param Request $request
     * @return RedirectResponse
     */
    public function confirmEmailAction(Request $request)
    {
        $userManager = $this->get('orob2b_account_user.manager');
        /** @var AccountUser $accountUser */
        $accountUser = $userManager->findUserByUsernameOrEmail($request->get('username'));
        $token = $request->get('token');
        if ($accountUser === null || empty($token) || $accountUser->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')
                    ->trans('orob2b.account.controller.accountuser.confirmation_error.message')
            );
        }

        $messageType = 'warn';
        $message = 'orob2b.account.controller.accountuser.already_confirmed.message';
        if (!$accountUser->isConfirmed()) {
            $userManager->confirmRegistration($accountUser);
            $userManager->updateUser($accountUser);
            $messageType = 'success';
            $message = 'orob2b.account.controller.accountuser.confirmed.message';
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);
        return $this->redirect($this->generateUrl('orob2b_account_account_user_security_login'));
    }

    /**
     * @Route("/profile", name="orob2b_account_frontend_account_user_profile")
     * @Layout
     * @AclAncestor("orob2b_account_frontend_account_user_view")
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
     * @Route("/profile/update", name="orob2b_account_frontend_account_user_profile_update")
     * @Layout()
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $accountUser = $this->getUser();
        $form = $this->get('orob2b_account.provider.frontend_account_user_profile_form')->getForm($accountUser);
        $handler = new FrontendAccountUserHandler(
            $form,
            $request,
            $this->get('orob2b_account_user.manager')
        );
        $resultHandler = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'orob2b_account_frontend_account_user_profile_update'],
            ['route' => 'orob2b_account_frontend_account_user_profile'],
            $this->get('translator')->trans('orob2b.account.controller.accountuser.profile_updated.message'),
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
