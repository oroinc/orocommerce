<?php

namespace Oro\Bundle\PricingBundle\Api\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * The flush data handler for ProductPrice entity.
 */
class BatchProductPriceFlushDataHandler implements BatchFlushDataHandlerInterface
{
    /** @var BatchFlushDataHandlerInterface */
    private $innerHandler;

    /** @var PriceManager */
    private $priceManager;

    public function __construct(
        BatchFlushDataHandlerInterface $innerHandler,
        PriceManager $priceManager
    ) {
        $this->innerHandler = $innerHandler;
        $this->priceManager = $priceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function startFlushData(array $items): void
    {
        $this->innerHandler->startFlushData($items);
    }

    /**
     * {@inheritDoc}
     */
    public function flushData(array $items): void
    {
        foreach ($items as $item) {
            $itemContext = $item->getContext();
            if (!$itemContext->hasErrors()) {
                $itemTargetContext = $itemContext->getTargetContext();
                if (null !== $itemTargetContext) {
                    $entity = $itemTargetContext->getResult();
                    if (null !== $entity) {
                        $this->priceManager->persist($entity);
                    }
                }
            }
        }

        $this->innerHandler->flushData($items);
    }

    /**
     * {@inheritDoc}
     */
    public function finishFlushData(array $items): void
    {
        $this->innerHandler->finishFlushData($items);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->innerHandler->clear();
    }
}
