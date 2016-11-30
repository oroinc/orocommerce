<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;

class PaymentTermProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $paymentTermClass;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ManagerRegistry $registry
     * @param TokenStorageInterface $tokenStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $paymentTermClass
     */
    public function __construct(
        ManagerRegistry $registry,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        $paymentTermClass
    ) {
        $this->registry = $registry;
        $this->paymentTermClass = $paymentTermClass;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
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

        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $paymentTermEvent = new ResolvePaymentTermEvent($this->getPaymentTerm($user->getAccount()));
        }

        $paymentTermEvent = isset($paymentTermEvent) ? $paymentTermEvent : new ResolvePaymentTermEvent();
        $this->eventDispatcher->dispatch(ResolvePaymentTermEvent::NAME, $paymentTermEvent);

        return $paymentTermEvent->getPaymentTerm();
    }

    /**
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getAccountPaymentTerm(Account $account)
    {
        return $this->getPaymentTermRepository()->getOnePaymentTermByAccount($account);
    }

    /**
     * @param AccountGroup $accountGroup
     * @return PaymentTerm|null
     */
    public function getAccountGroupPaymentTerm(AccountGroup $accountGroup)
    {
        return $this->getPaymentTermRepository()->getOnePaymentTermByAccountGroup($accountGroup);
    }

    /**
     * @return PaymentTermRepository
     */
    protected function getPaymentTermRepository()
    {
        return $this->registry->getManagerForClass($this->paymentTermClass)->getRepository($this->paymentTermClass);
    }
}
