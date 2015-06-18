<?php

namespace OroB2B\Bundle\CustomerBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager;
use OroB2B\Bundle\CustomerBundle\Mailer\Processor;

class ResetController extends Controller
{
    const SESSION_EMAIL = 'orob2b_account_user_reset_email';

    /**
     * @Route("/reset-request", name="orob2b_customer_account_user_reset_request")
     * @Method({"GET"})
     * @Template
     */
    public function requestAction()
    {
        return [];
    }

    /**
     * Request reset user password
     *
     * @Route("/send-email", name="orob2b_customer_account_user_reset_send_email")
     * @Method({"POST"})
     */
    public function sendEmailAction()
    {
        $userManager = $this->getUserManager();
        $email = $this->getRequest()->request->get('email');
        /** @var AccountUser $user */
        $user = $userManager->findUserByUsernameOrEmail($email);

        if (null === $user) {
            return $this->render(
                'OroB2BCustomerBundle:AccountUser/Frontend/Password:request.html.twig',
                ['invalid_email' => $email]
            );
        }

        $ttl = $this->container->getParameter('orob2b_customer.account_user.reset.ttl');
        if ($user->isPasswordRequestNonExpired($ttl)) {
            $this->get('session')->getFlashBag()->add(
                'warn',
                'orob2b.customer.accountuser.password.reset.ttl_already_requested.message'
            );

            return $this->redirect($this->generateUrl('orob2b_customer_account_user_reset_request'));
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        try {
            $this->getEmailProcessor()->sendResetPasswordEmail($user);
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('warn', 'oro.email.handler.unable_to_send_email');

            return $this->redirect($this->generateUrl('orob2b_customer_account_user_reset_request'));
        }
        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $userManager->updateUser($user);

        return $this->redirect($this->generateUrl('orob2b_customer_account_user_reset_check_email'));
    }

    /**
     * Tell the user to check his email
     *
     * @Route("/check-email", name="orob2b_customer_account_user_reset_check_email")
     * @Method({"GET"})
     * @Template
     */
    public function checkEmailAction()
    {
        $session = $this->get('session');
        $email = $session->get(static::SESSION_EMAIL);

        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return $this->redirect($this->generateUrl('orob2b_customer_account_user_reset_request'));
        }

        return [
            'email' => $email
        ];
    }

    /**
     * Reset user password
     *
     * @Route("/reset/{token}", name="orob2b_customer_account_user_reset_reset", requirements={"token"="\w+"})
     * @Method({"GET", "POST"})
     * @Template
     */
    public function resetAction($token)
    {
        /** @var AccountUser $user */
        $user = $this->getUserManager()->findUserByConfirmationToken($token);
        $session = $this->get('session');

        if (null === $user) {
            throw $this->createNotFoundException(
                sprintf('The user with "confirmation token" does not exist for value "%s"', $token)
            );
        }

        $ttl = $this->container->getParameter('orob2b_customer.account_user.reset.ttl');
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            $session->getFlashBag()->add(
                'warn',
                'orob2b.customer.accountuser.password.reset.ttl_already_requested.message'
            );

            return $this->redirect($this->generateUrl('orob2b_customer_account_user_reset_request'));
        }

        if ($this->get('oro_user.form.handler.reset')->process($user)) {
            // force user logout
            $session->invalidate();
            $this->get('security.context')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                'orob2b.customer.accountuser.password.reseted.message'
            );

            return $this->redirect($this->generateUrl('oro_user_security_login'));
        }

        return [
            'token' => $token,
            'form' => $this->get('oro_user.form.reset')->createView()
        ];
    }

    /**
     * Sets user password
     *
     * @AclAncestor("password_management")
     * @Method({"GET", "POST"})
     * @Route("/set-password/{id}", name="orob2b_customer_account_user_reset_set_password", requirements={"id"="\d+"})
     * @Template("OroUserBundle:Reset:update.html.twig")
     */
    public function setPasswordAction(AccountUser $entity)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_user_reset_set_password',
            $this->getRequest(),
            ['id' => $entity->getId()]
        );

        $responseData = [
            'entity' => $entity,
            'saved' => false
        ];

        if ($this->get('oro_user.form.handler.set_password')->process($entity)) {
            $responseData['entity'] = $entity;
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->get('oro_user.form.type.set_password.form')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
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
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }

    /**
     * @return AccountUserManager
     */
    protected function getUserManager()
    {
        return $this->get('orob2b_account_user.manager');
    }

    /**
     * @return Processor
     */
    protected function getEmailProcessor()
    {
        return $this->get('orob2b_customer.mailer.processor');
    }
}
