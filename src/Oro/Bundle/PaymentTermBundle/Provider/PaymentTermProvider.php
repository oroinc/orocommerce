<?php

namespace Oro\Bundle\PaymentTermBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This provider allows to get PaymentTerm entity from different sources.
 */
class PaymentTermProvider implements PaymentTermProviderInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getCurrentPaymentTerm()
    {
        $token = $this->tokenStorage->getToken();

        $paymentTermEvent = new ResolvePaymentTermEvent();

        if ($token) {
            if ($token->getUser() instanceof CustomerUser) {
                $user = $token->getUser();
                $paymentTermEvent->setPaymentTerm($this->getPaymentTerm($user->getCustomer()));
            } elseif ($token instanceof AnonymousCustomerUserToken
                && $token->getVisitor()->getCustomerUser()
                && $token->getVisitor()->getCustomerUser()->getCustomer()
            ) {
                $paymentTermEvent->setPaymentTerm(
                    $this->getPaymentTerm(
                        $token->getVisitor()->getCustomerUser()->getCustomer()
                    )
                );
            }
        }

        $this->eventDispatcher->dispatch($paymentTermEvent, ResolvePaymentTermEvent::NAME);

        return $paymentTermEvent->getPaymentTerm();
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerPaymentTerm(Customer $customer)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($customer);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerGroupPaymentTerm(CustomerGroup $customerGroup)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($customerGroup);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
