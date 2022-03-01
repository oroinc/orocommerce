<?php

namespace Oro\Bundle\PricingBundle\Event;

use Doctrine\ORM\EntityManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * It published immediately after the flush.
 */
class ProductPricesUpdated extends Event
{
    public const NAME = 'oro_pricing.product_prices.updated';

    private EntityManager $entityManager;
    private array $removed;
    private array $saved;
    private array $updated;
    private array $changeSets;

    public function __construct(
        EntityManager $entityManager,
        array $removed,
        array $saved,
        array $updated,
        array $changeSets
    ) {
        $this->entityManager = $entityManager;
        $this->removed = $removed;
        $this->saved = $saved;
        $this->updated = $updated;
        $this->changeSets = $changeSets;
    }

    public function getEntityManager(): ?EntityManager
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getRemoved(): array
    {
        return $this->removed;
    }

    public function getSaved(): array
    {
        return $this->saved;
    }

    public function getUpdated(): array
    {
        return $this->updated;
    }

    public function getChangeSets(): array
    {
        return $this->changeSets;
    }
}
