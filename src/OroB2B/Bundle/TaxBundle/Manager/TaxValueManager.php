<?php

namespace OroB2B\Bundle\TaxBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;

class TaxValueManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $taxValueClass;

    /** @var TaxValue[] */
    protected $taxValues = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxValueClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        $taxValueClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxValueClass = (string)$taxValueClass;
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

        $taxValue = $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findOneBy(['entityClass' => $entityClass, 'entityId' => $entityId]);

        if (!$taxValue) {
            $taxValue = new $this->taxValueClass;
        }

        $this->taxValues[$key] = $taxValue;

        return $taxValue;
    }

    /**
     * @param TaxValue $taxValue
     */
    public function saveTaxValue(TaxValue $taxValue)
    {
        $em = $this->doctrineHelper->getEntityManager($taxValue);
        $em->persist($taxValue);
        $em->flush($taxValue);
    }

    /**
     * @param string $className
     * @param string $identifier
     * @return Tax
     */
    public function getTaxReference($className, $identifier)
    {
        return $this->doctrineHelper->getEntityReference($className, $identifier);
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
