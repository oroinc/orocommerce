<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Adds a PriceList id suffix to ProductPrice id
 */
class ProductPriceIDByPriceListIDNormalizer implements ProductPriceIDByContextNormalizerInterface
{
    /**
     * @internal
     */
    const TEMPLATE = '%s-%s';

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
    public function normalize(string $productPriceID, ContextInterface $context): string
    {
        return sprintf(
            self::TEMPLATE,
            $productPriceID,
            $this->priceListIDContextStorage->get($context)
        );
    }
}
