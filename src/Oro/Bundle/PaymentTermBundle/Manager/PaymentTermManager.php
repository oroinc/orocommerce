<?php

namespace Oro\Bundle\PaymentTermBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

class PaymentTermManager
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PaymentTermAssociationProvider $paymentTermAssociationProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
    }

    /**
     * @param string $entityClass
     * @param PaymentTerm $paymentTerm
     * @return bool
     */
    public function hasAssignedPaymentTerm($entityClass, PaymentTerm $paymentTerm)
    {
        if (!$this->getAssociationName($entityClass)) {
            return false;
        }

        $qb = $this->doctrineHelper
            ->getEntityRepository($entityClass)
            ->createQueryBuilder('e');

        return (bool)$qb
            ->select($qb->expr()->count('e'))
            ->where($qb->expr()->eq(sprintf('IDENTITY(e.%s)', $this->getAssociationName($entityClass)), ':paymentTerm'))
            ->setParameter('paymentTerm', $paymentTerm)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $paymentTermId
     * @return PaymentTerm
     */
    public function getReference($paymentTermId)
    {
        return $this->doctrineHelper->getEntityReference(PaymentTerm::class, $paymentTermId);
    }

    /**
     * @param string $entityClass
     * @return string
     */
    public function getAssociationName($entityClass = null)
    {
        $default = $this->paymentTermAssociationProvider->getDefaultAssociationName();

        if ($entityClass
            && !in_array($default, $this->paymentTermAssociationProvider->getAssociationNames($entityClass), true)
        ) {
            return null;
        }

        return $default;
    }
}
