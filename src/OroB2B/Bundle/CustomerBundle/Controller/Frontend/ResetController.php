<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserPasswordRequestType;
use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserPasswordRequestHandler;
use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserPasswordResetHandler;

class ResetController extends Controller
{
    const SESSION_EMAIL = 'orob2b_account_user_reset_email';

    /**
     * @Route("/reset-request", name="orob2b_customer_frontend_account_user_reset_request")
     * @Method({"GET", "POST"})
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend/Password:request.html.twig")
     */
    public function requestAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('orob2b_customer_frontend_account_user_profile'));
        }

        /** @var AccountUserPasswordRequestHandler $handler */
        $handler = $this->get('orob2b_customer.account_user.password_request.handler');
        $form = $this->createForm(AccountUserPasswordRequestType::NAME);

        if ($user = $handler->process($form, $this->getRequest())) {
            $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
            return $this->redirect($this->generateUrl('orob2b_customer_frontend_account_user_reset_check_email'));
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * Tell the user to check his email
     *
     * @Route("/check-email", name="orob2b_customer_frontend_account_user_reset_check_email")
     * @Method({"GET"})
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend/Password:checkEmail.html.twig")
     */
    public function checkEmailAction()
    {
        $session = $this->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return $this->redirect($this->generateUrl('orob2b_customer_frontend_account_user_reset_request'));
        }

        return [
            'email' => $email
        ];
    }

    /**
     * Reset user password
     *
     * @Route("/reset", name="orob2b_customer_frontend_account_user_password_reset")
     * @Method({"GET", "POST"})
     * @Template("OroB2BCustomerBundle:AccountUser/Frontend/Password:reset.html.twig")
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
                    'orob2b.customer.controller.accountuser.token_not_found.message',
                    ['%token%' => $token]
                )
            );
        }

        $session = $this->get('session');
        $ttl = $this->container->getParameter('oro_user.reset.ttl');
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            $session->getFlashBag()->add(
                'warn',
                'orob2b.customer.accountuser.profile.password.reset.ttl_expired.message'
            );

            return $this->redirect($this->generateUrl('orob2b_customer_frontend_account_user_reset_request'));
        }

        /** @var AccountUserPasswordResetHandler $handler */
        $handler = $this->get('orob2b_customer.account_user.password_reset.handler');
        $form = $this->createForm('orob2b_customer_account_user_password_reset', $user);

        if ($handler->process($form, $this->getRequest())) {
            // force user logout
            $session->invalidate();
            $this->get('security.context')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                'orob2b.customer.accountuser.profile.password_reset.message'
            );

            return $this->redirect($this->generateUrl('orob2b_customer_account_user_security_login'));
        }

        return [
            'token' => $user->getConfirmationToken(),
            'username' => $user->getUsername(),
            'form' => $form->createView()
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
