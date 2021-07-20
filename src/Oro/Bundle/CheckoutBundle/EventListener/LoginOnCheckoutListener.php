<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Assigns checkout to newly logged CustomerUser (registered from guest checkout)
 */
class LoginOnCheckoutListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var CheckoutManager
     */
    private $checkoutManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        ConfigManager $configManager,
        CheckoutManager $checkoutManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->checkoutManager = $checkoutManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof CustomerUser) {
            return;
        }

        if ($user->getLoginCount() <= 1) {
            $this->checkoutManager->reassignCustomerUser($user);
        }

        $checkoutId = $event->getRequest()->request->get('_checkout_id');

        if (!$checkoutId || !$this->configManager->get('oro_checkout.guest_checkout')) {
            return;
        }

        $checkout = $this->checkoutManager->getCheckoutById($checkoutId);

        if (!$checkout || $checkout->getCustomerUser() || $checkout->getCustomer()) {
            $this->logger->warning("Wrong checkout id - $checkoutId passed during login from checkout");
            return;
        }

        $this->checkoutManager->updateCheckoutCustomerUser($checkout, $user);
        $this->dispatchLoginOnCheckoutEvent($checkout);
    }

    private function dispatchLoginOnCheckoutEvent(Checkout $checkout)
    {
        if (!$this->eventDispatcher->hasListeners(LoginOnCheckoutEvent::NAME)) {
            return;
        }

        $event = new LoginOnCheckoutEvent();
        $event->setSource($checkout->getSource());

        $this->eventDispatcher->dispatch($event, LoginOnCheckoutEvent::NAME);
    }
}
