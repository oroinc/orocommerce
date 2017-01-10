<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Handler\FrontendCustomerUserHandler;

class CustomerUserRegisterController extends Controller
{
    /**
     * Create customer user form
     *
     * @Route("/registration", name="oro_customer_frontend_customer_user_register")
     * @Layout()
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function registerAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_customer_frontend_customer_user_profile'));
        }

        if (!$this->isRegistrationAllowed()) {
            return $this->redirect($this->generateUrl('oro_customer_customer_user_security_login'));
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
        $form = $this->get('oro_customer.provider.frontend_customer_user_registration_form')
            ->getRegisterForm();
        $userManager = $this->get('oro_customer_user.manager');
        $handler = new FrontendCustomerUserHandler($form, $request, $userManager);

        if ($userManager->isConfirmationRequired()) {
            $registrationMessage = 'oro.customer.controller.customeruser.registered_with_confirmation.message';
        } else {
            $registrationMessage = 'oro.customer.controller.customeruser.registered.message';
        }
        $response = $this->get('oro_form.model.update_handler')->handleUpdate(
            $form->getData(),
            $form,
            ['route' => 'oro_customer_customer_user_security_login'],
            ['route' => 'oro_customer_customer_user_security_login'],
            $this->get('translator')->trans($registrationMessage),
            $handler
        );
        if ($response instanceof Response) {
            return $response;
        }

        return [];
    }

    /**
     * @Route("/confirm-email", name="oro_customer_frontend_customer_user_confirmation")
     * @param Request $request
     * @return RedirectResponse
     */
    public function confirmEmailAction(Request $request)
    {
        $userManager = $this->get('oro_customer_user.manager');
        /** @var CustomerUser $customerUser */
        $customerUser = $userManager->findUserByUsernameOrEmail($request->get('username'));
        $token = $request->get('token');
        if ($customerUser === null || empty($token) || $customerUser->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')
                    ->trans('oro.customer.controller.customeruser.confirmation_error.message')
            );
        }

        $messageType = 'warn';
        $message = 'oro.customer.controller.customeruser.already_confirmed.message';
        if (!$customerUser->isConfirmed()) {
            $userManager->confirmRegistration($customerUser);
            $userManager->updateUser($customerUser);
            $messageType = 'success';
            $message = 'oro.customer.controller.customeruser.confirmed.message';
        }

        $this->get('session')->getFlashBag()->add($messageType, $message);

        return $this->redirect($this->generateUrl('oro_customer_customer_user_security_login'));
    }
}
