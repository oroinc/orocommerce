<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Doctrine ORM entity listener for Order and OrderLineItem entities
 * This listener handle tax saving/removing/update by calling correspond methods of tax provider
 */
class EntityTaxListener
{
    /**
     * @var TaxProviderRegistry
     */
    protected $taxProviderRegistry;

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @var TaxValue[]
     */
    protected $taxValues = [];

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @param TaxProviderRegistry $taxProviderRegistry
     */
    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param OnFlushEventArgs $event
     */
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
     * @deprecated since 3.1, will be removed in 4.0
     * This method is workaround and should be removed after BB-11299
     *
     * @param boolean $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @param object $entity
     * @param LifecycleEventArgs $event
     */
    public function prePersist($entity, LifecycleEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        /**
         * Entities without ID can't be processed in preFlush, because flush() call required.
         * Create new TaxValue entities with empty "entityId" property.
         * Fill this property in postPersist event
         */
        if ($this->getIdentifier($entity, $event->getEntityManager())) {
            return;
        }

        try {
            $taxValue = $this->getProvider()->createTaxValue($entity);

            $this->taxValues[$this->getKey($entity)] = $taxValue;
            $event->getEntityManager()->persist($taxValue);
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax saving
        }
    }

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @param object $entity
     * @param LifecycleEventArgs $event
     */
    public function postPersist($entity, LifecycleEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $key = $this->getKey($entity);
        if (array_key_exists($key, $this->taxValues)) {
            $id = $this->getIdentifier($entity, $event->getEntityManager());
            $taxValue = $this->taxValues[$key];
            $taxValue->setEntityId($id);

            $uow = $event->getEntityManager()->getUnitOfWork();
            $uow->propertyChanged($taxValue, 'entityId', null, $id);
            $uow->scheduleExtraUpdate($taxValue, ['entityId' => [null, $id]]);
            $uow->recomputeSingleEntityChangeSet(
                $event->getEntityManager()->getClassMetadata(ClassUtils::getClass($taxValue)),
                $taxValue
            );

            unset($this->taxValues[$key]);
        }
    }

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @param object $entity
     * @param PreFlushEventArgs $event
     */
    public function preFlush($entity, PreFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        // Entities with ID can be processed in preFlush
        if ($this->getIdentifier($entity, $event->getEntityManager())) {
            try {
                $this->getProvider()->saveTax($entity);
            } catch (TaxationDisabledException $e) {
                // Taxation disabled, skip tax saving
            }
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
     * @deprecated since 3.1, will be removed in 4.0
     * @param object $object
     * @param EntityManagerInterface $entityManager
     * @return mixed false if empty
     */
    protected function getIdentifier($object, EntityManagerInterface $entityManager)
    {
        $ids = $entityManager->getClassMetadata(ClassUtils::getClass($object))->getIdentifierValues($object);

        if (!$ids) {
            return false;
        }

        return reset($ids);
    }

    /**
     * @deprecated since 3.1, will be removed in 4.0
     * @param $object
     * @return string
     */
    protected function getKey($object)
    {
        return spl_object_hash($object);
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
