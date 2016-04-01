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
     * @return PaymentTransaction
     */
    public function getPaymentTransaction($object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        return $this->doctrineHelper->getEntityRepository($this->paymentTransactionClass)->findOneBy(
            [
                'entityClass' => $className,
                'entityIdentifier' => $identifier,
            ]
        );
    }

    /**
     * @param string $type
     * @param object $object
     * @return PaymentTransaction
     */
    public function createPaymentTransaction($type, $object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = new $this->paymentTransactionClass;
        $paymentTransaction
            ->setEntityClass($className)
            ->setEntityIdentifier($identifier)
            ->setType($type);

        return $paymentTransaction;
    }
}
