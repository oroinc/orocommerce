<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sets the owner information for Payment transaction from current token.
 */
class PaymentTransactionOwnerListener
{
    /** @var TokenAccessor*/
    private $tokenAccessor;

    /**@param TokenAccessor $tokenAccessor
     */
    public function __construct(TokenAccessor $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Sets the owner information for Payment transaction from current token.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof PaymentTransaction) {
            return;
        }

        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $user = $this->tokenAccessor->getUser();
        if ($user instanceof User && null === $entity->getOwner()) {
            $entity->setOwner($user);
        }
        if ($user instanceof CustomerUser && null === $entity->getFrontendOwner()) {
            $entity->setFrontendOwner($user);
        }

        $organization = $this->tokenAccessor->getOrganization();
        if ($organization && null === $entity->getOrganization()) {
            $entity->setOrganization($organization);
        }
    }
}
