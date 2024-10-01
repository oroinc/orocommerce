<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Trigger product re-indexation when product localized fallback values (names, descriptions etc.) changed.
 */
class ReindexProductFallbackValueChanged implements ProcessorInterface
{
    public function __construct(private IndexationEntitiesContainer $indexationEntitiesContainer)
    {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        $fallbackValue = $context->getData();
        if (!$fallbackValue) {
            return;
        }

        $this->processFallbackValue($fallbackValue);
    }

    private function processFallbackValue(ProductName|ProductDescription|ProductShortDescription $fallbackValue): void
    {
        $product = $fallbackValue->getProduct();
        if ($product?->getId()) {
            if ($fallbackValue instanceof ProductName
                && $fallbackValue->getLocalization() === null
                && $fallbackValue->getString() !== $product->getDenormalizedDefaultName()
            ) {
                $product->updateDenormalizedProperties();
            }

            $this->indexationEntitiesContainer->addEntity($product);
        }
    }
}
