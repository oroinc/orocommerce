<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Executes autologin depending on CustomerUser status and adds email template params for autologin link
 */
class CustomerUserListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LoginManager
     */
    private $loginManager;

    /**
     * @var string
     */
    private $firewallName;

    /**
     * @param RequestStack $requestStack
     * @param CheckoutManager $checkoutManager
     * @param ConfigManager $configManager
     * @param LoginManager $loginManager
     * @param string $firewallName
     */
    public function __construct(
        RequestStack $requestStack,
        CheckoutManager $checkoutManager,
        ConfigManager $configManager,
        LoginManager $loginManager,
        $firewallName
    ) {
        $this->requestStack = $requestStack;
        $this->checkoutManager = $checkoutManager;
        $this->configManager = $configManager;
        $this->loginManager = $loginManager;
        $this->firewallName = $firewallName;
    }

    public function afterFlush(AfterFormProcessEvent $event)
    {
        $customerUser = $event->getData();

        if ($this->getFromRequest('_checkout_registration')) {
            if ($customerUser->isConfirmed()) {
                $this->loginManager->logInUser($this->firewallName, $customerUser);

                return;
            }

            $checkoutId = $this->getFromRequest('_checkout_id');
            if ($checkoutId && $this->isCheckoutOfCurrentVisitor($checkoutId)) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }

    /**
     * Only a checkout of the visitor who is registering may be claimed by the new account.
     */
    private function isCheckoutOfCurrentVisitor($checkoutId): bool
    {
        $checkout = $this->checkoutManager->getCheckoutById($checkoutId);
        $visitorSessionId = $checkout?->getVisitor()?->getSessionId();

        return $visitorSessionId && $visitorSessionId === $this->getVisitorSessionId();
    }

    private function getVisitorSessionId(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        return $this->getVisitorSessionIdFromCookie($request)
            ?? $this->getVisitorSessionIdFromAttributes($request);
    }

    private function getVisitorSessionIdFromCookie(Request $request): ?string
    {
        $cookieValue = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$cookieValue) {
            return null;
        }

        try {
            $sessionId = json_decode(base64_decode($cookieValue), null, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (\is_array($sessionId) && isset($sessionId[1])) {
            // BC compatibility: get sessionId from old format of the cookie value
            $sessionId = $sessionId[1];
        }

        return \is_string($sessionId) && $sessionId ? $sessionId : null;
    }

    private function getVisitorSessionIdFromAttributes(Request $request): ?string
    {
        $sessionId = $request->attributes->get('visitor_session_id');

        return \is_string($sessionId) && $sessionId ? $sessionId : null;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getFromRequest(string $name)
    {
        return $this->requestStack->getMainRequest()?->request->get($name);
    }

    public function onCustomerUserEmailSend(CustomerUserEmailSendEvent $event)
    {
        $checkoutId = $this->getFromRequest('_checkout_id');
        if (
            $this->getFromRequest('_checkout_registration') && $checkoutId &&
            !$this->configManager->get('oro_checkout.allow_checkout_without_email_confirmation') &&
            $event->getEmailTemplate() === Processor::CONFIRMATION_EMAIL_TEMPLATE_NAME
        ) {
            $event->setEmailTemplate('checkout_registration_confirmation');
            $params = $event->getEmailTemplateParams();

            $params['redirectParams'] = json_encode([
                'route' => 'oro_checkout_frontend_checkout',
                'params' => [
                    'id' => $checkoutId,
                    'transition' => 'back_to_billing_address'
                ]
            ]);
            $event->setEmailTemplateParams($params);
        }
    }
}
