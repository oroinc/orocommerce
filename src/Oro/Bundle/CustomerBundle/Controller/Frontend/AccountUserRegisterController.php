<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserRegisterController extends Controller
{
    /**
     * Create account user form
     *
     * @Route("/register", name="oro_customer_frontend_account_user_register")
     * @Layout()
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_customer_frontend_account_user_profile'));
        }

        if (!$this->isRegistrationAllowed()) {
            return $this->redirect($this->generateUrl('oro_customer_account_user_security_login'));
        }

        return $this->handleForm($request);
    }

    /**
     * @return bool
     */
    protected function isRegistrationAllowed()
    {
        return (bool) $this->get('oro_config.manager')->get('oro_customer.registration_allowed');
    }

    /**
     * @param Request $request
     * @return LayoutContext|RedirectResponse
     */
    protected function handleForm(Request $request)
    {
        $form = $this->get('oro_customer.provider.frontend_account_user_registration_form')
            ->getRegisterForm()
            ->getForm();
        $userManager = $this->get('oro_account_user.manager');
        $handler = new FrontendAccountUserHandler($form, $request, $userManager);

        if ($userManager->isConfirmationRequired()) {
            $registrationMessage = 'oro.customer.controller.accountuser.registered_with_confirmation.message';
        } else {
            $registrationMessage = 'oro.customer.controller.accountuser.registered.message';
        }
        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            ['route' => 'oro_customer_account_user_security_login'],
            ['route' => 'oro_customer_account_user_security_login'],
            $this->get('translator')->trans($registrationMessage),
            $handler
        );
        if ($response instanceof Response) {
            return $response;
        }

        return [];
    }

    /**
     * @Route("/confirm-email", name="oro_customer_frontend_account_user_confirmation")
     * @param Request $request
     * @return RedirectResponse
     */
    public function confirmEmailAction(Request $request)
    {
        $userManager = $this->get('oro_account_user.manager');
        /** @var AccountUser $accountUser */
        $accountUser = $userManager->findUserByUsernameOrEmail($request->get('username'));
        $token = $request->get('token');
        if ($accountUser === null || empty($token) || $accountUser->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')
                    ->trans('oro.customer.controller.accountuser.confirmation_error.message')
            );
        }

        $messageType = 'warn';
        $message = 'oro.customer.controller.accountuser.already_confirmed.message';
        if (!$accountUser->isConfirmed()) {
            $userManager->confirmRegistration($accountUser);
            $userManager->updateUser($accountUser);
            $messageType = 'success';
            $message = 'oro.customer.controller.accountuser.confirmed.message';
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);

        return $this->redirect($this->generateUrl('oro_customer_account_user_security_login'));
    }
}
