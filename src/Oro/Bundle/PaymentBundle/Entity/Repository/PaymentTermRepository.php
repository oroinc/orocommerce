<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermRepository extends EntityRepository
{
    /**
     * @param AccountGroup $accountGroup
     * @return PaymentTerm|null
     */
    public function getOnePaymentTermByAccountGroup(AccountGroup $accountGroup)
    {
        if (!$accountGroup->getId()) {
            return null;
        }

        $qb = $this->createQueryBuilder('paymentTerm');

        $qb->innerJoin('paymentTerm.accountGroups', 'accountGroup')
            ->andWhere($qb->expr()->eq('accountGroup', ':accountGroup'))
            ->setParameter('accountGroup', $accountGroup);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Account $account
     * @return PaymentTerm|null
     */
    public function getOnePaymentTermByAccount(Account $account)
    {
        if (!$account->getId()) {
            return null;
        }

        $qb = $this->createQueryBuilder('paymentTerm');
        $qb->innerJoin('paymentTerm.accounts', 'account')
            ->andWhere('account = :account')
            ->setParameter('account', $account);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param AccountGroup $accountGroup
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
