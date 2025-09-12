<?php

namespace Oro\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroPaymentBundle_Entity_PaymentStatus;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;

/**
 * Represents the payment status of an entity.
 *
 * @mixin OroPaymentBundle_Entity_PaymentStatus
 */
#[ORM\Entity(repositoryClass: PaymentStatusRepository::class)]
#[ORM\Table(name: 'oro_payment_status')]
#[ORM\UniqueConstraint(name: 'oro_payment_status_unique', columns: ['entity_class', 'entity_identifier'])]
#[Config]
class PaymentStatus implements UpdatedAtAwareInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;
    use UpdatedAtAwareTrait;

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
     * Indicates whether the payment status is forcefully set.
     * If true, the payment status will not be recalculated in the future.
     */
    #[ORM\Column(name: 'forced', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $forced = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(?string $entityClass): self
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    public function getEntityIdentifier(): ?int
    {
        return $this->entityIdentifier;
    }

    public function setEntityIdentifier(?int $entityIdentifier): self
    {
        $this->entityIdentifier = $entityIdentifier;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Indicates whether the payment status is forcefully set.
     * If true, the payment status will not be recalculated in the future.
     */
    public function isForced(): bool
    {
        return $this->forced;
    }

    /**
     * Sets whether the payment status is forcefully set.
     * If true, the payment status will not be recalculated in the future.
     */
    public function setForced(bool $forced): self
    {
        $this->forced = $forced;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getPaymentStatus();
    }
}
