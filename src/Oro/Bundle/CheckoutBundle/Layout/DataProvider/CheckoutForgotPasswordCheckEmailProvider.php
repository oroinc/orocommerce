<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the email address to which a reset password link was sent
 */
class CheckoutForgotPasswordCheckEmailProvider
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @return null|string
     */
    public function getCheckEmail()
    {
        $session = $this->requestStack->getSession();
        $email = $session->get('oro_customer_user_reset_email');
        $session->remove('oro_customer_user_reset_email');
        $request = $this->requestStack->getMainRequest();
        if ($request->get('isCheckEmail') !== null && empty($email)) {
            // the user does not come from the forgot password page
            $request->query->remove('isCheckEmail');
            $request->query->add(['isForgotPassword' => true]);
        }

        return $email;
    }
}
