<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\LayoutBundle\Annotation\Layout;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="oro_customer_account_user_security_login")
     * @Layout()
     */
    public function loginAction()
    {
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('oro_customer_frontend_account_user_profile'));
        }

        $registrationAllowed = (bool) $this->get('oro_config.manager')->get('oro_customer.registration_allowed');

        return [
            'data' => [
                'registrationAllowed' => $registrationAllowed
            ]
        ];
    }

    /**
     * @Route("/login-check", name="oro_customer_account_user_security_check")
     */
    public function checkAction()
    {
        throw new \RuntimeException(
            'You must configure the check path to be handled by the firewall ' .
            'using form_login in your security firewall configuration.'
        );
    }

    /**
     * @Route("/logout", name="oro_customer_account_user_security_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
}
