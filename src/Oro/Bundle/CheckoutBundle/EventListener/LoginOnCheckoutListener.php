<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param LoggerInterface $logger
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $checkoutId = $event->getRequest()->request->get('_checkout_id');

        if (!$user instanceof CustomerUser ||
            !$checkoutId ||
            !$this->configManager->get('oro_checkout.guest_checkout')) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $this->doctrineHelper
            ->getEntityRepository(Checkout::class)
            ->find($checkoutId);

        if (!$checkout || $checkout->getCustomerUser() || $checkout->getCustomer()) {
            $this->logger->warning("Wrong checkout id - $checkoutId passed during login from checkout");
            return;
        }

        $checkout->setCustomerUser($user);
        $checkout->setCustomer($user->getCustomer());

        $this->doctrineHelper->getEntityManager(Checkout::class)->flush($checkout);
    }
}
