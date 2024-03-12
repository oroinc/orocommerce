<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Payment Status
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_payment_status')]
#[ORM\UniqueConstraint(name: 'oro_payment_status_unique', columns: ['entity_class', 'entity_identifier'])]
class PaymentStatus
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'entity_class', type: Types::STRING)]
    protected ?string $entityClass = null;

    #[ORM\Column(name: 'entity_identifier', type: Types::INTEGER)]
    protected ?int $entityIdentifier = null;

    #[ORM\Column(name: 'payment_status', type: Types::STRING)]
    protected ?string $paymentStatus = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return PaymentStatus
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = (string)$entityClass;

        return $this;
    }

    /**
     * @return int
     */
    public function getEntityIdentifier()
    {
        return $this->entityIdentifier;
    }

    /**
     * @param int $entityIdentifier
     * @return PaymentStatus
     */
    public function setEntityIdentifier($entityIdentifier)
    {
        $this->entityIdentifier = (int)$entityIdentifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @param string $paymentStatus
     * @return PaymentStatus
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = (string)$paymentStatus;

        return $this;
    }
}
