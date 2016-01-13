<?php

namespace OroB2B\Bundle\TaxBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TaxTransformerInterface[] */
    private $transformers = [];

    /** @var string */
    protected $taxValueClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxValueClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxValueClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxValueClass = (string)$taxValueClass;
    }

    /**
     * @param string $className
     * @param TaxTransformerInterface $transformer
     */
    public function addTransformer($className, TaxTransformerInterface $transformer)
    {
        $this->transformers[(string)$className] = $transformer;
    }

    /**
     * @param string $className
     * @return TaxTransformerInterface
     * @throws \InvalidArgumentException if TaxTransformerInterface is missing for $className
     */
    protected function getTaxTransformer($className)
    {
        if (!array_key_exists((string)$className, $this->transformers)) {
            throw new \InvalidArgumentException(sprintf('TaxTransformerInterface is missing for %s', $className));
        }

        return $this->transformers[(string)$className];
    }

    /**
     * @param object $object
     * @return Result
     */
    public function loadTax($object)
    {
        $className = $this->doctrineHelper->getEntityClass($object);
        $transformer = $this->getTaxTransformer($className);

        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        if (!$identifier) {
            throw new \InvalidArgumentException(sprintf('Can\'t load TaxValue for new %s entity', $className));
        }

        /** @var TaxValue $taxValue */
        $taxValue = $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findOneBy(['entityClass' => $className, 'entityId' => $identifier]);

        if (!$taxValue) {
            throw new \InvalidArgumentException(sprintf('TaxValue for %s#%s not found', $className, $identifier));
        }

        return $transformer->transform($taxValue);
    }
}
