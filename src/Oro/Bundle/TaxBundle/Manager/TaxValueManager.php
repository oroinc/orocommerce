<?php

namespace Oro\Bundle\TaxBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use ProxyManager\Proxy\VirtualProxyInterface;

/**
 * Provides methods to work with TaxValue entity.
 * Should not be used outside this bundle.
 */
class TaxValueManager
{
    /** @var TaxValue[] */
    private array $taxValues = [];

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private DoctrineFlushProgressListener $doctrineFlushProgressListener,
        private string $taxValueClass,
        private string $taxClass
    ) {
    }

    public function getTaxValue(?string $entityClass, ?string $entityId): ?TaxValue
    {
        $key = $this->getTaxValueCacheKey($entityClass, $entityId);
        if (\array_key_exists($key, $this->taxValues)) {
            return $this->taxValues[$key];
        }

        return $this->cacheTaxValue(
            $entityClass,
            $entityId,
            $entityId ? $this->findTaxValue($entityClass, $entityId) : null
        );
    }

    private function cacheTaxValue(?string $entityClass, ?string $entityId, ?TaxValue $taxValue): TaxValue
    {
        if (!$taxValue) {
            /** @var TaxValue $taxValue */
            $taxValue = new $this->taxValueClass();
            $taxValue->setEntityClass($entityClass);
            $taxValue->setEntityId($entityId);
        }

        // Save taxValues to cache only with entity IDs
        if ($entityId && $taxValue->getId()) {
            $key = $this->getTaxValueCacheKey($entityClass, $entityId);
            $this->taxValues[$key] = $taxValue;
        }

        return $taxValue;
    }

    private function checkCached(string $entityClass, array $entityIds): array
    {
        $notCachedEntityIds = [];
        foreach ($entityIds as $entityId) {
            $key = $this->getTaxValueCacheKey($entityClass, $entityId);
            if (!\array_key_exists($key, $this->taxValues)) {
                $notCachedEntityIds[] = $entityId;
            }
        }

        return $notCachedEntityIds;
    }

    public function preloadTaxValues(string $entityClass, array $entityIds): void
    {
        $entityIds = $this->checkCached($entityClass, $entityIds);
        if (empty($entityIds)) {
            return;
        }

        $taxValues = $this->findTaxValues($entityClass, $entityIds);
        $taxValuesByEntityIds = [];

        foreach ($taxValues as $taxValue) {
            $taxValuesByEntityIds[$taxValue->getEntityId()] = $taxValue;
        }

        foreach ($entityIds as $entityId) {
            $taxValue = !empty($taxValuesByEntityIds[$entityId]) ? $taxValuesByEntityIds[$entityId] : null;

            $this->cacheTaxValue($entityClass, $entityId, $taxValue);
        }
    }

    /**
     * @return TaxValue[]
     */
    private function findTaxValues(?string $entityClass, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        return $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findBy(['entityClass' => $entityClass, 'entityId' => $entityIds]);
    }

    public function findTaxValue(?string $entityClass, ?string $entityId): ?TaxValue
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findOneBy(['entityClass' => $entityClass, 'entityId' => $entityId]);
    }

    public function saveTaxValue(TaxValue $taxValue): void
    {
        $em = $this->getTaxValueEntityManager();
        $em->persist($taxValue);

        if ($this->doctrineFlushProgressListener->isFlushInProgress($em)) {
            // If flush is in progress we can compute changeset and doctrine will update it
            $em->getUnitOfWork()->computeChangeSet(
                $em->getClassMetadata(ClassUtils::getClass($taxValue)),
                $taxValue
            );
        }
    }

    /**
     * @param TaxValue|TaxValue[]|null $entity
     *
     * @return bool
     */
    public function flushTaxValueIfAllowed(array|TaxValue|null $entity = null): bool
    {
        $em = $this->getTaxValueEntityManager();
        if (!$this->doctrineFlushProgressListener->isFlushInProgress($em)) {
            $em->flush($entity);

            return true;
        }

        return false;
    }

    public function removeTaxValue(TaxValue $taxValue, bool $flush = false): bool
    {
        $em = $this->getTaxValueEntityManager();
        if (!$em->contains($taxValue)) {
            return false;
        }

        $em->remove($taxValue);

        if ($flush) {
            $em->flush($taxValue);
        }

        return true;
    }

    public function getTax(string $taxCode): ?Tax
    {
        return $this->doctrineHelper->getEntityRepository($this->taxClass)
            ->findOneBy(['code' => $taxCode]);
    }

    public function clear(): void
    {
        $this->taxValues = [];
    }

    private function getTaxValueEntityManager(): EntityManager
    {
        $em = $this->doctrineHelper->getEntityManagerForClass($this->taxValueClass);

        return $em instanceof VirtualProxyInterface ? $em->getWrappedValueHolderValue() : $em;
    }

    private function getTaxValueCacheKey(?string $entityClass, ?string $entityId): string
    {
        return sprintf('%s#%s', $entityClass, $entityId);
    }
}
