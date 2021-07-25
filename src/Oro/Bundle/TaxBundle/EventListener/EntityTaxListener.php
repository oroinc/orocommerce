<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Doctrine ORM entity listener for Order and OrderLineItem entities.
 * This listener handle tax saving/removing/update by calling correspond methods of tax provider.
 */
class EntityTaxListener
{
    private TaxProviderRegistry $taxProviderRegistry;
    private string $entityClass;

    public function __construct(TaxProviderRegistry $taxProviderRegistry, string $entityClass)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->entityClass = $entityClass;
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entities = $uow->getScheduledEntityUpdates();
        if (!$entities) {
            return;
        }

        $taxProvider = $this->getTaxProvider();
        try {
            foreach ($entities as $entity) {
                if (is_a($entity, $this->entityClass)) {
                    $taxProvider->saveTax($entity);
                }
            }
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax saving
        }
    }

    public function preRemove(object $entity): void
    {
        try {
            $this->getTaxProvider()->removeTax($entity);
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax removing
        }
    }

    private function getTaxProvider(): TaxProviderInterface
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
