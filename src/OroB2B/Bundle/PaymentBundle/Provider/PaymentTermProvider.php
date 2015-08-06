<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
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
        $repository = $this->getPaymentTermRepository();
        $paymentTerm = $repository->getOnePaymentTermByAccount($account);

        if (!$paymentTerm && $account->getGroup()) {
            $paymentTerm = $repository->getOnePaymentTermByAccountGroup($account->getGroup());
        }

        return $paymentTerm;
    }

    /**
     * @return PaymentTermRepository
     */
    protected function getPaymentTermRepository()
    {
        return $this->registry->getManagerForClass($this->paymentTermClass)->getRepository($this->paymentTermClass);
    }
}
