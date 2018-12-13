<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\DependencyInjection\Configuration;
use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Subscribed on Oro\Bundle\RFPBundle\Entity\Request persist
 * it sets owner and CustomerUser to the entity in case of Guest user
 */
class RFPListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var GuestCustomerUserManager */
    private $guestCustomerUserManager;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     * @param TokenAccessorInterface $tokenAccessor
     * @param GuestCustomerUserManager $guestCustomerUserManager
     */
    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        GuestCustomerUserManager $guestCustomerUserManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->guestCustomerUserManager = $guestCustomerUserManager;
    }

    /**
     * @param Request $request
     */
    public function prePersist(Request $request)
    {
        $token = $this->tokenAccessor->getToken();

        if ($token instanceof AnonymousCustomerUserToken) {
            $this->setOwner($request);
            $this->setCustomerUser($request);
        }
    }

    /**
     * @param Request $request
     */
    protected function setOwner(Request $request)
    {
        if (null === $request->getOwner()) {
            $request->setOwner(
                $this->defaultUserProvider->getDefaultUser(
                    OroRFPExtension::ALIAS,
                    Configuration::DEFAULT_GUEST_RFP_OWNER
                )
            );
        }
    }

    /**
     * Always generates new CustomerUser for RFQ which is not assigned to visitor
     * @param Request $request
     */
    private function setCustomerUser(Request $request)
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
