<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves priceListId from ProductPrice entity to context for later use
 */
class StorePriceListInContextByProductPrice implements ProcessorInterface
{
    /**
     * @var PriceListIDContextStorageInterface
     */
    private $priceListIDContextStorage;

    /**
     * @param PriceListIDContextStorageInterface $priceListIDContextStorage
     */
    public function __construct(PriceListIDContextStorageInterface $priceListIDContextStorage)
    {
        $this->priceListIDContextStorage = $priceListIDContextStorage;
    }

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        $productPrice = $context->getResult();

        if (!$productPrice instanceof ProductPrice) {
            return;
        }

        $priceList = $productPrice->getPriceList();
        if (!$priceList || !$priceList->getId()) {
            return;
        }

        $this->priceListIDContextStorage->store($priceList->getId(), $context);
    }
}
