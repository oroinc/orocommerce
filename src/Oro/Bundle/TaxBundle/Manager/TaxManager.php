<?php

namespace Oro\Bundle\TaxBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Event\TaxEventDispatcher;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

/**
 * Organizes logic to work with taxes such as saving, getting, removing for passed object (eg. Order)
 */
class TaxManager
{
    /** @var TaxTransformerInterface[] */
    protected $transformers = [];

    /** @var TaxFactory */
    protected $taxFactory;

    /** @var TaxEventDispatcher */
    protected $eventDispatcher;

    /** @var TaxValueManager */
    protected $taxValueManager;

    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /** @var CacheProvider */
    protected $cacheProvider;

    /** @var ObjectCacheKeyGenerator */
    protected $objectCacheKeyGenerator;

    public function __construct(
        TaxFactory $taxFactory,
        TaxEventDispatcher $eventDispatcher,
        TaxValueManager $taxValueManager,
        TaxationSettingsProvider $settingsProvider,
        CacheProvider $cacheProvider,
        ObjectCacheKeyGenerator $objectCacheKeyGenerator
    ) {
        $this->taxFactory = $taxFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->taxValueManager = $taxValueManager;
        $this->settingsProvider = $settingsProvider;
        $this->cacheProvider = $cacheProvider;
        $this->objectCacheKeyGenerator = $objectCacheKeyGenerator;
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
     * Load tax and return Result by object
     * @param object $object
     * @return Result
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be loaded
     */
    public function loadTax($object)
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);
        $transformer = $this->getTaxTransformer($taxable->getClassName());

        $taxValue = $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());

        return $transformer->transform($taxValue);
    }

    /**
     * Calculate Result by object
     *
     * @param object $object
     * @return Result
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function getTax($object)
    {
        $this->throwExceptionIfTaxationDisabled();

        return $this->getTaxable($object)->getResult();
    }

    /**
     * @param object $object
     * @param bool $includeItems
     * @return false|Result
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be saved
     */
    public function saveTax($object, $includeItems = false)
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        if (!$taxable->getIdentifier()) {
            return false;
        }

        $taxable = $this->getTaxable($object);

        $this->saveTaxValueByTaxable($taxable);

        if ($includeItems) {
            foreach ($taxable->getItems() as $item) {
                $this->saveTaxValueByTaxable($item);
            }
        }

        $this->taxValueManager->flushTaxValueIfAllowed();

        return $taxable->getResult();
    }

    /**
     * Remove tax value assigned to object
     *
     * @param object $object
     * @param bool $includeItems Remove object item taxes too
     * @return bool
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function removeTax($object, $includeItems = false)
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        if ($includeItems) {
            foreach ($taxable->getItems() as $item) {
                $this->removeTaxValue($item->getClassName(), $item->getIdentifier());
            }
        }

        return $this->removeTaxValue($taxable->getClassName(), $taxable->getIdentifier());
    }

    /**
     * Creates new or returns existing TaxValue instance based on object
     *
     * @internal
     * @param object $object
     * @return TaxValue
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if impossible to create TaxValue for object
     */
    public function createTaxValue($object)
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getTaxable($object);
        $result = $taxable->getResult();

        $transformer = $this->getTaxTransformer($taxable->getClassName());

        return $transformer->reverseTransform($result, $taxable);
    }

    /**
     * Returns existing TaxValue instance based on object
     *
     * @internal
     * @param object $object
     * @return TaxValue
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function getTaxValue($object)
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        return $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
    }

    /**
     * @param string $className
     * @param string $entityId
     * @return bool
     */
    protected function removeTaxValue($className, $entityId)
    {
        $taxValue = $this->taxValueManager->findTaxValue($className, $entityId);

        if (!$taxValue) {
            return false;
        }

        return $this->taxValueManager->removeTaxValue($taxValue);
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

        $taxable = $this->getCachedTaxable($object);
        $taxable->setResult($taxResult);

        $this->eventDispatcher->dispatch($taxable);

        return $taxable;
    }

    protected function saveTaxValueByTaxable(Taxable $taxable)
    {
        $itemResult = $taxable->getResult();

        $itemTransformer = $this->getTaxTransformer($taxable->getClassName());
        $taxItemValue = $itemTransformer->reverseTransform($itemResult, $taxable);

        // Save without flush, flush must be called separately
        $this->taxValueManager->saveTaxValue($taxItemValue);
    }

    /**
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    protected function throwExceptionIfTaxationDisabled()
    {
        if (!$this->settingsProvider->isEnabled()) {
            throw new TaxationDisabledException();
        }
    }

    /**
     * Returns cached taxation entity representation to reduce calls to TaxFactory which executes heavy
     * mapping logic
     * @param $object
     * @return Taxable
     */
    protected function getCachedTaxable($object)
    {
        $cacheKey = $this->objectCacheKeyGenerator->generate($object, 'tax');
        if (!$this->cacheProvider->contains($cacheKey)) {
            $taxable = $this->taxFactory->create($object);
            $this->cacheProvider->save($cacheKey, $taxable);

            return clone $taxable;
        }

        $taxable = clone $this->cacheProvider->fetch($cacheKey);

        return $taxable;
    }
}
