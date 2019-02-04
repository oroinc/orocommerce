<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sets the owner information for Payment transaction from current security context.
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
     * Sets the owner information for a payment transaction from current security context.
     *
     * @param PaymentTransaction $entity
     * @param LifecycleEventArgs $args
     */
    public function prePersist(PaymentTransaction $entity, LifecycleEventArgs $args)
    {
        $user = $this->tokenAccessor->getUser();
        if ($user instanceof User) {
            if (null === $entity->getOwner()) {
                $entity->setOwner($user);
            }
        } elseif ($user instanceof CustomerUser) {
            if (null === $entity->getFrontendOwner()) {
                $entity->setFrontendOwner($user);
            }
        }

        $organization = $this->tokenAccessor->getOrganization();
        if (null !== $organization && null === $entity->getOrganization()) {
            $entity->setOrganization($organization);
        }
    }
}
