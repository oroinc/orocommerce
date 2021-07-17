<?php

namespace Oro\Bundle\PricingBundle\Api\Handler;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerFactoryInterface;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchFlushDataHandlerInterface;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * The factory that creates a flush data handler for ProductPrice entity for a batch operation.
 */
class BatchProductPriceFlushDataHandlerFactory implements BatchFlushDataHandlerFactoryInterface
{
    /** @var BatchFlushDataHandlerFactoryInterface */
    private $innerFactory;

    /** @var PriceManager */
    private $priceManager;

    public function __construct(BatchFlushDataHandlerFactoryInterface $innerFactory, PriceManager $priceManager)
    {
        $this->innerFactory = $innerFactory;
        $this->priceManager = $priceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function createHandler(string $entityClass): ?BatchFlushDataHandlerInterface
    {
        return new BatchProductPriceFlushDataHandler(
            $this->innerFactory->createHandler($entityClass),
            $this->priceManager
        );
    }
}
