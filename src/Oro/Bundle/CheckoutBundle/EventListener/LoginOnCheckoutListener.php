<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

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
     * @param LoggerInterface $logger
     * @param ConfigManager $configManager
     * @param CheckoutManager $checkoutManager
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigManager $configManager,
        CheckoutManager $checkoutManager
    ) {
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->checkoutManager = $checkoutManager;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
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
    }
}
