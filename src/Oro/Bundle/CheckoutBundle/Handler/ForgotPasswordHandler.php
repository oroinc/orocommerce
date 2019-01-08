<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserPasswordRequestHandler;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserFormProvider;
use Oro\Bundle\UserBundle\Util\ObfuscatedEmailTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handling forgot password request during checkout
 */
class ForgotPasswordHandler
{
    use ObfuscatedEmailTrait;

    /**
     * @var CustomerUserPasswordRequestHandler
     */
    private $passwordRequestHandler;

    /**
     * @var FrontendCustomerUserFormProvider
     */
    private $customerUserFormProvider;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param CustomerUserPasswordRequestHandler $passwordRequestHandler
     * @param FrontendCustomerUserFormProvider $customerUserFormProvider
     * @param Session $session
     */
    public function __construct(
        CustomerUserPasswordRequestHandler $passwordRequestHandler,
        FrontendCustomerUserFormProvider $customerUserFormProvider,
        Session $session
    ) {
        $this->passwordRequestHandler = $passwordRequestHandler;
        $this->customerUserFormProvider = $customerUserFormProvider;
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_POST)
            || $request->get('isForgotPassword') === null
        ) {
            return false;
        }
        $form = $this->customerUserFormProvider->getForgotPasswordForm();
        $email = $this->passwordRequestHandler->process($form, $request);
        if (!$email) {
            return false;
        }

        $request->query->remove('isForgotPassword');
        $request->query->add(['isCheckEmail' => true]);
        $this->session->set(
            'oro_customer_user_reset_email',
            $this->getObfuscatedEmail($email)
        );

        return true;
    }
}
