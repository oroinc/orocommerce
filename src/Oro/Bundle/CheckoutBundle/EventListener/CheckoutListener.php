<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class CheckoutListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var WebsiteManager */
    private $websiteManager;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     * @param TokenAccessorInterface $tokenAccessor
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        WebsiteManager $websiteManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param Checkout $checkout
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(Checkout $checkout, LifecycleEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        $unitOfWork->scheduleExtraUpdate(
            $checkout,
            [
                'completedData' => [null, $checkout->getCompletedData()]
            ]
        );
    }

    /**
     * @param Checkout $checkout
     */
    public function prePersist(Checkout $checkout)
    {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && null === $checkout->getOwner()
        ) {
            $checkout->setOwner($this->defaultUserProvider->getDefaultUser(
                OroCheckoutExtension::ALIAS,
                Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
            ));

            $website = $this->websiteManager->getCurrentWebsite();
            if ($website && $website->getOrganization()) {
                $checkout->setOrganization($website->getOrganization());
            }
        }
    }
}
