<?php

namespace Oro\Bundle\TaxBundle\Manager;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Event\TaxEventDispatcher;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Organizes logic to work with taxes such as saving, getting, removing for passed object (eg. Order)
 */
class TaxManager
{
    protected array $transformers = [];

    public function __construct(
        protected TaxFactory $taxFactory,
        protected TaxEventDispatcher $eventDispatcher,
        protected TaxValueManager $taxValueManager,
        protected TaxationSettingsProvider $settingsProvider,
        protected CacheInterface $cacheProvider,
        protected ObjectCacheKeyGenerator $objectCacheKeyGenerator
    ) {
    }

    public function addTransformer(string $className, TaxTransformerInterface $transformer): void
    {
        $this->transformers[$className] = $transformer;
    }

    /**
     * Loads tax and returns Result by object.
     *
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be loaded
     */
    public function loadTax(object $object): ?Result
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        return $this->getTaxTransformer($taxable->getClassName())->transform(
            $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier())
        );
    }

    /**
     * Calculates Result by object.
     *
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be loaded
     */
    public function getTax(object $object): ?Result
    {
        $this->throwExceptionIfTaxationDisabled();

        return $this->getTaxable($object)->getResult();
    }

    /**
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be loaded
     */
    public function saveTax(object $object, bool $includeItems = false): Result|null|false
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        if (!$taxable->getIdentifier()) {
            return false;
        }

        $taxable = $this->getTaxable($object);

        $this->saveTaxValueByTaxable($taxable);

        if ($includeItems && !$taxable->isKitTaxable()) {
            foreach ($taxable->getItems() as $item) {
                $this->saveTaxValueByTaxable($item);
            }
        }

        $this->taxValueManager->flushTaxValueIfAllowed();

        return $taxable->getResult();
    }

    /**
     * Removes tax value assigned to object.
     *
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function removeTax(object $object, bool $includeItems = false): ?bool
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
     * Creates new or returns existing TaxValue instance based on object.
     *
     * @throws TaxationDisabledException if taxation disabled in system configuration
     * @throws \InvalidArgumentException if taxes for object could not be loaded
     */
    public function createTaxValue(object $object): ?TaxValue
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getTaxable($object);

        return $this->getTaxTransformer($taxable->getClassName())
            ->reverseTransform($taxable->getResult(), $taxable);
    }

    /**
     * Returns existing TaxValue instance based on object.
     *
     * @throws TaxationDisabledException if taxation disabled in system configuration
     */
    public function getTaxValue(object $object): ?TaxValue
    {
        $this->throwExceptionIfTaxationDisabled();

        $taxable = $this->getCachedTaxable($object);

        return $this->taxValueManager->getTaxValue($taxable->getClassName(), $taxable->getIdentifier());
    }

    protected function removeTaxValue(string $className, string|int $entityId): bool
    {
        $taxValue = $this->taxValueManager->findTaxValue($className, $entityId);
        if (null === $taxValue) {
            return false;
        }

        return $this->taxValueManager->removeTaxValue($taxValue);
    }

    protected function getTaxable(object $object): Taxable
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

    protected function saveTaxValueByTaxable(Taxable $taxable): void
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
    protected function throwExceptionIfTaxationDisabled(): void
    {
        if (!$this->settingsProvider->isEnabled()) {
            throw new TaxationDisabledException();
        }
    }

    /**
     * Returns cached taxation entity representation to reduce calls to TaxFactory which executes heavy mapping logic
     */
    protected function getCachedTaxable(object $object): Taxable
    {
        $cacheKey = $this->objectCacheKeyGenerator->generate($object, 'tax');
        return clone $this->cacheProvider->get($cacheKey, function () use ($object) {
            return $this->taxFactory->create($object);
        });
    }

    /**
     * @throws \InvalidArgumentException if a TAX transformer does not exist for the given class
     */
    protected function getTaxTransformer(?string $className): TaxTransformerInterface
    {
        if (!\array_key_exists($className, $this->transformers)) {
            throw new \InvalidArgumentException(sprintf('TaxTransformerInterface is missing for %s', $className));
        }

        return $this->transformers[$className];
    }
}
