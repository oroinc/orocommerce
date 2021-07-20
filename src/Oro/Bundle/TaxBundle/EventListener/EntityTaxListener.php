<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Doctrine ORM entity listener for Order and OrderLineItem entities
 * This listener handle tax saving/removing/update by calling correspond methods of tax provider
 */
class EntityTaxListener
{
    /** @var TaxProviderRegistry */
    private $taxProviderRegistry;

    /** @var string */
    private $entityClass;

    public function __construct(TaxProviderRegistry $taxProviderRegistry, string $entityClass)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->entityClass = $entityClass;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        try {
            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                if (is_a($entity, $this->entityClass)) {
                    $this->getProvider()->saveTax($entity);
                }
            }
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax saving
        }
    }

    /**
     * @param object $entity
     */
    public function preRemove($entity)
    {
        try {
            $this->getProvider()->removeTax($entity);
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax removing
        }
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
