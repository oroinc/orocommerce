<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;

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
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param RequestStack $requestStack
     * @param LoginManager $loginManager
     * @param CheckoutManager $checkoutManager
     * @param ConfigManager $configManager

     */
    public function __construct(
        RequestStack $requestStack,
        LoginManager $loginManager,
        CheckoutManager $checkoutManager,
        ConfigManager $configManager
    ) {
        $this->requestStack = $requestStack;
        $this->loginManager = $loginManager;
        $this->checkoutManager = $checkoutManager;
        $this->configManager = $configManager;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function afterFlush(AfterFormProcessEvent $event)
    {
        $customerUser = $event->getData();

        if ($this->getFromRequest('_checkout_registration')) {
            if ($customerUser->isConfirmed()) {
                $this->loginManager->logInUser('frontend_secure', $customerUser);

                return;
            }

            $checkoutId = $this->getFromRequest('_checkout_id');
            if ($checkoutId) {
                $this->checkoutManager->assignRegisteredCustomerUserToCheckout($customerUser, $checkoutId);
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getFromRequest($name)
    {
        if (!$this->request) {
            $this->request = $this->requestStack->getMasterRequest();
        }

        return $this->request->request->get($name);
    }

    /**
     * @param CustomerUserEmailSendEvent $event
     */
    public function onCustomerUserEmailSend(CustomerUserEmailSendEvent $event)
    {
        $checkoutId = $this->getFromRequest('_checkout_id');
        if ($this->getFromRequest('_checkout_registration') && $checkoutId &&
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
