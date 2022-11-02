<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Subscribed on Oro\Bundle\RFPBundle\Entity\Request persist
 * it sets owner and CustomerUser to the entity in case of Guest user
 */
class RFPListener
{
    private DefaultUserProvider $defaultUserProvider;
    private TokenAccessorInterface $tokenAccessor;
    private GuestCustomerUserManager $guestCustomerUserManager;

    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        GuestCustomerUserManager $guestCustomerUserManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->guestCustomerUserManager = $guestCustomerUserManager;
    }

    public function prePersist(Request $request): void
    {
        $token = $this->tokenAccessor->getToken();

        if ($token instanceof AnonymousCustomerUserToken) {
            $this->setOwner($request);
            $this->setCustomerUser($request);
        }
    }

    private function setOwner(Request $request): void
    {
        if (null === $request->getOwner()) {
            $request->setOwner(
                $this->defaultUserProvider->getDefaultUser('oro_rfp.default_guest_rfp_owner')
            );
        }
    }

    /**
     * Always generates new CustomerUser for RFQ which is not assigned to visitor
     */
    private function setCustomerUser(Request $request): void
    {
        $user = $this->guestCustomerUserManager
            ->generateGuestCustomerUser(
                [
                    'email' => $request->getEmail(),
                    'first_name' => $request->getFirstName(),
                    'last_name' => $request->getLastName()
                ]
            );

        $request->setCustomerUser($user);
    }
}
