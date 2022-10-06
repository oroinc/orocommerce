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
 * Class provides a methods to work with TaxValue entity
 *
 * Should not be used outside this bundle
 */
class TaxValueManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DoctrineFlushProgressListener */
    protected $doctrineFlushProgressListener;

    /** @var string */
    protected $taxValueClass;

    /** @var string */
    protected $taxClass;

    /** @var TaxValue[] */
    protected $taxValues = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param DoctrineFlushProgressListener $doctrineFlushProgressListener
     * @param string $taxValueClass
     * @param string $taxClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DoctrineFlushProgressListener $doctrineFlushProgressListener,
        $taxValueClass,
        $taxClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxValueClass = (string)$taxValueClass;
        $this->taxClass = (string)$taxClass;
        $this->doctrineFlushProgressListener = $doctrineFlushProgressListener;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return TaxValue
     */
    public function getTaxValue($entityClass, $entityId)
    {
        $key = $this->getTaxValueCacheKey($entityClass, $entityId);

        if (array_key_exists($key, $this->taxValues)) {
            return $this->taxValues[$key];
        }

        $taxValue = null;

        if ($entityId) {
            $taxValue = $this->findTaxValue($entityClass, $entityId);
        }

        return $this->cacheTaxValue($entityClass, $entityId, $taxValue);
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @param TaxValue|null $taxValue
     * @return TaxValue
     */
    private function cacheTaxValue($entityClass, $entityId, $taxValue)
    {
        if (!$taxValue) {
            /** @var TaxValue $taxValue */
            $taxValue = new $this->taxValueClass;
            $taxValue
                ->setEntityClass($entityClass)
                ->setEntityId($entityId);
        }

        // Save taxValues to cache only with entity IDs
        if ($entityId && $taxValue->getId()) {
            $key = $this->getTaxValueCacheKey($entityClass, $entityId);
            $this->taxValues[$key] = $taxValue;
        }

        return $taxValue;
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @return bool
     */
    private function isCached($entityClass, array $entityIds)
    {
        foreach ($entityIds as $entityId) {
            $key = $this->getTaxValueCacheKey($entityClass, $entityId);
            if (!array_key_exists($key, $this->taxValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     */
    public function preloadTaxValues($entityClass, array $entityIds)
    {
        if ($this->isCached($entityClass, $entityIds)) {
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
     * @param string $entityClass
     * @param array $entityIds
     * @return array|TaxValue[]
     */
    private function findTaxValues($entityClass, array $entityIds)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findBy(['entityClass' => $entityClass, 'entityId' => $entityIds]);
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return null|TaxValue
     */
    public function findTaxValue($entityClass, $entityId)
    {
        return $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findOneBy(['entityClass' => $entityClass, 'entityId' => $entityId]);
    }

    public function saveTaxValue(TaxValue $taxValue)
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
     * Flush tax value changes to database if it is allowed to do
     *
     * @param null|TaxValue|TaxValue[] $entity
     * @return bool
     */
    public function flushTaxValueIfAllowed($entity = null): bool
    {
        $em = $this->getTaxValueEntityManager();

        if (!$this->doctrineFlushProgressListener->isFlushInProgress($em)) {
            $em->flush($entity);

            return true;
        }

        return false;
    }

    /**
     * @param TaxValue $taxValue
     * @param bool $flush
     * @return bool
     */
    public function removeTaxValue(TaxValue $taxValue, $flush = false)
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

    /**
     * @param string $taxCode
     * @return Tax
     */
    public function getTax($taxCode)
    {
        return $this->doctrineHelper->getEntityRepository($this->taxClass)->findOneBy(['code' => $taxCode]);
    }

    /**
     * Clear caches
     */
    public function clear()
    {
        $this->taxValues = [];
    }

    /**
     * @return EntityManager
     */
    protected function getTaxValueEntityManager()
    {
        $em = $this->doctrineHelper->getEntityManagerForClass($this->taxValueClass);

        return $em instanceof VirtualProxyInterface ? $em->getWrappedValueHolderValue() : $em;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return string
     */
    protected function getTaxValueCacheKey($entityClass, $entityId)
    {
        return sprintf('%s#%s', $entityClass, $entityId);
    }
}
