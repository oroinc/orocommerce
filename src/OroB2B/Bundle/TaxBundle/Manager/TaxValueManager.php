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

    /** @var string */
    protected $taxClass;

    /** @var TaxValue[] */
    protected $taxValues = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxValueClass
     * @param string $taxClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxValueClass, $taxClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxValueClass = (string)$taxValueClass;
        $this->taxClass = (string)$taxClass;
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
            /** @var TaxValue $taxValue */
            $taxValue = new $this->taxValueClass;
            $taxValue
                ->setEntityClass($entityClass)
                ->setEntityId($entityId);
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
     * @param string $taxCode
     * @return Tax
     */
    public function getTax($taxCode)
    {
        return $this->doctrineHelper->getEntityRepository($this->taxClass)->findOneBy(['code' => $taxCode]);
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
