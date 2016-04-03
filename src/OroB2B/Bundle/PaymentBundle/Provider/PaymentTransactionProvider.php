<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class PaymentTransactionProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $paymentTransactionClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param $paymentTransactionClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $paymentTransactionClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionClass = $paymentTransactionClass;
    }

    /**
     * @param object $object
     * @param array $filter
     * @return PaymentTransaction
     */
    public function getPaymentTransaction($object, array $filter = [])
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            array_merge(
                $filter,
                [
                    'entityClass' => $className,
                    'entityIdentifier' => $identifier,
                ]
            )
        );
    }

    /**
     * @param object $object
     * @param string $action
     * @return PaymentTransaction
     */
    public function getActivePaymentTransaction($object, $action)
    {
        return $this->getPaymentTransaction(
            $object,
            ['active' => true, 'action' => (string)$action]
        );
    }

    /**
     * @param string $paymentMethod
     * @param string $type
     * @param object $object
     * @return PaymentTransaction
     */
    public function createPaymentTransaction($paymentMethod, $type, $object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = new $this->paymentTransactionClass;
        $paymentTransaction
            ->setPaymentMethod($paymentMethod)
            ->setAction($type)
            ->setEntityClass($className)
            ->setEntityIdentifier($identifier);

        return $paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function savePaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $em = $this->doctrineHelper->getEntityManager($paymentTransaction);

        $em->persist($paymentTransaction);
        $em->flush($paymentTransaction);
    }
}
