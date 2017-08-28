<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class CustomerUserListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

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
     * @param RequestStack $requestStack
     * @param LoginManager $loginManager
     * @param ConfigManager $configManager
     * @param CheckoutManager $checkoutManager
     */
    public function __construct(
        RequestStack $requestStack,
        LoginManager $loginManager,
        ConfigManager $configManager,
        CheckoutManager $checkoutManager
    ) {
        $this->requestStack = $requestStack;
        $this->loginManager = $loginManager;
        $this->configManager = $configManager;
        $this->checkoutManager = $checkoutManager;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function afterFlush(AfterFormProcessEvent $event)
    {
        $customerUser = $event->getData();

        $request = $this->requestStack->getMasterRequest();
        if ($request->request->get('_checkout_registration')) {
            if ($customerUser->isConfirmed()) {
                $this->loginManager->logInUser('frontend_secure', $customerUser);

                return;
            }

            $checkoutId = $request->request->get('_checkout_id');
            if ($checkoutId && $this->configManager->get('oro_checkout.allow_checkout_without_email_confirmation')) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }
}
