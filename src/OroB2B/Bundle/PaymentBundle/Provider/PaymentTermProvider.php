<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository;

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
     * @param ManagerRegistry $registry
     * @param TokenStorageInterface $tokenStorage
     * @param string $paymentTermClass
     */
    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage, $paymentTermClass)
    {
        $this->registry = $registry;
        $this->paymentTermClass = $paymentTermClass;
        $this->tokenStorage = $tokenStorage;
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
            return $this->getAccountPaymentTerm($user->getAccount());
        }

        return null;
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
