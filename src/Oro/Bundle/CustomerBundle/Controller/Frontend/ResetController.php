<?php

namespace Oro\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserManager;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserPasswordRequestHandler;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserPasswordResetHandler;

class ResetController extends Controller
{
    const SESSION_EMAIL = 'oro_account_user_reset_email';

    /**
     * @Layout()
     * @Route("/reset-request", name="oro_account_frontend_account_user_reset_request")
     * @Method({"GET", "POST"})
     */
    public function requestAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_account_frontend_account_user_profile'));
        }

        /** @var AccountUserPasswordRequestHandler $handler */
        $handler = $this->get('oro_account.account_user.password_request.handler');
        $form = $this->get('oro_account.provider.frontend_account_user_form')
            ->getForgotPasswordForm()
            ->getForm();

        $request = $this->get('request_stack')->getCurrentRequest();
        $user = $handler->process($form, $request);
        if ($user) {
            $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
            return $this->redirect($this->generateUrl('oro_account_frontend_account_user_reset_check_email'));
        }

        return [];
    }

    /**
     * Tell the user to check his email
     *
     * @Layout()
     * @Route("/check-email", name="oro_account_frontend_account_user_reset_check_email")
     * @Method({"GET"})
     */
    public function checkEmailAction()
    {
        $session = $this->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return $this->redirect($this->generateUrl('oro_account_frontend_account_user_reset_request'));
        }

        return [
            'data' => [
                'email' => $email
            ]
        ];
    }

    /**
     * Reset user password
     *
     * @Layout
     * @Route("/reset", name="oro_account_frontend_account_user_password_reset")
     * @Method({"GET", "POST"})
     * @return array|RedirectResponse
     */
    public function resetAction()
    {
        $token = $this->getRequest()->get('token');
        $username = $this->getRequest()->get('username');
        /** @var AccountUser $user */
        $user = $this->getUserManager()->findUserByUsernameOrEmail($username);
        if (!$token || null === $user || $user->getConfirmationToken() !== $token) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans(
                    'oro.account.controller.accountuser.token_not_found.message',
                    ['%token%' => $token]
                )
            );
        }

        $session = $this->get('session');
        $ttl = $this->container->getParameter('oro_user.reset.ttl');
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            $session->getFlashBag()->add(
                'warn',
                'oro.account.accountuser.profile.password.reset.ttl_expired.message'
            );

            return $this->redirect($this->generateUrl('oro_account_frontend_account_user_reset_request'));
        }

        /** @var AccountUserPasswordResetHandler $handler */
        $handler = $this->get('oro_account.account_user.password_reset.handler');
        $form = $this->get('oro_account.provider.frontend_account_user_form')
            ->getResetPasswordForm($user)
            ->getForm();

        if ($handler->process($form, $this->getRequest())) {
            // force user logout
            $session->invalidate();
            $this->get('security.context')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                'oro.account.accountuser.profile.password_reset.message'
            );

            return $this->redirect($this->generateUrl('oro_account_account_user_security_login'));
        }

        return [
            'data' => [
                'user' => $user
            ]
        ];
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     * The default implementation only keeps the part following @ in the address.
     *
     * @param AccountUser $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(AccountUser $user)
    {
        $email = $user->getEmail();

        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    /**
     * @return AccountUserManager
     */
    protected function getUserManager()
    {
        return $this->get('oro_account_user.manager');
    }
}
