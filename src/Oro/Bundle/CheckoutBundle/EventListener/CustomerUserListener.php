<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
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
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @param RequestStack $requestStack
     * @param LoginManager $loginManager
     * @param CheckoutManager $checkoutManager
     */
    public function __construct(
        RequestStack $requestStack,
        LoginManager $loginManager,
        CheckoutManager $checkoutManager
    ) {
        $this->requestStack = $requestStack;
        $this->loginManager = $loginManager;
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
            if ($checkoutId) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }
}
