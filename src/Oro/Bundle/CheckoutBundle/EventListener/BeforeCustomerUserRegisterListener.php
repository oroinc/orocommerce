<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CustomerBundle\Event\BeforeCustomerUserRegisterEvent;

use Symfony\Component\HttpFoundation\Request;

class BeforeCustomerUserRegisterListener
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param BeforeCustomerUserRegisterEvent $event
     */
    public function beforeCustomerUserRegister(BeforeCustomerUserRegisterEvent $event)
    {
        $checkoutId = $this->request->request->get('_checkout_id');
        if ($checkoutId && $this->request->request->get('_checkout_registration')) {
            $event->setRedirect(
                [
                    'route' => 'oro_checkout_frontend_checkout',
                    'parameters' => ['id' => $checkoutId, 'transition' => 'back_to_billing_address']
                ]
            );
        }
    }
}
