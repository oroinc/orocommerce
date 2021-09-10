<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Provides the email address to which a reset password link was sent
 */
class CheckoutForgotPasswordCheckEmailProvider
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        RequestStack $requestStack,
        Session $session
    ) {
        $this->session = $session;
        $this->requestStack = $requestStack;
    }

    /**
     * @return null|string
     */
    public function getCheckEmail()
    {
        $email = $this->session->get('oro_customer_user_reset_email');
        $this->session->remove('oro_customer_user_reset_email');
        $request = $this->requestStack->getMainRequest();
        if ($request->get('isCheckEmail') !== null && empty($email)) {
            // the user does not come from the forgot password page
            $request->query->remove('isCheckEmail');
            $request->query->add(['isForgotPassword' => true]);
        }

        return $email;
    }
}
