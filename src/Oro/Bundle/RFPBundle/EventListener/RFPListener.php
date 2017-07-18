<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\RFPBundle\DependencyInjection\Configuration;
use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class RFPListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param Request $request
     */
    public function prePersist(Request $request)
    {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && null === $request->getOwner()
        ) {
            $request->setOwner($this->defaultUserProvider->getDefaultUser(
                OroRFPExtension::ALIAS,
                Configuration::DEFAULT_GUEST_RFP_OWNER
            ));
        }
    }
}
