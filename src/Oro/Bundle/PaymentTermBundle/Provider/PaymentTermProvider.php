<?php

namespace Oro\Bundle\PaymentTermBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PaymentTermProvider
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        PaymentTermAssociationProvider $paymentTermAssociationProvider
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
    }

    /**
     * @param Customer $customer
     * @return PaymentTerm|null
     */
    public function getPaymentTerm(Customer $customer)
    {
        $paymentTerm = $this->getCustomerPaymentTerm($customer);

        if (!$paymentTerm && $customer->getGroup()) {
            $paymentTerm = $this->getCustomerGroupPaymentTerm($customer->getGroup());
        }

        return $paymentTerm;
    }

    /**
     * @return PaymentTerm|null
     */
    public function getCurrentPaymentTerm()
    {
        $token = $this->tokenStorage->getToken();

        $paymentTermEvent = new ResolvePaymentTermEvent();

        /** @var CustomerUser $user */
        if ($token && ($user = $token->getUser()) instanceof CustomerUser) {
            $paymentTermEvent->setPaymentTerm($this->getPaymentTerm($user->getCustomer()));
        }

        $this->eventDispatcher->dispatch(ResolvePaymentTermEvent::NAME, $paymentTermEvent);

        return $paymentTermEvent->getPaymentTerm();
    }

    /**
     * @param Customer $customer
     * @return PaymentTerm|null
     */
    public function getCustomerPaymentTerm(Customer $customer)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($customer);
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return PaymentTerm|null
     */
    public function getCustomerGroupPaymentTerm(CustomerGroup $customerGroup)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($customerGroup);
    }

    /**
     * @param CustomerOwnerAwareInterface $customerOwnerAware
     * @return PaymentTerm|null
     */
    public function getCustomerPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        $customer = $customerOwnerAware->getCustomer();
        if (!$customer) {
            return null;
        }

        return $this->getCustomerPaymentTerm($customer);
    }

    /**
     * @param CustomerOwnerAwareInterface $customerOwnerAware
     * @return PaymentTerm|null
     */
    public function getCustomerGroupPaymentTermByOwner(CustomerOwnerAwareInterface $customerOwnerAware)
    {
        $customer = $customerOwnerAware->getCustomer();
        if (!$customer || !$customer->getGroup()) {
            return null;
        }

        return $this->getCustomerGroupPaymentTerm($customer->getGroup());
    }

    /**
     * @param object $object
     * @return null|PaymentTerm
     * @throws \InvalidArgumentException if argument is not an object
     */
    public function getObjectPaymentTerm($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(sprintf('Object expected, "%s" given', gettype($object)));
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames(ClassUtils::getClass($object));
        foreach ($associationNames as $associationName) {
            $paymentTerm = $this->paymentTermAssociationProvider->getPaymentTerm($object, $associationName);
            if ($paymentTerm) {
                return $paymentTerm;
            }
        }

        return null;
    }
}
