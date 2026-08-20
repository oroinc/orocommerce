<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CustomerBundle\Async\PasswordResetRequestContext;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Event\PasswordResetRequestContextCollectEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds email template params for redirecting to the checkout page
 */
class CustomerUserResetPasswordListener
{
    const CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME = 'checkout_customer_user_reset_password';

    private const CHECKOUT_FORGOT_PASSWORD_PARAMETER = '_checkout_forgot_password';
    private const CHECKOUT_ID_PARAMETER = '_checkout_id';

    /**
     * @var RequestStack
     */
    private $requestStack;

    private ?PasswordResetRequestContext $passwordResetRequestContext = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function setPasswordResetRequestContext(PasswordResetRequestContext $passwordResetRequestContext): void
    {
        $this->passwordResetRequestContext = $passwordResetRequestContext;
    }

    public function onPasswordResetRequestContextCollect(PasswordResetRequestContextCollectEvent $event): void
    {
        $request = $event->getRequest();
        foreach ([self::CHECKOUT_FORGOT_PASSWORD_PARAMETER, self::CHECKOUT_ID_PARAMETER] as $name) {
            $value = $request->request->get($name);
            if (null !== $value) {
                $event->setRequestParameter($name, $value);
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getFromRequest(string $name)
    {
        return $this->requestStack->getMainRequest()?->request->get($name)
            ?? $this->passwordResetRequestContext?->getRequestParameter($name);
    }

    public function onCustomerUserEmailSend(CustomerUserEmailSendEvent $event)
    {
        $checkoutId = $this->getFromRequest(self::CHECKOUT_ID_PARAMETER);
        if ($this->getFromRequest(self::CHECKOUT_FORGOT_PASSWORD_PARAMETER)
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
