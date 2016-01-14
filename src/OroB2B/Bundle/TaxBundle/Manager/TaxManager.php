<?php

namespace OroB2B\Bundle\TaxBundle\Manager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManager
{
    /** @var TaxTransformerInterface[] */
    private $transformers = [];

    /** @var TaxFactory */
    protected $taxFactory;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $taxValueClass;

    /** @var TaxValue[] */
    protected $taxValues = [];

    /**
     * @param TaxFactory $taxFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxValueClass
     */
    public function __construct(
        TaxFactory $taxFactory,
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        $taxValueClass
    ) {
        $this->taxFactory = $taxFactory;
        $this->eventDispatcher = $eventDispatcher;
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
        if (!array_key_exists($className, $this->transformers)) {
            throw new \InvalidArgumentException(sprintf('TaxTransformerInterface is missing for %s', $className));
        }

        return $this->transformers[$className];
    }

    /**
     * @param object $object
     * @return Result
     */
    public function loadTax($object)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($object);
        $transformer = $this->getTaxTransformer($entityClass);

        $entityId = $this->getSingleEntityIdentifier($object);

        $taxValue = $this->getTaxValue($entityClass, $entityId);

        return $transformer->transform($taxValue);
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return TaxValue
     */
    protected function getTaxValue($entityClass, $entityId)
    {
        $key = $this->getTaxValueCacheKey($entityClass, $entityId);

        if (array_key_exists($key, $this->taxValues)) {
            return $this->taxValues[$key];
        }

        $taxValue = $this->doctrineHelper->getEntityRepositoryForClass($this->taxValueClass)
            ->findOneBy(['entityClass' => $entityClass, 'entityId' => $entityId]);

        if (!$taxValue) {
            $taxValue = new TaxValue();
        }

        $this->taxValues[$key] = $taxValue;

        return $taxValue;
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

    /**
     * @param object $object
     * @return mixed|null
     */
    protected function getSingleEntityIdentifier($object)
    {
        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object);

        if (!$identifier) {
            throw new \InvalidArgumentException('Object identifier is missing');
        }

        return $identifier;
    }

    /**
     * @param object $object
     * @return Result
     */
    public function getTax($object)
    {
        try {
            $taxResult = $this->loadTax($object);
        } catch (\InvalidArgumentException $e) {
            $taxResult = new Result();
        }

        $taxable = $this->taxFactory->create($object);

        $this->eventDispatcher->dispatch(ResolveTaxEvent::NAME, new ResolveTaxEvent($taxable, $taxResult));

        return $taxResult;
    }

    /**
     * @param object $object
     * @return Result
     */
    public function saveTax($object)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($object);
        $entityId = $this->getSingleEntityIdentifier($object);

        $transformer = $this->getTaxTransformer($entityClass);

        $result = $this->getTax($object);

        $taxValue = $transformer->reverseTransform($this->getTaxValue($entityClass, $entityId), $result);

        $taxValue->setEntityClass($entityClass);
        $taxValue->setEntityId($entityId);

        /** @todo: context from resolver */
        $taxValue->setAddress('address');

        $em = $this->doctrineHelper->getEntityManager($taxValue);
        $em->persist($taxValue);
        $em->flush($taxValue);

        return $result;
    }
}
