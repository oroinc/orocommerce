<?php

namespace OroB2B\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class PaymentTermRepository extends EntityRepository
{
    /**
     * @param CustomerGroup $customerGroup
     * @return PaymentTerm|null
     */
    public function getOnePaymentTermByCustomerGroup(CustomerGroup $customerGroup)
    {
        return $this->createQueryBuilder('paymentTerm')
            ->innerJoin('paymentTerm.customerGroups', 'customerGroup')
            ->andWhere('customerGroup = :customerGroup')
            ->setParameter('customerGroup', $customerGroup)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOnePaymentTermByCustomer(Customer $customer)
    {
        return $this->createQueryBuilder('paymentTerm')
            ->innerJoin('paymentTerm.customers', 'customer')
            ->andWhere('customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param CustomerGroup    $customerGroup
     * @param PaymentTerm|null $paymentTerm
     */
    public function setPaymentTermToCustomerGroup(CustomerGroup $customerGroup, PaymentTerm $paymentTerm = null)
    {
        $oldPaymentTermByCustomerGroup = $this->getOnePaymentTermByCustomerGroup($customerGroup);

        if (
            $oldPaymentTermByCustomerGroup &&
            $paymentTerm &&
            $oldPaymentTermByCustomerGroup->getId() === $paymentTerm->getId()
        ) {
            return;
        }

        if ($oldPaymentTermByCustomerGroup) {
            $oldPaymentTermByCustomerGroup->removeCustomerGroup($customerGroup);
        }

        if ($paymentTerm) {
            $paymentTerm->addCustomerGroup($customerGroup);
        }
    }

    /**
     * @param Customer         $customer
     * @param PaymentTerm|null $paymentTerm
     */
    public function setPaymentTermToCustomer(Customer $customer, PaymentTerm $paymentTerm = null)
    {
        $oldPaymentTermByCustomer = $this->getOnePaymentTermByCustomer($customer);

        if (
            $oldPaymentTermByCustomer &&
            $paymentTerm &&
            $oldPaymentTermByCustomer->getId() === $paymentTerm->getId()
        ) {
            return;
        }

        if ($oldPaymentTermByCustomer) {
            $oldPaymentTermByCustomer->removeCustomer($customer);
        }

        if ($paymentTerm) {
            $paymentTerm->addCustomer($customer);
        }
    }
}
