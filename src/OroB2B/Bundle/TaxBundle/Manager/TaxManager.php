<?php

namespace OroB2B\Bundle\TaxBundle\Manager;

use OroB2B\Bundle\TaxBundle\Event\TaxEventDispatcher;
use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

class TaxManager
{
    /** @var TaxTransformerInterface[] */
    private $transformers = [];

    /** @var TaxFactory */
    protected $taxFactory;

    /** @var TaxEventDispatcher */
    protected $eventDispatcher;

    /** @var TaxValueManager */
    protected $taxValueManager;

    /**
     * @param TaxFactory $taxFactory
     * @param TaxEventDispatcher $eventDispatcher
     * @param TaxValueManager $taxValueManager
     */
    public function __construct(
        TaxFactory $taxFactory,
        TaxEventDispatcher $eventDispatcher,
        TaxValueManager $taxValueManager
    ) {
        $this->taxFactory = $taxFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->taxValueManager = $taxValueManager;
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
        $taxable = $this->taxFactory->create($object);
        $transformer = $this->getTaxTransformer($taxable->getClassName());

        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());

        return $transformer->transform($taxValue);
    }

    /**
     * @param object $object
     * @return Result
     */
    public function getTax($object)
    {
        return $this->getTaxable($object)->getResult();
    }

    /**
     * @param object $object
     * @return Taxable
     */
    protected function getTaxable($object)
    {
        try {
            $taxResult = $this->loadTax($object);
        } catch (\InvalidArgumentException $e) {
            $taxResult = new Result();
        }

        $taxable = $this->taxFactory->create($object);
        $taxable->setResult($taxResult);

        $this->eventDispatcher->dispatch($taxable);

        return $taxable;
    }

    /**
     * @param object $object
     * @return Result|false
     */
    public function saveTax($object)
    {
        $taxable = $this->taxFactory->create($object);

        if (!$taxable->getIdentifier()) {
            return false;
        }

        $taxable = $this->getTaxable($object);
        $result = $taxable->getResult();

        $transformer = $this->getTaxTransformer($taxable->getClassName());
        $taxValue = $transformer->reverseTransform($result, $taxable);

        $this->taxValueManager->saveTaxValue($taxValue);

        return $result;
    }

    /**
     * @param $object
     * @return Result|false
     */
    public function saveTaxWithItems($object)
    {
        $taxable = $this->taxFactory->create($object);

        if (!$taxable->getIdentifier()) {
            return false;
        }

        $taxable = $this->getTaxable($object);
        $result = $taxable->getResult();

        $transformer = $this->getTaxTransformer($taxable->getClassName());
        $taxValue = $transformer->reverseTransform($result, $taxable);
        $this->taxValueManager->saveTaxValue($taxValue);

        $this->saveTaxItems($taxable);

        return $result;
    }

    /**
     * @param Taxable $taxable
     */
    protected function saveTaxItems(Taxable $taxable)
    {
        foreach ($taxable->getItems() as $item) {
            $itemResult = $item->getResult();

            $itemTransformer = $this->getTaxTransformer($item->getClassName());
            $taxItemValue = $itemTransformer->reverseTransform($itemResult, $item);

            $this->taxValueManager->saveTaxValue($taxItemValue);
        }
    }
}
