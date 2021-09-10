<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds email template params for redirecting to the checkout page
 */
class CustomerUserResetPasswordListener
{
    const CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME = 'checkout_customer_user_reset_password';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getFromRequest(string $name)
    {
        $request = $this->requestStack->getMainRequest();

        return $request->request->get($name);
    }

    public function onCustomerUserEmailSend(CustomerUserEmailSendEvent $event)
    {
        $checkoutId = $this->getFromRequest('_checkout_id');
        if ($this->getFromRequest('_checkout_forgot_password')
            && $checkoutId
            && $event->getEmailTemplate() === Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME
        ) {
            $event->setEmailTemplate(self::CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME);
            $params = $event->getEmailTemplateParams();

            $params['redirectParams'] = json_encode([
                'route' => 'oro_checkout_frontend_checkout',
                'params' => [
                    'id' => $checkoutId
                ]
            ]);
            $event->setEmailTemplateParams($params);
        }
    }
}
