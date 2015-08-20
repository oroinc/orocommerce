<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
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
     * @param ManagerRegistry $registry
     * @param string $paymentTermClass
     */
    public function __construct(ManagerRegistry $registry, $paymentTermClass)
    {
        $this->registry = $registry;
        $this->paymentTermClass = $paymentTermClass;
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
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getAccountPaymentTerm(Account $account)
    {
        return $this->getPaymentTermRepository()->getOnePaymentTermByAccount($account);
    }

    /**
     * @param AccountGroup $account
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
