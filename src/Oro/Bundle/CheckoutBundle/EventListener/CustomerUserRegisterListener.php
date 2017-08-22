<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CustomerBundle\Event\CustomerUserRegisterEvent;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;

use Symfony\Component\HttpFoundation\Request;

class CustomerUserRegisterListener
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoginManager
     */
    private $loginManager;

    /**
     * @param Request $request
     * @param LoginManager $loginManager
     */
    public function __construct(Request $request, LoginManager $loginManager)
    {
        $this->loginManager = $loginManager;
        $this->request = $request;
    }

    /**
     * @param CustomerUserRegisterEvent $event
     */
    public function onCustomerUserRegister(CustomerUserRegisterEvent $event)
    {
        if ($this->request->request->get('_checkout_registration')) {
            $this->loginManager->logInUser('frontend_secure', $event->getCustomerUser());
        }
    }
}
