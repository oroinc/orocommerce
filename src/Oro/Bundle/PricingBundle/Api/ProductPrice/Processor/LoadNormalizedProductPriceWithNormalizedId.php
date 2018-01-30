<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadNormalizedEntity as ParentLoadNormalizedEntity;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * After update/create "get" request happens and to properly send context id
 * we modify it back to guid-pricelist format.
 */
class LoadNormalizedProductPriceWithNormalizedId extends ParentLoadNormalizedEntity
{
    /**
     * @var ProductPriceIDByContextNormalizerInterface
     */
    private $productPriceIDByContextNormalizer;

    /**
     * @param ActionProcessorBagInterface                $processorBag
     * @param ProductPriceIDByContextNormalizerInterface $productPriceIDByContextNormalizer
     */
    public function __construct(
        ActionProcessorBagInterface $processorBag,
        ProductPriceIDByContextNormalizerInterface $productPriceIDByContextNormalizer
    ) {
        $this->productPriceIDByContextNormalizer = $productPriceIDByContextNormalizer;

        parent::__construct($processorBag);
    }

    /**
     * @inheritDoc
     */
    protected function createGetContext(SingleItemContext $context, ActionProcessorInterface $processor)
    {
        $getContext = parent::createGetContext($context, $processor);

        $getContext->setId(
            $this->productPriceIDByContextNormalizer->normalize($getContext->getId(), $context)
        );

        return $getContext;
    }
}
