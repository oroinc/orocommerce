<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultCheckoutOwnerSetter
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultOwner(Checkout $checkout)
    {
        if ($checkout->getOwner()) {
            return;
        }
        /**
         * @TODO: Must be updated when requirements will be clarified
         */
        /** @var User $owner */
        $owner = $this->doctrineHelper->getEntityRepositoryForClass(User::class)->findOneBy([]);
        $checkout->setOwner($owner);
    }
}
