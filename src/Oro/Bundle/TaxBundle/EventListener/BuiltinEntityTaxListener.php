<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Doctrine entity listener for built-in tax provider helps to create TaxValue entities
 * for Order and OrderLineItem during flush cycle
 */
class BuiltinEntityTaxListener
{
    /** @var TaxProviderRegistry */
    private $taxProviderRegistry;

    /** @var TaxValue[] */
    private $taxValues = [];

    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * @param object $entity
     * @param LifecycleEventArgs $event
     */
    public function prePersist($entity, LifecycleEventArgs $event)
    {
        $provider = $this->getProvider();

        // Skip this logic if not built-in tax provider is used
        if (!$provider instanceof BuiltInTaxProvider) {
            return;
        }

        /**
         * Entities without ID can't be processed in preFlush, because flush() call required.
         * Create new TaxValue entities with empty "entityId" property.
         * Fill this property in postPersist event
         */
        if ($this->getIdentifier($entity, $event->getObjectManager())) {
            return;
        }

        try {
            $taxValue = $provider->createTaxValue($entity);

            $this->taxValues[$this->getKey($entity)] = $taxValue;
            $event->getObjectManager()->persist($taxValue);
        } catch (TaxationDisabledException $e) {
            // Taxation disabled, skip tax saving
        }
    }

    /**
     * @param object $entity
     * @param LifecycleEventArgs $event
     */
    public function postPersist($entity, LifecycleEventArgs $event)
    {
        $key = $this->getKey($entity);
        if (array_key_exists($key, $this->taxValues)) {
            $id = $this->getIdentifier($entity, $event->getObjectManager());
            $taxValue = $this->taxValues[$key];
            $taxValue->setEntityId($id);

            $uow = $event->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($taxValue, 'entityId', null, $id);
            $uow->scheduleExtraUpdate($taxValue, ['entityId' => [null, $id]]);
            $uow->recomputeSingleEntityChangeSet(
                $event->getObjectManager()->getClassMetadata(ClassUtils::getClass($taxValue)),
                $taxValue
            );

            unset($this->taxValues[$key]);
        }
    }

    /**
     * @param object $object
     * @param EntityManagerInterface $entityManager
     * @return mixed false if empty
     */
    private function getIdentifier($object, EntityManagerInterface $entityManager)
    {
        $ids = $entityManager->getClassMetadata(ClassUtils::getClass($object))->getIdentifierValues($object);

        if (!$ids) {
            return false;
        }

        return reset($ids);
    }

    /**
     * @param $object
     * @return string
     */
    private function getKey($object)
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
