<?php

namespace Oro\Bundle\PaymentTermBundle\Provider;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
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
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getPaymentTerm(Account $account)
    {
        $paymentTerm = $this->getAccountPaymentTerm($account);

        if (!$paymentTerm && $account->getGroup()) {
            $paymentTerm = $this->getAccountGroupPaymentTerm($account->getGroup());
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

        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $paymentTermEvent->setPaymentTerm($this->getPaymentTerm($user->getAccount()));
        }

        $this->eventDispatcher->dispatch(ResolvePaymentTermEvent::NAME, $paymentTermEvent);

        return $paymentTermEvent->getPaymentTerm();
    }

    /**
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getAccountPaymentTerm(Account $account)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($account);
    }

    /**
     * @param CustomerGroup $accountGroup
     * @return PaymentTerm|null
     */
    public function getAccountGroupPaymentTerm(CustomerGroup $accountGroup)
    {
        return $this->paymentTermAssociationProvider->getPaymentTerm($accountGroup);
    }

    /**
     * @param AccountOwnerAwareInterface $accountOwnerAware
     * @return PaymentTerm|null
     */
    public function getAccountPaymentTermByOwner(AccountOwnerAwareInterface $accountOwnerAware)
    {
        $account = $accountOwnerAware->getAccount();
        if (!$account) {
            return null;
        }

        return $this->getAccountPaymentTerm($account);
    }

    /**
     * @param AccountOwnerAwareInterface $accountOwnerAware
     * @return PaymentTerm|null
     */
    public function getAccountGroupPaymentTermByOwner(AccountOwnerAwareInterface $accountOwnerAware)
    {
        $account = $accountOwnerAware->getAccount();
        if (!$account || !$account->getGroup()) {
            return null;
        }

        return $this->getAccountGroupPaymentTerm($account->getGroup());
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
