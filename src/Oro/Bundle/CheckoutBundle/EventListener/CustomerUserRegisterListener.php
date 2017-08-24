<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @param Request $request
     * @param LoginManager $loginManager
     * @param ConfigManager $configManager
     * @param CheckoutManager $checkoutManager
     */
    public function __construct(
        Request $request,
        LoginManager $loginManager,
        ConfigManager $configManager,
        CheckoutManager $checkoutManager
    ) {
        $this->loginManager = $loginManager;
        $this->request = $request;
        $this->configManager = $configManager;
        $this->checkoutManager = $checkoutManager;
    }

    /**
     * @param CustomerUserRegisterEvent $event
     */
    public function onCustomerUserRegister(CustomerUserRegisterEvent $event)
    {
        $customerUser = $event->getCustomerUser();

        if ($this->request->request->get('_checkout_registration')) {
            if ($customerUser->isConfirmed()) {
                $this->loginManager->logInUser('frontend_secure', $customerUser);
                return;
            }
            $checkoutId = $this->request->request->get('_checkout_id');

            if ($checkoutId && $this->configManager->get('oro_checkout.allow_checkout_without_email_confirmation')) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }
}
