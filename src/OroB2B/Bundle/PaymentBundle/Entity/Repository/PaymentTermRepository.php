<?php

namespace OroB2B\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermRepository extends EntityRepository
{
    /**
     * @param AccountGroup $accountGroup
     * @return PaymentTerm|null
     */
    public function getOnePaymentTermByAccountGroup(AccountGroup $accountGroup)
    {
        return $this->createQueryBuilder('paymentTerm')
            ->innerJoin('paymentTerm.accountGroups', 'accountGroup')
            ->andWhere('accountGroup = :accountGroup')
            ->setParameter('accountGroup', $accountGroup)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getOnePaymentTermByAccount(Account $account)
    {
        return $this->createQueryBuilder('paymentTerm')
            ->innerJoin('paymentTerm.accounts', 'account')
            ->andWhere('account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param AccountGroup    $accountGroup
     * @param PaymentTerm|null $paymentTerm
     */
    public function setPaymentTermToAccountGroup(AccountGroup $accountGroup, PaymentTerm $paymentTerm = null)
    {
        $oldPaymentTermByAccountGroup = $this->getOnePaymentTermByAccountGroup($accountGroup);

        if ($oldPaymentTermByAccountGroup &&
            $paymentTerm &&
            $oldPaymentTermByAccountGroup->getId() === $paymentTerm->getId()
        ) {
            return;
        }

        if ($oldPaymentTermByAccountGroup) {
            $oldPaymentTermByAccountGroup->removeAccountGroup($accountGroup);
        }

        if ($paymentTerm) {
            $paymentTerm->addAccountGroup($accountGroup);
        }
    }

    /**
     * @param Account         $account
     * @param PaymentTerm|null $paymentTerm
     */
    public function setPaymentTermToAccount(Account $account, PaymentTerm $paymentTerm = null)
    {
        $oldPaymentTermByAccount = $this->getOnePaymentTermByAccount($account);

        if ($oldPaymentTermByAccount &&
            $paymentTerm &&
            $oldPaymentTermByAccount->getId() === $paymentTerm->getId()
        ) {
            return;
        }

        if ($oldPaymentTermByAccount) {
            $oldPaymentTermByAccount->removeAccount($account);
        }

        if ($paymentTerm) {
            $paymentTerm->addAccount($account);
        }
    }
}
