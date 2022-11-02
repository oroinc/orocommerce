<?php

namespace Oro\Bundle\CheckoutBundle\Manager;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Handles logic for reassigning Customer if he was registered as guest (sets registered_id to null)
 */
class CheckoutManager
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CustomerUser $customerUser
     * @param integer $checkoutId
     */
    public function assignRegisteredCustomerUserToCheckout(CustomerUser $customerUser, $checkoutId)
    {
        $checkout = $this->getCheckoutById($checkoutId);

        //Only guest checkout which is not still assigned
        if ($checkout && !$checkout->getRegisteredCustomerUser() &&
            (!$checkout->getCustomerUser() || $checkout->getCustomerUser()->isGuest())) {
            $checkout->setRegisteredCustomerUser($customerUser);
            $this->getEntityManager()->flush($checkout);
        }
    }

    public function reassignCustomerUser(CustomerUser $customerUser)
    {
        /** @var Checkout $checkout */
        $checkout = $this->getRepository()->findOneBy(['registeredCustomerUser' => $customerUser]);

        if (!$checkout) {
            return;
        }

        $checkout->setRegisteredCustomerUser(null);
        $this->updateCheckoutCustomerUser($checkout, $customerUser);
    }

    /**
     * @param $checkoutId
     * @return Checkout|null
     */
    public function getCheckoutById($checkoutId)
    {
        return $this->getRepository()->find($checkoutId);
    }

    public function updateCheckoutCustomerUser(Checkout $checkout, CustomerUser $customerUser)
    {
        $checkout->setCustomerUser($customerUser);
        $checkout->setCustomer($customerUser->getCustomer());

        $this->getEntityManager()->flush($checkout);
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(Checkout::class);
    }

    /**
     * @return \Doctrine\ORM\EntityManager|null
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(Checkout::class);
    }
}
