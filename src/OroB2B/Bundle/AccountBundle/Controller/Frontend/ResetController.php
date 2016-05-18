<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserManager;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserPasswordRequestHandler;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserPasswordResetHandler;

class ResetController extends Controller
{
    const SESSION_EMAIL = 'orob2b_account_user_reset_email';

    /**
     * @Layout(action="orob2b_account_frontend_account_user_security")
     * @Route("/reset-request", name="orob2b_account_frontend_account_user_reset_request")
     * @Method({"GET", "POST"})
     */
    public function requestAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_profile'));
        }

        /** @var AccountUserPasswordRequestHandler $handler */
        $handler = $this->get('orob2b_account.account_user.password_request.handler');
        $form = $this->get('orob2b_account.provider.frontend_account_user_forgot_password_form')->getForm();

        $request = $this->get('request_stack')->getCurrentRequest();
        $user = $handler->process($form, $request);
        if ($user) {
            $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_reset_check_email'));
        }

        return [];
    }

    /**
     * Tell the user to check his email
     *
     * @Layout(action="orob2b_account_frontend_account_user_security")
     * @Route("/check-email", name="orob2b_account_frontend_account_user_reset_check_email")
     * @Method({"GET"})
     */
    public function checkEmailAction()
    {
        $session = $this->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_reset_request'));
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
     * @Layout(vars={"user"})
     * @Route("/reset", name="orob2b_account_frontend_account_user_password_reset")
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
                    'orob2b.account.controller.accountuser.token_not_found.message',
                    ['%token%' => $token]
                )
            );
        }

        $session = $this->get('session');
        $ttl = $this->container->getParameter('oro_user.reset.ttl');
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            $session->getFlashBag()->add(
                'warn',
                'orob2b.account.accountuser.profile.password.reset.ttl_expired.message'
            );

            return $this->redirect($this->generateUrl('orob2b_account_frontend_account_user_reset_request'));
        }

        /** @var AccountUserPasswordResetHandler $handler */
        $handler = $this->get('orob2b_account.account_user.password_reset.handler');
        $form = $this->get('orob2b_account.provider.frontend_account_user_reset_password_form')->getForm($user);

        if ($handler->process($form, $this->getRequest())) {
            // force user logout
            $session->invalidate();
            $this->get('security.context')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                'orob2b.account.accountuser.profile.password_reset.message'
            );

            return $this->redirect($this->generateUrl('orob2b_account_account_user_security_login'));
        }

        return [
            'user' => $user
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
        return $this->get('orob2b_account_user.manager');
    }
}
